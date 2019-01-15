<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Log
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Log {
    private static $rows = [];

    /**
     * Add log string
     * @param type $type - type of log in configuration
     * @param type $method - initiator (usually __METHOD__)
     * @param type $text - text of log
     * @return type
     */
    public static function add($type,$method,$text) {
        if ( App::params("log.type") == 'file' ) {
            $file = ROOT_DIR . DIR_SEP . App::params("log.".$type);
            $log =  date("Y-m-d H:i:s") . " " . App::getUID() . " (".$method.") ".$text.PHP_EOL;
            self::$rows[$type] = isset(self::$rows[$type]) ? self::$rows[$type]++ : 1;
            return file_put_contents($file, $log, FILE_APPEND);
        }
    }
    
    /**
     * Statistics
     * @return type
     */
    public static function statistics(string $type=null) {
        return is_null($type) 
                ? self::$rows 
                : ( isset(self::$rows[$type]) ? self::$rows[$type] : null );
    }
}
