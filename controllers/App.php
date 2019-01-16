<?php

namespace trivial\controllers;
use trivial\models\DatabaseFactory;

/**
 * Application
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class App {
    private static $db = null;
    private static $params = null;
    private static $optionsFile = ROOT_DIR . DIR_SEP . 'config' . DIR_SEP . 'config.php';
    private static $uid = null;

    public static function params($opt='') {
        if (is_null(self::$params)) {
            self::loadParams();
        }
        $param = self::$params;
        foreach (preg_split('/\./', $opt, null, PREG_SPLIT_NO_EMPTY) as $value) {
            $param = $param[$value];
        }
        return $param;
    }
    
    public static function setParam(string $name,string $value) {
        if (is_null(self::$params)) {
            self::loadParams();
        }
        $param = &self::$params;
        foreach (preg_split('/\./', $name, null, PREG_SPLIT_NO_EMPTY) as $val) {
            $param = &$param[$val];
        }
        $param = $value;
    }
    
    private static function loadParams() {
        self::$params = require self::$optionsFile;
    }

    public static function db($name = 'db') {
        return self::$db ?? self::createDB($name);
    }
    
    private static function createDb($name) {
        self::$db = DatabaseFactory::create(self::params($name));
        if (self::$db->getError('connectionCode') !== 0) {
            echo "Can't connect to DB";
            exit(1);
        }
        return self::$db;
    }
    
    public static function getPath() {
        return __DIR__ . DIR_SEP . '..' . DIR_SEP;
    }
    
    public static function getUID() {
        if (is_null(self::$uid)) {
            self::$uid = getmypid() . uniqid();
        }
        return self::$uid;
    }
   
}
