<?php

namespace geotime\Test\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use geotime\helpers\ModelHelper;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
use PHPUnit_Extensions_Database_TestCase;

abstract class MariaDbTestHelper extends \PHPUnit_Extensions_Database_TestCase {

    /**
     * @var EntityManager
     */
    static public $em = null;

    /**
     * @var Connection
     */
    static public $conn;


    static $jsonSourceDir = 'test/phpunit/_data';

    public function setUp() {
        ModelHelper::getEm()->clear();

        $tool = new SchemaTool(ModelHelper::getEm());
        $classes = ModelHelper::getEm()->getMetaDataFactory()->getAllMetaData();

        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    public function tearDown() {
        $tool = new SchemaTool(ModelHelper::getEm());
        $classes = ModelHelper::getEm()->getMetaDataFactory()->getAllMetaData();

        $tool->dropSchema($classes);
    }

    /**
     * Returns the test database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
    }
}

include_once('ModelHelper.php');
ModelHelper::setEm($GLOBALS['entityManagerForTest']);
include_once('AbstractEntityHelper.php');