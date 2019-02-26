<?php

namespace trivial\models;

/**
 * Factory for create model for Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class DatabaseFactory {
    public static function create(array $dbOptions) {
        $db = false;
        $type = strtolower($dbOptions['type']);
        $driver = strtolower($dbOptions['driver']);
        if ($driver == 'pdo' ) {
            $db = new PDODatabase($dbOptions);
        } elseif ( $type == 'mysql' || $type == 'mariadb' ) {
            $db = new MySQLDatabase($dbOptions);
        } elseif ( $type == 'postgresql' ) {
            $db = new PostgreSQLDatabase($dbOptions);
        } else {
            return false;
        }
        return new ProxyMethodWrapper($db,new DatabaseLogger($db));
    }
}
