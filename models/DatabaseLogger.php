<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Log for Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class DatabaseLogger extends Log {
    // object of database for logging
    private $db; 
    // methods for debug and errors
    private $logResult = ['getAll','getArray','getScalar'];
    private $logQuery = ['exec'];
    // count of queries and error queries
    private $queriesCount = 0;
    private $errorQueriesCount = 0;
    // Set error mode for queries (debug, log, ignore)
    private $errorLog;

    /**
     * 
     * @param type $db - object of database for logging
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function beforeMethod($name,$arguments,$result=null) {
        if($name=='statistics') {
            $stat = [
                'errorQueriesCount' => $this->errorQueriesCount,
                'queriesCount' => $this->queriesCount,
            ];
            return (isset($arguments[0]) && isset($stat[$arguments[0]]))
                ? $stat[$arguments[0]] : $stat;
        } elseif($name=='setErrorMode') {
            $this->errorLog = $arguments[0];
        } elseif($name=='getErrorMode') {
            return $this->errorLog;
        } else {
            return true;
        }
    }

    public function afterMethod($name,$arguments,$result=null) {
        if(in_array($name,$this->logQuery)) {
            $this->logQuery($name,$arguments,$result);
        }
        if(in_array($name,$this->logResult)) {
            $this->logResult($name,$arguments,$result);
        }
        return true;
    }
    
    private function logQuery($name,$arguments,$result) {
        $errorLog = App::params('db.errorLog');
        $queriesLog = App::params('db.queriesLog');
        $query = $this->db->getQuery();
        $method = get_class($this->db) . '->' . $name;
        $this->queriesCount++;
        if ( !$this->db->getStatus() ) {
            $this->errorQueriesCount++;
            if ($errorLog == "display") {
                echo 'Error in DB query: [' . $this->db->getError('code') . '] '
                    . $this->db->getError('description') . PHP_EOL;
            }
            if ($errorLog == "log" || $errorLog == "display") {
                self::add("dbDebugFile",$method, $query . ". ERROR: " . $this->db->getError('description'));
                self::add("errorsFile",$method, $query . ". ERROR: " . $this->db->getError('description'));
            }
        } else if ( $queriesLog ) {
            $text = $query . (!empty($vars) ? '. ' . json_encode($vars) : '' );
            self::add("dbDebugFile",$method, $text);
        }
    }
    
    private function logResult($name,$arguments,$result) {
        if (App::params('db.queriesLog')) {
            if (is_array($result)) {
                if (isset($result[0])) {
                    $text = count($result)>0 
                        ? count($result) . ' rows: ' . json_encode(current($result)) . ',...' 
                        : '[]';
                } else {
                    $text = json_encode($result);
                }
            } else {
                $text = is_null($data) ? 'null' : $data;
            }
            $method = get_class($this->db) . '->' . $name;
            self::add("dbDebugFile",$method, $text);
        }
        
    }
    
}
