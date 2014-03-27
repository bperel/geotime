<?php

namespace geotime;

use Purekid\Mongodm\MongoDB;

class Database {

    static $username;
    static $password;
    static $db;

    static $dbName = 'geotime';
    static $testDbName = 'geotime_test';

    static $connected = false;

    static function connect($dbName = null) {
        $conf = parse_ini_file('/home/geotime/config.ini');
        Database::$username = $conf['username'];
        Database::$password = $conf['password'];

        self::$db = is_null($dbName) ? Database::$dbName : $dbName;

        MongoDB::setConfigBlock('default', array(
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