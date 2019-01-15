<?php

namespace models;

/**
 * Model for HtmlHelper
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class HtmlHelper {
    
    /**
     * Get array with pagination info
     * 
     * @param type $opt - array
     *              $opt['start'] - start element for current page
     *              $opt['size']  - size of elements on one page
     *              $opt['count'] - count of all elements
     * @param type $buttonsCount
     */
    public static function getPagination(array $opt,int $buttonsCount=9) {
        // Check and correct input
        $opt['count']=!empty($opt['count']) ? $opt['count'] : $opt['start']+$opt['size']*$buttonsCount;
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
            $pLeft=$buttonsCount-1-(floor($opt['count']/$opt['size'])-floor($opt['start']/$opt['size']));
        }
        $n=1;
        for ($i=0;$i<$buttonsCount;$i++) {
            $pos=$opt['start']+$opt['size']*($i-$pLeft);
            $p[floor($pos/$opt['size'])+1]=$pos;
        }
        // Button "Next"
        if (($opt['start']+$opt['size'])<=$opt['count']) {
            $p['Next']=$opt['start']+$opt['size'];
        }
        return $p;
    }
    
}
