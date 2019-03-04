<?php

namespace trivial\models;

/**
 * Universal model of Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
abstract class Database implements DatabaseInterface {
    protected $connection;
    protected $result;
    protected $affectedRows;
    /**
     * Text of SQL query
     */
    protected $query;
    /**
     * Database attributes
     */
    protected $attributes = [
        self::ATTR_ERRMODE=>self::ERRMODE_WARNING,
        self::ATTR_DEFAULT_FETCH_MODE=>self::FETCH_ASSOC,
    ];
    /**
     * ATTR_ERRMODE - Set mode errors showing:
     *   ERRMODE_SILENT    - don't show any errors
     *   ERRMODE_WARNING   - show warning
     *   ERRMODE_EXCEPTION - throw exception
     */
    const ATTR_ERRMODE = 3;
    const ERRMODE_SILENT = 0;
    const ERRMODE_WARNING = 1;
    const ERRMODE_EXCEPTION = 2;
    /**
     * ATTR_DEFAULT_FETCH_MODE - Controls how the next row will be returned to the caller
     *   FETCH_ASSOC - returns an array indexed by column name as returned in your result set
     *   FETCH_NUM - returns an array indexed by column number as returned in your result set, starting at column 0
     *   FETCH_BOTH - returns an array indexed by both column name and 0-indexed column number as returned in your result set
     */
    const ATTR_DEFAULT_FETCH_MODE = 19;
    const FETCH_ASSOC = 2;
    const FETCH_NUM = 3;
    const FETCH_BOTH = 4;
    
    /**
     * 
     * @param type $dbOptions
     */
    public function __construct($dbOptions) {
        if(isset($dbOptions['attributes'])) {
            $this->attributes = $dbOptions['attributes'] + $this->attributes;
        };
    }

    /**
     * Set Database attribute
     * @param int $attribute
     * @param int $value
     */
    public function setAttribute(int $attribute , int $value) {
        $this->attributes[$attribute] = $value;
    }
    
    /**
     * Return of Database attribute
     * @param int $attribute
     * @return type
     */
    public function getAttribute(int $attribute) {
        return $this->attributes[$attribute];
    }
    
    /**
     * Return SQL query
     * @return type
     */
    public function getQuery() {
        return $this->query;
    }
    
    /**
     * Modify array: change keys to value of field 
     * @param type $data   - array for modify
     * @param type $syncId - field for replace of keys
     * @return type        - modified array
     */
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
    
    /**
     * Error handler
     */
    protected function errorHandler(string $message, $code,string $type) {
        $attrErrMode = $this->getAttribute(Database::ATTR_ERRMODE);
        $errMessage = "";
        if ($type=='connection') {
            // If error while connect - only exception
            $attrErrMode = Database::ERRMODE_EXCEPTION;
        } else {
            $errMessage .= "{$type} :";
        }
        if (!is_null($code)) {
            $errMessage .= " [{$code}]";
        }
        $errMessage .= " {$message}";
        if ($attrErrMode == Database::ERRMODE_EXCEPTION) {
            throw new \Exception($errMessage, $code);
        } elseif ($attrErrMode == Database::ERRMODE_WARNING) {
            trigger_error($errMessage, E_USER_WARNING);
        }
    }
    
    /**
     * Get affected rows in last SQL query
     * @return type
     */
    public function getAffectedRows() {
        return $this->affectedRows;
    }
    
    /**
     * Access to connection object for manual manipulation
     * @return type
     */
    public function connection() {
        return $this->connection;
    }
    
    /**
     * Access to result object for manual manipulation
     * @return type
     */
    public function result() {
        return $this->result;
    }
    
}
