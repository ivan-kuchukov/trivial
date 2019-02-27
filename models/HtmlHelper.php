<?php

namespace trivial\models;
use trivial\controllers\App;

/**
 * Model for HtmlHelper
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class HtmlHelper {
    
    private static $js = [];
    private static $css = [];

    /**
     * jsRequire
     * @param array $jsRequire
     */
    public static function jsRequire(array $jsRequire=[]) {
        self::$js = array_merge(self::$js,$jsRequire);
    }
    
    /**
     * jsBlock
     * @param array $jsRequire
     */
    public static function jsBlock(array $jsRequire=[]) {
        self::jsRequire($jsRequire);
        foreach (array_unique(self::$js) as $jsRequireType) {
            echo App::params("includedJavascripts.".$jsRequireType);
        }
    }
    
    /**
     * cssRequire
     * @param array $cssRequire
     */
    public static function cssRequire(array $cssRequire=[]) {
        self::$css = array_merge(self::$css,$cssRequire);
    }
    
    /**
     * cssBlock
     * @param array $cssRequire
     */
    public static function cssBlock(array $cssRequire=[]) {
        self::cssRequire($cssRequire);
        foreach (array_unique(self::$css) as $cssRequireType) {
            echo App::params("includedCSS.".$cssRequireType);
        }
    }

    /**
     * Get array with pagination info
     * 
     * @param type $opt - array
     *              $opt['start'] - start element for current page
     *              $opt['size']  - size of elements on one page
     *              $opt['count'] - count of all elements
     * @param type $buttonsCount
     */
    public static function getPagination(array $opt, $buttonsCount=9) {
        $p=[];
        // Check and correct input
        $opt['count']=!is_null($opt['count']) ? $opt['count'] : $opt['start']+$opt['size']*$buttonsCount;
        $buttonsCount=(ceil($opt['count']/$opt['size'])<$buttonsCount) ? ceil($opt['count']/$opt['size']) : $buttonsCount;
        $opt['size']=((int)$opt['size']>0) ? $opt['size'] : 10;
        // Button "Prev"
        if (($opt['start']-$opt['size'])>0) {
            $p['Prev']=$opt['start']-$opt['size'];
        }
        $pLeft=floor(($buttonsCount-1)/2);
        $pRight=floor(($buttonsCount-1)/2) + (($buttonsCount-1) % 2);
        if ( ($opt['start']-$opt['size']*$pLeft)<1 ) {
            $pLeft=floor($opt['start']/$opt['size']);
        } elseif ( ($opt['start']+$opt['size']*$pRight)>$opt['count'] ) {
            $pLeft=$buttonsCount-1-(ceil($opt['count']/$opt['size'])-ceil($opt['start']/$opt['size']));
        }
        for ($i=0;$i<$buttonsCount;$i++) {
            $pos=$opt['start']+$opt['size']*($i-$pLeft);
            $p[floor($pos/$opt['size'])+1]=$pos;
        }
        // Button "Next"
        if (($opt['start']+$opt['size'])<=$opt['count']) {
            $p['Next']=$opt['start']+$opt['size'];
        }
        return count($p)==1 ? [] : $p;
    }
    
}
