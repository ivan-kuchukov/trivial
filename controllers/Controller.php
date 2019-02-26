<?php

namespace trivial\controllers;

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
        include ROOT_DIR . DIR_SEP . 'views' . DIR_SEP . $viewName . '.php';
        echo "<script>uid='".App::getUID()."'</script>";
        if ( App::params("debug") ) {
            // Debug Panel
            $debug['database'] = App::db()->statistics();
            include App::getPath() . 'views' . DIR_SEP . 'debugPanel.php';
        }
    }
    
}
