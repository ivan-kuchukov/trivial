<?php

namespace trivial\controllers;
use trivial\models\DatabaseFactory;
use trivial\controllers\UrlController;

/**
 * Application
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class App {
    /**
     * @var null - database object
     */
    private static $db = null;
    /**
     * @var null - parameters from configuration file
     */
    private static $params = null;
    /**
     * @var string - path to configuration file
     */
    private static $optionsFile = ROOT_DIR . DIR_SEP . 'config' . DIR_SEP . 'config.php';
    /**
     * @var null - UID current session
     */
    private static $uid = null;
    /**
     * @var null - storage for data from php://input
     */
    private static $input = null;

    /**
     * Initialization new application
     */
    public static function init() {
        new UrlController();
    }

    /**
     * Get configuration parameters of application
     * @param string $opt
     * @return mixed|null
     */
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

    /**
     * Set new configuration parameter of application
     * @param string $name
     * @param string $value
     */
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

    /**
     * Return database for work
     * @param string $name
     * @return bool|object|null
     */
    public static function db($name = 'db') {
        return self::$db ?? self::createDB($name);
    }

    /**
     * Create object of new Database
     * @param $name
     * @return bool|object|null
     */
    private static function createDb($name) {
        self::$db = DatabaseFactory::create(self::params($name));
        return self::$db;
    }

    public static function getPath() {
        return __DIR__ . DIR_SEP . '..' . DIR_SEP;
    }

    /**
     * Get UID of current session
     * @return string|null
     */
    public static function getUID() {
        if (is_null(self::$uid)) {
            self::$uid = getmypid() . uniqid();
        }
        return self::$uid;
    }

    /**
     * Set UID of current session
     * @param string $uid
     */
    public static function setUID(string $uid) {
        self::$uid = $uid;
    }
    
    private static function readInput($param,$filter) {
        if(is_null(self::$input)) {
            self::$input = json_decode(file_get_contents('php://input'), true);
        }
        return isset(self::$input[$param]) 
            ? filter_var(self::$input[$param],$filter) 
            : null;
    }

    /**
     * Get data from GET-request
     * @param $param
     * @param int $filter
     * @return mixed|null
     */
    public static function get($param,$filter=FILTER_SANITIZE_SPECIAL_CHARS) {
        return isset($param) 
            ? filter_input(INPUT_GET, $param, $filter)
            : null;
    }

    /**
     * Get data from POST-request (including php://input)
     * @param $param
     * @param int $filter
     * @return mixed|null
     */
    public static function post($param,$filter=FILTER_SANITIZE_SPECIAL_CHARS) {
        return (isset($_POST[$param])) 
            ? filter_input(INPUT_POST, $param, $filter)
            : self::readInput($param,$filter);
    }
   
}
