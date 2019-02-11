<?php

namespace trivial\models;

/**
 * Model for Translator
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Translator {
    private static $dic = []; //dictionary
    private static $dicDir = ROOT_DIR . DIR_SEP . 'translate' . DIR_SEP;
    private static $translFile = 't.php';
    private static $lang = null;
    private static $type = 'main';

    public static function setLanguage(string $lang) {
        self::$lang = $lang;
    }
    
    public static function setType(string $type) {
        self::$type = $type;
    }

    public static function translate(string $text, string $type=null) {
        $type = $type ?? self::$type;
        self::loadDictionary();
        return self::$dic[self::$lang][$type][$text] ?? $text;
    }
    
    private static function loadDictionary() {
        if (!isset(self::$dic[self::$lang]) && file_exists(self::$dicDir.self::$lang.DIR_SEP.self::$translFile)) {
            self::$dic[self::$lang] = include self::$dicDir.self::$lang.DIR_SEP.self::$translFile;
        }
    }
}
