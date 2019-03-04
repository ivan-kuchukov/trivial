<?php

namespace trivial\models;

/**
 * Model for Template Translating
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Translator {
    /**
     * @var array - dictionary. storage for translating
     */
    private static $dic = [];
    /**
     * @var string - path to dictionaries
     */
    private static $dicDir = ROOT_DIR . DIR_SEP . 'translate' . DIR_SEP;
    /**
     * @var string - file with translations for load to $dic
     */
    private static $translFile = 't.php';
    /**
     * @var null - language for translation
     */
    private static $lang = null;
    /**
     * @var string - default translation type, dictionary can have more one variant of translation
     */
    private static $type = 'main';

    /**
     * Change translation language
     * @param string $lang
     */
    public static function setLanguage(string $lang) {
        self::$lang = $lang;
    }

    /**
     * Change type of translation
     * @param string $type
     */
    public static function setType(string $type) {
        self::$type = $type;
    }

    /**
     * Translate input string
     * @param string $text - input text for translate
     * @param string|null $type - variant of translation
     * @return string - output text after translation
     */
    public static function translate(string $text, string $type=null) {
        $type = $type ?? self::$type;
        self::loadDictionary();
        return self::$dic[self::$lang][$type][$text] ?? $text;
    }

    /**
     * Load Translation Dictionary for chosen language
     */
    private static function loadDictionary() {
        if (!isset(self::$dic[self::$lang])
            && file_exists(self::$dicDir.self::$lang.DIR_SEP.self::$translFile)) {
            self::$dic[self::$lang] = include self::$dicDir.self::$lang.DIR_SEP.self::$translFile;
        }
    }
}
