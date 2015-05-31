<?php

namespace geotime;

use Purekid\Mongodm\MongoDB;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class Database {

    /** @var \Logger */
    static $log;

    static $username;
    static $password;
    static $db;

    static $dbName = 'geotime';
    static $testDbName = 'geotime_test';

    static $connected = false;

    static function connect($dbName = null) {
        $conf = parse_ini_file('/home/geotime/config.ini');
        self::$username = $conf['username'];
        self::$password = $conf['password'];

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
     * @return int|null Number of imported objects, or NULL on error
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
                $nbImportedObjects = intval($match[1]);
                self::$log->info("Successfully imported $nbImportedObjects objects into $collectionName");
                return $nbImportedObjects;
            }
            else {
                self::$log->error("An error occured while importing data into $collectionName");
                throw new \InvalidArgumentException('Error on JSON import : ' . $fileName . "\n");
            }
        }
    }
}

Database::$log = Logger::getLogger("main");
