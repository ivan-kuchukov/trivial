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
    private $query;
    private $result;
    private $affectedRows;

    public function __construct(array $dbOptions) {
        $options ="host=" . $dbOptions['servername'] . " " .
            "user=" . $dbOptions['username'] . " " .
            "password=" . $dbOptions['password'] . " " .
            "dbname=" . $dbOptions['database'];
        if (isset($dbOptions['persistentConnection']) && $dbOptions['persistentConnection']) {
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
    
    public function getQuery() {
        return $this->query;
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
 
    public function getStatus() {
        return $this->getError('code');
    }

    private function execWithoutBind(string $query) {
        $this->result = @pg_query($this->connection,$query);
        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);
        }
    }
    
    /**
     * Modify query and binding variables to MariaDB style
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
            $varNumber=1;
            foreach ($vars as $key=>$var) {
                $pattern='~".*?"(*SKIP)(*FAIL)|\'.*?\'(*SKIP)(*FAIL)|\:' . preg_quote($key, '~') . '\b~s';
                $matches=[];
                preg_match($pattern,$query,$matches,PREG_OFFSET_CAPTURE);
                $query=preg_replace($pattern, 
                    '\$'.(string)$varNumber.str_repeat(' ',strlen($key)-strlen((string)$varNumber))
                    , $query);
                $modifyVars[$varNumber]=$var;
                $varNumber++;
            }
            $vars=$modifyVars;
        }
        return true;
    }
    
    private function execWithBind(string &$query, array &$vars=[]) {
        if (!$this->nameBind($query,$vars)) {
            return false;
        }
        $values = [];
        foreach ($vars as $var) {
            $values[] = is_array($var) ? $var[0] : $var;
        }
        $result = @pg_prepare($this->connection,"",$query);
        if (!$result) {
            $this->result = false;
            return false;
        }
        $this->result = @pg_execute($this->connection,"",$values);
        if ($this->result) {
            $this->affectedRows = pg_affected_rows($this->result);
        }
    }

    public function exec(string $query, array $vars=[]) {
        $this->query = $query;
        empty($vars) 
            ? $this->execWithoutBind($query) 
            : $this->execWithBind($query, $vars);
        /*$this->queriesCount++;
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
            $text = $query . (!empty($vars) ? '. ' . json_encode($vars) : '' );
            Log::add("dbDebugFile",__METHOD__, $text);
        }*/
        return $this;
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
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_all($this->result);
        $data = (!is_null($syncId)) ? $this->syncId($data,$syncId) : $data;
        $data = $data ?: [];
        return $data;
    }
    
    public function getArray() {
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_all($this->result);
        $data = isset($data[0]) ? $data[0] : null;
        return $data;
    }
    
    public function getScalar() {
        if (!$this->result) {
            return false;
        }
        $data = pg_fetch_row($this->result);
        $data = isset($data[0]) ? $data[0] : null;
        return $data;
    }
    
    public function transaction($flags=null) {
        if (pg_query($this->connection,"BEGIN")) {
            return true;
        } else {
            $this->error['description'] = 'Transaction begin fail';
            $this->error['code'] = 'UE-1';
            return false;
        }
    }
    
    public function commit() {
        if (pg_query($this->connection,"COMMIT")) {
            return true;
        } else {
            $this->error['description'] = 'Transaction commit fail';
            $this->error['code'] = 'UE-2';
            return false;
        }
    }
    
    public function rollback() {
        if (pg_query($this->connection,"ROLLBACK")) {
            return true;
        } else {
            $this->error['description'] = 'Transaction rollback fail';
            $this->error['code'] = 'UE-3';
            return false;
        }
    }
 
}
