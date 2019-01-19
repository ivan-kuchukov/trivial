<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class PostgreDatabase implements DatabaseInterface {
    private $connection;
    private $result;
    private $affectedRows;
    private $errorLog;
    private $queriesLog;
    private $queriesCount = 0;
    private $errorQueriesCount = 0;

    public function __construct(array $dbOptions) {
        $this->errorLog = App::params('db.errorLog');
        $this->queriesLog = App::params('db.queriesLog');
        $options ="host=" . $dbOptions['servername'] . " " .
            "user=" . $dbOptions['username'] . " " .
            "password=" . $dbOptions['password'] . " " .
            "dbname=" . $dbOptions['database'];
        if ($dbOptions['persistentConnection']) {
            @$this->connection = pg_pconnect($options);
        } else {
            @$this->connection = pg_connect($options);
        }
        return $this->connection;
    }
    
    public function __destruct() {
        if (pg_connection_status($this->connection)===PGSQL_CONNECTION_OK) {
            pg_close($this->connection);
        }
    }
    
    public function getError($key=null) {
        $error=[
            'connectionDescription'=>pg_connection_status($this->connection)===PGSQL_CONNECTION_OK ? 'Ok' : 'Fail',
            'connectionCode'=>pg_connection_status($this->connection),
            'description'=>null,
            'code'=>null,
        ];
        if ($error['connectionCode']===PGSQL_CONNECTION_OK) {
            $error['description']=pg_last_error($this->connection);
            $error['code']=pg_last_error($this->connection) ? false : true;
        }
        return ($key===null) ? $error : $error[$key];
    }
    
    public function setErrorMode($errorLog) {
        $this->errorLog = $errorLog;
    }
    
    public function getErrorMode() {
        return $this->errorLog;
    }
    
    public function getResult() {
        return $this->result;
    }

    private function execWithoutBind(string $query) {
        $this->result = @pg_query($this->connection,$query);
        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);
        }
    }
    
    private function execWithBind(string $query, array $vars=[]) {
        $values = [];
        foreach ($vars as $var) {
            $values[] = is_array($var) ? $var[0] : $var;
        }
        $result = pg_prepare($this->connection,"",$query);
        if (!$result) {
            return $result;
        }
        $this->result = @pg_execute($this->connection,"",$values);
        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);
        }
    }

    public function exec(string $query, array $vars=[]) {
        empty($vars) ? $this->execWithoutBind($query) : $this->execWithBind($query, $vars);
        $this->queriesCount++;
        if ( ! $this->result ) {
            $this->errorQueriesCount++;
            if ($this->errorLog=="display") {
                echo 'Error in DB query: ' . $this->getError('description') . PHP_EOL;
            }
            if ($this->errorLog=="log" || $this->errorLog=="display") {
                Log::add("dbDebugFile",__METHOD__, $query . ". ERROR: " . $this->getError('description') );
                Log::add("errorsFile",__METHOD__, $query . ". ERROR: " . $this->getError('description') );
            }
        } else if ( $this->queriesLog ) {
            $text = $query . (!empty($vars) ? PHP_EOL . ' ' . json_encode($vars) : '' );
            Log::add("dbDebugFile",__METHOD__, $text);
        }
        return $this;
    }
    
    /*public function getInsertId() {
        return pg_last_oid($this->result);
    }*/
    
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
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_all($this->result);
        $data = (!is_null($syncId)) ? $this->syncId($data,$syncId) : $data;
        $data = $data ?: [];
        $text = ( count($data)>0 ? count($data) . ' rows: ' . json_encode(current($data)) . ',...' : '[]');
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, $text);
        }
        return $data;
    }
    
    public function getArray() {
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_all($this->result);
        $data = isset($data[0]) ? $data[0] : null;
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, json_encode($data));
        }
        return $data;
    }
    
    public function getScalar() {
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_row($this->result);
        $data = isset($data[0]) ? $data[0] : null;
        if ($this->queriesLog) {
            Log::add("dbDebugFile",__METHOD__, (is_null($data) ? 'null' : $data));
        }
        return $data;
    }
    
    public function transaction($flags=null) {
        if (pg_query($this->connection,"BEGIN")) {
            return true;
        } else {
            Log::add("errorsFile",__METHOD__, 'Transaction begin fail');
            return false;
        }
    }
    
    public function commit() {
        if (pg_query($this->connection,"COMMIT")) {
            return true;
        } else {
            Log::add("errorsFile",__METHOD__, 'Transaction commit fail');
            return false;
        }
    }
    
    public function rollback() {
        if (pg_query($this->connection,"ROLLBACK")) {
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
