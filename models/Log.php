<?php

namespace models;

/**
 * Log
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Log {
    /**
     * Add log string
     * @param type $type - type of log in configuration
     * @param type $method - initiator (usually __METHOD__)
     * @param type $text - text of log
     * @return type
     */
    public static function add($type,$method,$text) {
        if ( \controllers\App::params("log.type") == 'file' ) {
            $file = ROOT_DIR . DIRECTORY_SEPARATOR . \controllers\App::params("log.".$type);
            $log =  date("Y-m-d H:i:s") . " " . \controllers\App::getUID() . " (".$method.") ".$text.PHP_EOL;
            return file_put_contents($file, $log, FILE_APPEND);
        }
    }
}
