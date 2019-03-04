<?php

namespace trivial\models;
use trivial\controllers\App;
use trivial\models\Database;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class MySQLDatabase extends Database {
    protected $connection;
    protected $result;
    protected $affectedRows;
    protected $resultType = [
        Database::FETCH_ASSOC=>MYSQLI_ASSOC,
        Database::FETCH_NUM=>MYSQLI_NUM,
        Database::FETCH_BOTH=>MYSQLI_BOTH,
    ];

    public function __construct(array $dbOptions) {
        parent::__construct($dbOptions);
        if (isset($dbOptions['persistentConnection']) && $dbOptions['persistentConnection'] 
                && substr($dbOptions['servername'],0,2)!=="p:") {
            $dbOptions['servername']="p:".$dbOptions['servername'];
        }
        @$this->connection = new \mysqli(
            $dbOptions['servername'], 
            $dbOptions['username'], 
            $dbOptions['password'], 
            $dbOptions['database']
        );
        if ($this->connection->connect_errno!==0) {
            $this->errorHandler($this->connection->connect_error
                ,$this->connection->connect_errno,'connection');
        }
        return $this->connection;
    }
    
    public function __destruct() {
        if ($this->connection->connect_errno===0) {
            $this->connection->close();
        }
    }
    
    public function getError($key=null) {
        $error=[
            'connectionDescription'=>$this->connection->connect_error,
            'connectionCode'=>$this->connection->connect_errno,
            'description'=>$this->connection->error,
            'code'=>$this->connection->errno,
        ];
        return ($key===null) ? $error : $error[$key];
    }
    
    public function getStatus() {
        return ($this->getError('code')==0);
    }

    private function execWithoutBind(string $query) {
        $this->result = $this->connection->query($query);
        if ($this->connection->errno!==0) {
            $this->errorHandler($this->connection->error
                ,$this->connection->errno,'mysqli::query');
        }
        $this->affectedRows = $this->connection->affected_rows;
    }
    
    /**
     * Modify query and binding variables to PostgreSQL style 
     * @param string $query
     * @param array $vars
     * @return boolean
     */
    private function nameBind(string &$query, array &$vars) {
        if (ArrayHelper::getType($vars)=='mixed') {
            $this->connection->error='Incorrect type of variables.';
            $this->connection->errno=-1;
            return false;
        } elseif (ArrayHelper::getType($vars)=='associative') {
            $modifyVars=[];
            foreach ($vars as $key=>$var) {
                $pattern='~".*?"(*SKIP)(*FAIL)|\'.*?\'(*SKIP)(*FAIL)|\:' . preg_quote($key, '~') . '\b~s';
                $matches=[];
                preg_match_all($pattern,$query,$matches,PREG_OFFSET_CAPTURE);
                $query=preg_replace($pattern, '?'.str_repeat(' ',strlen($key)), $query);
                foreach ($matches[0] as $match) {
                    $modifyVars[$match[1]]=$var;
                }
            }
            $vars=$modifyVars;
        }
        return true;
    }

    private function execWithBind(string &$query, array &$vars=[]) {
        if (!$this->nameBind($query,$vars)) {
            return false;
        }
        $types = '';
        $values = [];
        foreach ($vars as $var) {
            $values[] = is_array($var) ? $var[0] : $var;
            $types .= (is_array($var) && !empty($var[1])) ? substr($var[1],0,1) : 's';
        }
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            $this->errorHandler($this->connection->error
                ,$this->connection->errno,'mysqli::prepare');
            return $this;
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $this->result = $stmt;
        $this->affectedRows = $this->connection->affected_rows;
    }

    public function exec(string $query, array $vars=[]) {
        $this->query = $query;
        empty($vars) ? $this->execWithoutBind($query) : $this->execWithBind($query, $vars);
        $query = str_replace(PHP_EOL,' ',$query);
        $query = preg_replace('/[ ]+/',' ',$query);
        return $this;
    }
    
    /**
     * get last inserted id
     * @return type
     */
    public function getInsertedId() {
        return $this->connection->insert_id;
    }
    
    private function fetchResult($all) {
        $result = [];
        if ( is_object($this->result) && property_exists($this->result, 'num_rows') 
                && $this->result->num_rows > 0 ) {
            while ($row = $this->result->fetch_array(
                    $this->resultType[$this->attributes[Database::ATTR_DEFAULT_FETCH_MODE]]
            )) {
                if (!$all) {
                    $result = $row;
                    break;
                }
                $result[] = $row;
            }
        }
        return !empty($result) ? $result : null;
    }
    
    private function fetchStmt($all) {
        $meta = $this->result->result_metadata(); 
        if (!$meta) {
            return false;
        }
        $key = 0;
        while ($field = $meta->fetch_field()) 
        { 
            if ($this->attributes[Database::ATTR_DEFAULT_FETCH_MODE] === Database::FETCH_BOTH) {
                $params[] = &$row[$field->name];
                $row[$key++] = &$row[$field->name];
            } elseif ($this->attributes[Database::ATTR_DEFAULT_FETCH_MODE] === Database::FETCH_NUM) {
                $params[] = &$row[$key++];
            } else { // Database::FETCH_ASSOC
                $params[] = &$row[$field->name];
            }
        }
        $this->result->bind_result(...$params);
        $result = [];
        while ($this->result->fetch()) { 
            foreach($row as $key => $val) 
            { 
                $c[$key] = $val; 
            } 
            if (!$all) {
                $result = $c;
                break;
            }
            $result[] = $c;
        }
        return !empty($result) ? $result : null;
    }

    private function fetch($all=true) {
        return (get_class($this->result)=='mysqli_result') 
                ? $this->fetchResult($all) : $this->fetchStmt($all);
    }
    
    public function getAll($syncId=null) {
        if (!is_object($this->result)) {
            return false;
        }
        $data = $this->fetch();
        $data = (!is_null($syncId)) ? $this->syncId($data,$syncId) : $data;
        return $data;
    }
    
    public function getArray() {
        if (!is_object($this->result)) {
            return false;
        }
        $data = $this->fetch(false);
        return $data;
    }
    
    public function getScalar() {
        if (!is_object($this->result)) {
            return false;
        }
        $data = $this->fetch(false);
        $data = $data[key($data)];
        return $data;
    }
    
    public function transaction($flags=MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT) {
        if ($this->connection->begin_transaction($flags)) {
            return true;
        } else {
            $this->error['description'] = 'Transaction begin fail';
            $this->error['code'] = 'UE-1';
            return false;
        }
    }
    
    public function commit() {
        if ($this->connection->commit()) {
            return true;
        } else {
            $this->error['description'] = 'Transaction commit fail';
            $this->error['code'] = 'UE-2';
            return false;
        }
    }
    
    public function rollback() {
        if ($this->connection->rollback()) {
            return true;
        } else {
            $this->error['description'] = 'Transaction rollback fail';
            $this->error['code'] = 'UE-3';
            return false;
        }
    }
    
    /**
     * Access to mysqli connection variable
     * @return type
     */
    public function connection() {
        return $this->connection;
    }

}
