<?php

namespace geotime;

class Database {

    /**
     * @var \MongoClient
     */
    static $m;

    /**
     * @var \MongoDB
     */
    static $db;

    static function connect() {
        self::$m = new \MongoClient();
        self::changeDb("geotime");
    }

    static function changeDb($name) {
        self::$db = self::$m->selectDB($name);
    }
}

if (!isset(Database::$db)) {
    Database::connect();
}