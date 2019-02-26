<?php

namespace trivial\models;

/**
 * Method wrapper for methods from class A by methods from class B
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class ProxyMethodWrapper {
    private $mainClass;
    private $wrapperClass;

    /**
     * 
     * @param object $mainClass           - object of main class
     * @param object|string $wrapperClass - object of class or name of static class
     */
    public function __construct(object $mainClass, $wrapperClass) {
        $this->mainClass = $mainClass;
        $this->wrapperClass = $wrapperClass;
    }

    public function __call($name, $arguments) {
        $result = $this->wrapperMethod('beforeMethod',$name, $arguments);
        if ($result===true || $result===false) {
            $result = $this->mainClass->$name(...$arguments);
        }
        $this->wrapperMethod('afterMethod',$name, $arguments, $result);
        return is_object($result) ? $this : $result;
    }
    
    private function wrapperMethod($wrapperMethod,$name,$arguments,$result=null) {
        if (method_exists($this->wrapperClass,$wrapperMethod))  {
            if(is_object($this->wrapperClass)) {
                return $this->wrapperClass->$wrapperMethod($name,$arguments,$result);
            } elseif(is_string($this->wrapperClass)) {
                return $this->wrapperClass::$wrapperMethod($name,$arguments,$result);
            }
        }
        return false;
    }
}
