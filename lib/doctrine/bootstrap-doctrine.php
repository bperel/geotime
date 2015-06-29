<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$conf = parse_ini_file('/home/geotime/config.ini');
$username = $conf['username'];
$password = $conf['password'];

//Logger::configure("../geotime/logger.xml");

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/../geotime/new_models"), $isDevMode);

// database configuration parameters
$connectionParams = array(
    'dbname' => 'geotime',
    'user' => $username,
    'password' => $password,
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
    'server_version' => '15.1'
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
$entityManager = EntityManager::create($conn, $config);

$connectionParamsForTest = array_merge($connectionParams, array('dbname' => 'geotime_test'));
$connForTest = \Doctrine\DBAL\DriverManager::getConnection($connectionParamsForTest, $config);
$entityManagerForTest = EntityManager::create($connForTest, $config);