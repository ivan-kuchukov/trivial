<?php

namespace trivial\models;
use trivial\controllers\App;
use trivial\models\Database;

/**
 * Model for work with Database by PDO
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class PDODatabase extends Database implements DatabaseInterface {
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
    private $type = [
        'mysql'=>'mysql',
        'mariadb'=>'mysql',
        'postgresql'=>'pgsql',
    ];

    public function __construct(array $dbOptions) {
        parent::__construct($dbOptions);
        $type = $this->type[strtolower($dbOptions['type'])] ?? 'mysql';
        try {
            $this->connection = new \PDO(
                $type.':'
                    .'dbname='.$dbOptions['database'].';'
                    .'host='.$dbOptions['servername'], 
                $dbOptions['username'], 
                $dbOptions['password'],
                $this->attributes);
        } catch (\PDOException $e) {
            $this->error['connectionDescription'] = $e->getMessage();
            $this->error['connectionCode'] = $e->getCode();
            throw $e;
        }
        return $this->connection;
    }
    
    public function __destruct() {
        if ($this->getError('connectionCode')===0) {
            $this->connection=null;
        }
    }
    
    public function setAttribute(int $attribute, int $value) {
        if ($this->connection->setAttribute($attribute, $value)) {
            parent::setAttribute($attribute, $value);
            return true;
        } else {
            return false;
        }
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
        $this->result = $this->connection->prepare($query);
        $params=[];
        foreach ($vars as $key=>$var) {
            if (is_array($var)) {
                switch (substr($var[1],0,1)) {
                    case 'b':
                        $type = \PDO::PARAM_BOOL;
                        break;
                    case 'i':
                        $type = \PDO::PARAM_INT;
                        break;
                    case 's':
                    default:
                        $type = \PDO::PARAM_STR;
                        break;
                }
                $var=$var[0];
            } else {
                if (is_bool($var)) {
                    $type = \PDO::PARAM_BOOL;
                } elseif (is_numeric($var)) {
                    $type = \PDO::PARAM_INT;
                } else {
                    $type = \PDO::PARAM_STR;
                }
            }
            $params[$key]=$var;
            $this->result->bindParam(':'.$key, $params[$key], $type);
        }
        $this->result->execute();
        //$this->affectedRows = $this->connection->rowCount();
    }
    
    public function exec(string $query, array $vars=[]) {
        $query = str_replace(PHP_EOL,' ',$query);
        $query = preg_replace('/[ ]+/',' ',$query);
        $this->query = $query;
        empty($vars) ? $this->execWithoutBind($query) : $this->execWithBind($query, $vars);
        return $this;
    }
    
    private function fetch($all=true) {
        if($this->resultType==='query') {
            $data = [];
            foreach ($this->result as $row) {
                $data[] = $row;
                if(!$all) {
                    break;
                }
            }
            return $data;
        } else {
            if ($all) {
                return $this->result->fetchAll(
                    $this->getAttribute(Database::ATTR_DEFAULT_FETCH_MODE));
            } else {
                return $this->result->fetch(
                    $this->getAttribute(Database::ATTR_DEFAULT_FETCH_MODE));
            }
        }
    }

    public function getAll($syncId=null) {
        $data = $this->fetch();
        $data = (!is_null($syncId)) ? $this->syncId($data,$syncId) : $data;
        return $data;
    }
    
    public function getArray() {
        $data = $this->fetch(false);
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
