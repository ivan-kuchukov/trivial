<?php

namespace trivial\controllers;
use trivial\models\Log;
use trivial\models\Translator;

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
    /**
     * @var string - path to 404 error page
     */
    private static $error404Page = ROOT_DIR . DIR_SEP . 'views' . DIR_SEP . 'error404.php';

    /**
     * UrlController constructor. Parse URL and run chosen controller/method
     */
    public function __construct() {
        if(!empty(App::post('uid'))) {
            App::setUID(App::post('uid'));
        }
        if(!empty(App::get('lang')) || !empty(App::post('lang'))) {
            Translator::setLanguage(App::get('lang') ?? App::post('lang'));
        }
        if(!empty(App::get('r'))) {
            $url = "/".App::get('r');
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
            $actionName = ($actionName!='action') ? $actionName : 'index';
        } else {
            $controllerName=ucfirst(App::params('defaultController'));
            $actionName='index';
        }
        $controllerName = 'app\\controllers\\' . $controllerName . 'Controller';
        
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (@method_exists($controller,$actionName)) {
                $controller->$actionName();
            } elseif (@method_exists($controller,'index')) {
                Log::add("errorsFile",__METHOD__, "Controller $controllerName has no action $actionName");
                $controller->index();
            } else {
                Log::add("errorsFile",__METHOD__, "Controller $controllerName has no Index action");
            }
        } else {
            http_response_code(404);
            if (file_exists(self::$error404Page)) {
                include self::$error404Page;
            }
            die();
        }
    }

}
