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

    /**
     * @param $fileName string : JSON file to import
     * @param $collectionName string : Name of the collection to create
     * @return int|null Number of imported object, or NULL on error
     */
    public static function importFromJson($fileName, $collectionName)
    {
        if (!preg_match('#^[./_a-zA-Z0-9]+$#', $fileName)) {
            throw new \InvalidArgumentException('Invalid file name for JSON import : ' . $fileName . "\n");
        } else {
            $command = 'mongoimport --jsonArray -u ' . Database::$username . ' -p ' . Database::$password . ' -d ' . Database::$db . ' -c ' . $collectionName . ' ' . (getcwd()) . "/" . $fileName . ' 2>&1';
            $status = shell_exec($command);
            preg_match('#imported ([\d]+) objects$#', $status, $match);
            if ($match) {
                return intval($match[1]);
            }
            else {
                throw new \InvalidArgumentException('Error on JSON import : ' . $fileName . "\n");
            }
        }
    }
}