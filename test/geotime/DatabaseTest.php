<?php

namespace geotime\Test;

use geotime\Database;
use geotime\models\CriteriaGroup;
use PHPUnit_Framework_TestCase;


class DatabaseTest extends \PHPUnit_Framework_TestCase {

    static $jsonSourceDir = 'test/geotime/_data';

    public function testConnect() {
        Database::connect(Database::$testDbName);
        $this->assertTrue(Database::$connected);
    }

    /* Tests */

    public function testImportFromJson() {
        CriteriaGroup::drop();

        $this->assertEquals(0, CriteriaGroup::count());
        $nbImportedObjects = CriteriaGroup::importFromJson(self::$jsonSourceDir.'/criteriaGroups.json');
        $this->assertEquals(1, CriteriaGroup::count());
        $this->assertEquals(1, $nbImportedObjects);
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
}
 