<?php

namespace geotime;

class Database {

    static $username;
    static $password;
    static $db;

    static $connected = false;

    static function connect($dbName) {
        self::$db = $dbName;

        \Purekid\Mongodm\MongoDB::setConfigBlock('default', array(
            'connection' => array(
                'hostnames' => 'localhost',
                'database'  => self::$db,
                'username'  => self::$username,
                'password'  => self::$password,
                'options'   => array()
            )
        ));
        self::$connected = true;
    }
}

if (!Database::$connected) {
    $conf = parse_ini_file('/home/geotime/config.ini');
    Database::$username = $conf['username'];
    Database::$password = $conf['password'];

    $dbName = isset($dbName) ? $dbName : 'geotime';
    Database::connect($dbName);
}