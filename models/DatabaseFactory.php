<?php

namespace trivial\models;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class DatabaseFactory {
    public static function create(array $dbOptions) {
        $db = false;
        if (strtolower($dbOptions['driver']) == 'pdo' ) {
            $db = new PDODatabase($dbOptions);
        }
        $type=strtolower($dbOptions['type']);
        if ( $type == 'mysql' || $type == 'mariadb' ) {
            $db = new MariaDatabase($dbOptions);
        }
        if ( $type == 'postgresql' ) {
            $db = new PostgreDatabase($dbOptions);
        }
        return $db;
    }
}
