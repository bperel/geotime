<?php

set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/geotime')));
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/geotime/models')));
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/geotime/helpers')));
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/geotime/types')));

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;

use geotime\types\CalibrationPointType;

class DoctrineBootstrap {
    /**
     * @return \Doctrine\ORM\Configuration
     */
    static function getMetadataConfig() {
        $isDevMode = true;
        return Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../geotime/models", __DIR__."/../geotime/types"), $isDevMode);
    }

    private static function getEntityManagerFromConnectionParams($connectionParams) {
        $config = self::getMetadataConfig();
        $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        $em = EntityManager::create($conn, $config);

        Type::addType(CalibrationPointType::MYTYPE, CalibrationPointType::$CLASSNAME);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('CALIBRATION_POINT', CalibrationPointType::MYTYPE);

        return $em;
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
            'path' => __DIR__.'/geotime.sqlite',
            'driver' => 'pdo_sqlite'
        );
        return self::getEntityManagerFromConnectionParams($connectionParams);
    }
}
