<?php

namespace controllers;

/**
 * Base Controller
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Controller {
    protected function render($viewName,$properties=[]) {
        foreach ($properties as $var=>$value) {
            $$var = $value;
        }
        include ROOT_DIR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . '.php';
        
        if ( App::params("db.errorMode") === "debug" ) {
            // Debug Panel
            $debug['database'] = !is_null(App::db()) ? App::db()->statistics() : [];
            include App::getPath() . 'views' . DIRECTORY_SEPARATOR . 'debugPanel.php';
        }
    }
    
}
