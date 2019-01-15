<?php

namespace models;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class MariaDatabase implements DatabaseInterface {
    private $connection;
    private $result;
    private $affectedRows;
    private $errorMode;
    private $queriesLog;
    private $queriesCount = 0;
    private $errorQueriesCount = 0;

    public function __construct(array $dbOptions) {
        $this->errorMode = \controllers\App::params('db.errorMode');
        $this->queriesLog = \controllers\App::params('db.queriesLog');
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
    
    public function setErrorMode($errorMode) {
        $this->errorMode = $errorMode;
    }
    
    public function getErrorMode() {
        return $this->errorMode;
    }
    
    public function getResult() {
        return $this->result;
    }

    private function execWithoutBind(string $query) {
        $this->result = $this->connection->query($query);
        $this->affectedRows = $this->connection->affected_rows;
    }
    
    private function execWithBind(string $query, array $vars=[]) {
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
        empty($vars) ? $this->execWithoutBind($query) : $this->execWithBind($query, $vars);
        if ( ! $this->result ) {
            if ($this->errorMode=="debug" && $this->connection->errno>0 ) {
                echo 'Error in DB query: [' . $this->connection->errno . '] '
                    . $this->connection->error . PHP_EOL;
            } elseif ($this->errorMode=="log") {
                Log::add("errorsFile",__METHOD__, $this->connection->error);
            }
        }
        if ( $this->queriesLog ) {
            $text = $query;
            $text .= (!empty($vars)) ? PHP_EOL . ' ' . json_encode($vars) : '';
            if ($this->connection->errno!==0) {
                $text .= PHP_EOL . ' Error:' . $this->connection->error;
                $this->errorQueriesCount++;
            }
            $this->queriesCount++;
            Log::add("dbDebugFile",__METHOD__, $text);
        }
        return $this;
    }
    
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
        return (get_class($this->result)=='mysqli_result') ? $this->fetchResult() : $this->fetchStmt();
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
        $data = $this->fetch();
        $data = (!is_null($syncId)) ? $this->syncId($data,$syncId) : $data;
        $text = (count($data)>0 ? count($data) . ' rows: ' . json_encode(current($data)) . ',...' : '[]');
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, $text);
        }
        return $data;
    }
    
    public function getArray() {
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0] : null;
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, json_encode($data));
        }
        return $data;
    }
    
    public function getScalar() {
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0][key($data[0])] : null;
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, (is_null($data) ? 'null' : $data));
        }
        return $data;
    }
    
    public function transaction($flags=MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT) {
        if ($this->connection->begin_transaction($flags)) {
            return true;
        } else {
            Log::add("errorsFile",__METHOD__, 'Transaction begin fail');
            return false;
        }
    }
    
    public function commit() {
        if ($this->connection->commit()) {
            return true;
        } else {
            Log::add("errorsFile",__METHOD__, 'Transaction commit fail');
            return false;
        }
    }
    
    public function rollback() {
        if ($this->connection->rollback()) {
            return true;
        } else {
            Log::add("errorsFile",__METHOD__, 'Transaction rollback fail');
            return false;
        }
    }
    
    public function statistics() {
        return [
            'queriesCount' => $this->queriesCount,
            'errorQueriesCount' => $this->errorQueriesCount,
        ];
    }
    
}
