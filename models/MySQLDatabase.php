<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class MySQLDatabase implements DatabaseInterface {
    private $connection;
    private $query;
    private $result;
    private $affectedRows;

    public function __construct(array $dbOptions) {
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
        return $this->connection;
    }
    
    public function __destruct() {
        if ($this->connection->connect_errno===0) {
            $this->connection->close();
        }
    }
    
    public function getQuery() {
        return $this->query;
    }
    
    public function getError($key=null) {
        $error=[
            'connectionDescription'=>$this->connection->connect_error,
            'connectionCode'=>$this->connection->connect_errno,
            'description'=>null,
            'code'=>null,
        ];
        if ($error['connectionCode']===0) {
            $error['description']=$this->connection->error;
            $error['code']=$this->connection->errno;
        }
        return ($key===null) ? $error : $error[$key];
    }
    
    public function getStatus() {
        return ($this->getError('code')==0);
    }

    private function execWithoutBind(string $query) {
        $this->result = $this->connection->query($query);
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
    public function getInsertId() {
        return $this->connection->insert_id;
    }
    
    private function fetchResult($syncId=null) {
        $result=[];
        if ( is_object($this->result) && property_exists($this->result, 'num_rows') 
                && $this->result->num_rows > 0 ) {
            while ($row = $this->result->fetch_assoc()) {
                $result[]=$row;
            }
        }
        return $result;
    }
    
    private function fetchStmt() {
        $meta = $this->result->result_metadata(); 
        if (!$meta) {
            return false;
        }
        while ($field = $meta->fetch_field()) 
        { 
            $params[] = &$row[$field->name];
        } 
        call_user_func_array(array($this->result, 'bind_result'), $params); 
        $result = [];
        while ($this->result->fetch()) { 
            foreach($row as $key => $val) 
            { 
                $c[$key] = $val; 
            } 
            $result[] = $c; 
        }
        return $result;
    }

    private function fetch() {
        return (get_class($this->result)=='mysqli_result') 
                ? $this->fetchResult() : $this->fetchStmt();
    }
    
    private function syncId($data,$syncId) {
        $result=[];
        foreach ($data as $key=>$value) {
            $k = isset($value[$syncId]) ? $value[$syncId] : $key;
            if (!isset($result[$k])) {
                $result[$k] = $value;
            } else {
                if (isset($result[$k][0])) {
                    $result[$k][]=$value;
                } else {
                    $result[$k]=[$result[$k],$value];
                }
            }
        }
        return $result;
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
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0] : null;
        return $data;
    }
    
    public function getScalar() {
        if (!is_object($this->result)) {
            return false;
        }
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0][key($data[0])] : null;
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
    
}
