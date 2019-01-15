<?php

namespace trivial\controllers;
use trivial\controllers\App;

/**
 * LogController
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class LogController extends Controller {
    
    public function actionDatabaseQueries() {
        echo "Database Debug Log:";
        $this->showLog('dbDebug.log');
    }
    
    public function actionErrors() {
        echo "Error Log:";
        $this->showLog('errors.log');
    }
    
    private function showLog($logFilename) {
        $logUID = filter_input(INPUT_GET, 'uid', FILTER_SANITIZE_SPECIAL_CHARS);
        $handle = @fopen(ROOT_DIR . DIR_SEP . 'runtime' . DIR_SEP . $logFilename, "r");
        if ($handle) {
            echo "<pre>";
            while (($buffer = fgets($handle, 4096)) !== false) {
                if (is_null($logUID) || strpos($buffer,$logUID)) {
                    $buffer = preg_replace('/^[^ ]* /', '', $buffer);
                    if (!is_null($logUID)) {
                        $buffer = preg_replace('/'.$logUID.' /', '', $buffer);
                    }
                    echo $buffer;
                }
            }
            echo "</pre>";
            fclose($handle);
        }
    }
    
}
