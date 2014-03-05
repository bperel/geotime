<?php

namespace geotime;

class Database {

    /**
     * @var \MongoDB
     */
    static $db;

    static function connect() {
        $m = new \MongoClient();
        self::$db = $m->selectDB("geotime");
    }
}

if (isset(Database::$db)) {
    Database::connect();
}