<?php

namespace trivial\models;

/**
 * Description of Migration
 *
 * @author Ivan Kuchukov <ivan.kuchukov@gmail.com>
 */
class Migration {
    protected $db;
    
    public function __construct($db) {
        $this->db=$db;
    }
}
