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

    /* Tests */
/*
    public function testImportFromJson() {
        CriteriaGroup::drop();

        $this->assertEquals(0, CriteriaGroup::count());
        $nbImportedObjects = CriteriaGroup::importFromJson(self::$jsonSourceDir.'/criteriaGroups.json');
        $this->assertEquals(2, CriteriaGroup::count());
        $this->assertEquals(2, $nbImportedObjects);
    }

    public function testImportFromJsonInvalidFileName() {
        try {
            CriteriaGroup::importFromJson(self::$jsonSourceDir.'/criteriaGroups-1-.json');
            $this->fail();
        }
        catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid file name for JSON import', $e->getMessage());
        }
    }

    public function testImportFromJsonInexistentFile() {
        try {
            CriteriaGroup::importFromJson(self::$jsonSourceDir . '/criteriaGroups2.json');
            $this->fail();
        }
        catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('Error on JSON import', $e->getMessage());
        }
    }
*/
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