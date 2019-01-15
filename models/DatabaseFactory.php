<?php

namespace models;

/**
 * Model for work with Database
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class DatabaseFactory {
    public static function create(array $dbOptions) {
        if ($dbOptions['type'] == 'MariaDB' ) {
            $db = new MariaDatabase($dbOptions);
            return $db;
        }
    }
}
