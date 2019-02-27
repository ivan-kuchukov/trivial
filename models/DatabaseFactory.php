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
            $db = new PDODatabaseWithLog($dbOptions);
        } elseif ( $type == 'mysql' || $type == 'mariadb' ) {
            $db = new MySQLDatabaseWithLog($dbOptions);
        } elseif ( $type == 'postgresql' ) {
            $db = new PostgreSQLDatabaseWithLog($dbOptions);
        } else {
            return false;
        }
        $db->logger = new DatabaseLogger($db);
        return $db;
    }
}
