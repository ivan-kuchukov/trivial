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
    // set error mode for queries (debug, log, ignore)
    public $errorLog;
    // replaced methods
    private $replacedMethods = ['statistics','setErrorMode','getErrorMode'];

    /**
     * 
     * @param type $db - object of database for logging
     */
    public function __construct($db) {
        $this->db = $db;
    }

    public function statistics($arguments=null) {
        $stat = [
            'errorQueriesCount' => $this->errorQueriesCount,
            'queriesCount' => $this->queriesCount,
        ];
        return (isset($arguments[0]) && isset($stat[$arguments[0]])) ? $stat[$arguments[0]] : $stat;
    }

    public function logQuery($name,$arguments,$result) {
        $errorLog = App::params('db.errorLog');
        $queriesLog = App::params('db.queriesLog');
        $query = $this->db->getQuery();
        $method = get_parent_class($this->db) . '->' . $name;
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
            $text = $query . (!empty($arguments) ? '. ' . json_encode($arguments) : '' );
            self::add("dbDebugFile",$method, $text);
        }
    }
    
    public function logResult($name,$arguments,$result) {
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
                $text = is_null($result) ? 'null' : $result;
            }
            $method = get_parent_class($this->db) . '->' . $name;
            self::add("dbDebugFile",$method, $text);
        }
        
    }
    
}
