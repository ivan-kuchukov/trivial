<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Model for work with Database by PDO
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class PDODatabase implements DatabaseInterface {
    private $connection;
    private $error=[
        'connectionDescription'=>0,
        'connectionCode'=>0,
        'description'=>0,
        'code'=>0,
    ];
    private $result;
    private $resultType;
    private $affectedRows;
    private $errorLog;
    private $queriesLog;
    private $queriesCount = 0;
    private $errorQueriesCount = 0;
    private $type = [
        'mysql'=>'mysql',
        'mariadb'=>'mysql',
        'postgresql'=>'pgsql',
    ];

    public function __construct(array $dbOptions) {
        $this->errorLog = App::params('db.errorLog');
        $this->queriesLog = App::params('db.queriesLog');
        $type = $this->type[strtolower($dbOptions['type'])] ?? 'mysql';
        try {
            $this->connection = new \PDO(
                $type.':'
                    .'dbname='.$dbOptions['database'].';'
                    .'host='.$dbOptions['servername'], 
                $dbOptions['username'], 
                $dbOptions['password']);
        } catch (\PDOException $e) {
            $this->error['connectionDescription'] = $e->getMessage();
            $this->error['connectionCode'] = $e->getCode();
        }
        return $this->connection;
    }
    
    public function __destruct() {
        if ($this->getError('connectionCode')===0) {
            $this->connection=null;
        }
    }
    
    public function getError($key=null) {
        if ($this->connection) {
            $this->error['code'] = $this->connection->errorCode();
            $this->error['description'] = $this->connection->errorInfo()[2];
        }
        return is_null($key) ? $this->error : $this->error[$key];
    }
    
    public function setErrorMode($errorLog) {
        $this->errorLog = $errorLog;
    }
    
    public function getErrorMode() {
        return $this->errorLog;
    }
    
    public function getStatus() {
        return ($this->getError('code')==0);
    }
    
    private function execWithoutBind(string $query) {
        $this->resultType = 'query';
        $this->result = $this->connection->query($query);
        //$this->affectedRows = $this->connection->rowCount();
    }
    
    private function execWithBind(string $query, array $vars=[]) {
        $this->resultType = 'prepare';
        $modifyVars=[];
        foreach ($vars as $key=>$var) {
            if (is_array($var)) {
                switch (substr($var[1],0,1)) {
                    case 'i':
                        $modifyVars[$key]=(int)$var[0];
                        break;
                    case 's':
                        $modifyVars[$key]=(string)$var[0];
                        break;
                    default:
                        $modifyVars[$key]=$var[0];
                }
            } else {
                $modifyVars[$key]=$var;
            }
        }
        $this->result = $this->connection->prepare($query);
        $this->result->execute($modifyVars);
        //$this->affectedRows = $this->connection->rowCount();
    }
    
    public function exec(string $query, array $vars=[]) {
        empty($vars) ? $this->execWithoutBind($query) : $this->execWithBind($query, $vars);
        $this->queriesCount++;
        $query = str_replace(PHP_EOL,' ',$query);
        $query = preg_replace('/[ ]+/',' ',$query);
        if ( $this->getError('code') != 0 ) {
            $this->errorQueriesCount++;
            if ($this->errorLog=="display") {
                echo 'Error in DB query: [' . $this->getError('code') . '] '
                    . $this->getError('description') . PHP_EOL;
            }
            if ($this->errorLog=="log" || $this->errorLog=="display") {
                Log::add("dbDebugFile",__METHOD__, $query . ". ERROR: " . $this->getError('description'));
                Log::add("errorsFile",__METHOD__, $query . ". ERROR: " . $this->getError('description'));
            }
        } else if ( $this->queriesLog ) {
            $text = $query . (!empty($vars) ? '. ' . json_encode($vars) : '' );
            Log::add("dbDebugFile",__METHOD__, $text);
        }
        return $this;
    }
    
    /*private function fetchResult($syncId=null) {
        $result=[];
        if ( is_object($this->result) && property_exists($this->result, 'num_rows') 
                && $this->result->num_rows > 0 ) {
            while ($row = $this->result->fetch_assoc()) {
                $result[]=$row;
            }
        }
        return $result;
    }*/
    
    /*private function fetchStmt() {
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
    }*/

    private function fetch() {
        if($this->resultType==='query') {
            $data = [];
            foreach ($this->result as $row) {
                $data[] = $row;
            }
            return $data;
        } else {
            return $this->result->fetchAll();
        }
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
        if ($this->queriesLog) {
            $text = (count($data)>0 
                ? count($data) . ' rows: ' . json_encode(current($data)) . ',...' 
                : '[]');
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
    
    public function transaction($flags=null) {
        if ($this->connection->beginTransaction()) {
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
        if ($this->connection->rollBack()) {
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
