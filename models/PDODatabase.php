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
    private $query;
    private $error=[
        'connectionDescription'=>0,
        'connectionCode'=>0,
        'description'=>0,
        'code'=>0,
    ];
    private $result;
    private $resultType;
    private $affectedRows;
    private $type = [
        'mysql'=>'mysql',
        'mariadb'=>'mysql',
        'postgresql'=>'pgsql',
    ];

    public function __construct(array $dbOptions) {
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
    
    public function getQuery() {
        return $this->query;
    }
    
    public function getError($key=null) {
        if ($this->connection) {
            $this->error['code'] = $this->connection->errorCode();
            $this->error['description'] = $this->connection->errorInfo()[2];
        }
        return is_null($key) ? $this->error : $this->error[$key];
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
        $query = str_replace(PHP_EOL,' ',$query);
        $query = preg_replace('/[ ]+/',' ',$query);
        return $this;
    }
    
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
        return $data;
    }
    
    public function getArray() {
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0] : null;
        return $data;
    }
    
    public function getScalar() {
        $data = $this->fetch();
        $data = isset($data[0]) ? $data[0][key($data[0])] : null;
        return $data;
    }
    
    public function transaction($flags=null) {
        if ($this->connection->beginTransaction()) {
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
        if ($this->connection->rollBack()) {
            return true;
        } else {
            $this->error['description'] = 'Transaction rollback fail';
            $this->error['code'] = 'UE-3';
            return false;
        }
    }
    
}