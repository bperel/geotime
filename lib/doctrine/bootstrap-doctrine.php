<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DoctrineBootstrap {
    /**
     * @return \Doctrine\ORM\Configuration
     */
    static function getMetadataConfig() {
        $isDevMode = true;
        return Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../geotime/new_models"), $isDevMode);
    }

    private static function getEntityManagerFromConnectionParams($connectionParams) {
        $config = self::getMetadataConfig();
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        return EntityManager::create($conn, $config);
    }

    static function getEntityManager() {
        $conf = parse_ini_file('/home/geotime/config.ini');
        $username = $conf['username'];
        $password = $conf['password'];

        $connectionParams = array(
            'dbname' => 'geotime',
            'user' => $username,
            'password' => $password,
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
            'server_version' => '15.1'
        );

        return self::getEntityManagerFromConnectionParams($connectionParams);

    }

    static function getTestEntityManager() {
        $connectionParams = array(
            'user' => 'test',
            'password' => 'test',
            'memory' => true,
            'driver' => 'pdo_sqlite'
        );
        return self::getEntityManagerFromConnectionParams($connectionParams);
    }
}