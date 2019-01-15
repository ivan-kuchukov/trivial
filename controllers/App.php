<?php

namespace controllers;

/**
 * Application
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class App {
    private static $db = null;
    private static $params = null;
    private static $optionsFile = ROOT_DIR . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
    private static $uid = null;

    public static function params($opt='') {
        if (is_null(self::$params)) {
            self::$params = require self::$optionsFile;
        }
        $param = self::$params;
        foreach (preg_split('/\./', $opt, null, PREG_SPLIT_NO_EMPTY) as $value) {
            $param = $param[$value];
        }
        return $param;
    }
    
    public static function setParam(string $name,string $value) {
        $param = &self::$params;
        foreach (preg_split('/\./', $name, null, PREG_SPLIT_NO_EMPTY) as $val) {
            $param = &$param[$val];
        }
        $param = $value;
    } 

    public static function db($name = 'db') {
        return self::$db ?? self::createDB($name);
    }
    
    private static function createDb($name) {
        self::$db = \models\DatabaseFactory::create(self::params($name));
        if (self::$db->getError('connectionCode') !== 0) {
            echo "Can't connect to DB";
            exit(1);
        }
        return self::$db;
    }
    
    public static function getPath() {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }
    
    public static function getUID() {
        if (is_null(self::$uid)) {
            self::$uid = getmypid() . uniqid();
        }
        return self::$uid;
    }
   
}
