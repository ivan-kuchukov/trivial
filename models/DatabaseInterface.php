<?php

namespace trivial\models;

/**
 * DatabaseInterface
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
interface DatabaseInterface {
    
    /**
     * Connect to DB
     * @param array $dbOptions
     * persistentConnection - use for persistent connect
     */
    public function __construct(array $dbOptions);
    
    /**
     * Close DB connection
     */
    public function __destruct();

    /**
     * Get errors codes and describes array. 
     * @param type $key - if is not null, get key from array
     */
    public function getError($key);
    /**
     * Set error mode for queries
     * @param type $errorMode - debug, log, ignore
     */
    public function setErrorMode($errorMode);
    /**
     * Get error mode (see setErrorMode())
     */
    public function getErrorMode();
    /** 
     * Get result of query executing. Return true (success) or false (fail)
     */
    public function getResult();
    /**
     * Execute query
     * @param string $query - SQL-query
     * @param array $vars - variables for binding
     * return $this
     */
    public function exec(string $query, array $vars=[]);
    /**
     * Get id for inserted row after query execute
     */
//public function getInsertId(); 
    /**
     * Fetch and get all rows as associate array
     * @param type $syncId - if is not null, change key for array
     */
    public function getAll($syncId=null);
    /**
     * Fetch and get result as associate array in one row
     */
    public function getArray();
    /**
     * Fetch and get result as scalar
     */
    public function getScalar();
    /**
     * Begin transaction
     * @param type $flags - type of transaction
     */
    public function transaction($flags);
    /**
     * Commit transaction
     */
    public function commit();
    /**
     * Rollback transaction
     */
    public function rollback();
}
