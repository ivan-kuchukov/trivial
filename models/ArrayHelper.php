<?php

namespace trivial\models;

/**
 * Model for ArrayHelper
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class ArrayHelper {
    
    /**
     * getType - get type of array
     * 
     * @param type $array
     * @return string - 'associative' (only string keys),
     *                  'sequential' (only not string keys),
     *                  'mixed' (string and not string keys),
     *                  null (array has no elements)
     */
    public static function getType(array $array) {
        $type = null;
        foreach ($array as $key=>$value) {
            if (is_string($key)) {
                $type = (is_null($type) || $type=='associative') ? 'associative' : 'mixed';
            } else {
                $type = (is_null($type) || $type=='sequential') ? 'sequential' : 'mixed';
            }
        }
        return $type;
    }
}
