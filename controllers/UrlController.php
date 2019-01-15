<?php

namespace controllers;

/**
 * UrlController - parse request and redirect to controller and action
 * 
 * for example, redirect to LessonController->beginAction():
 * www.example.com/lesson/begin   (require Pretty Url)
 * www.example.com?r=lesson/begin 
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class UrlController {
    
    public function __construct() {
        if(!empty($_GET['r'])) {
            $url = "/".filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS);
        } else { // Pretty URL
            $uri = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'].'?','?'));
            $url = str_replace($_SERVER['BASE'], '', $uri);
        }
        // Uppercase char with prefix _
        $url = str_replace(" ","",ucwords(str_replace("_", " ", $url)));
        $controllerName = ucfirst(substr($url,1,strpos($url."/","/",1)-1));
        if (!empty($controllerName)) {
            $controllerNameLength = strlen($controllerName)+2;
            $actionName = 'action' . ucfirst(substr($url,$controllerNameLength,strpos($url."/","/",$controllerNameLength)-1));
        } else {
            $controllerName=ucfirst(App::params('defaultController'));
            $actionName='index';
        }
        $controllerName = '\\controllers\\' . $controllerName . 'Controller';
        
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (@method_exists($controller,$actionName)) {
                $controller->$actionName();
            } elseif (@method_exists($controller,'index')) {
                \models\Log::add("errorsFile",__METHOD__, "Controller $controllerName has no action $actionName");
                $controller->index();
            } else {
                \models\Log::add("errorsFile",__METHOD__, "Controller $controllerName has no Index action");
            }
        } else {
            \models\Log::add("errorsFile",__METHOD__, "Controller $controllerName not found");
        }
    }

}
