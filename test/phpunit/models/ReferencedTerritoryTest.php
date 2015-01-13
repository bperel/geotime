<?php

namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\models\ReferencedTerritory;
use PHPUnit_Framework_TestCase;


class ReferencedTerritoryTest extends \PHPUnit_Framework_TestCase {

    static function setUpBeforeClass() {
        ReferencedTerritory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        ReferencedTerritory::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect(Database::$testDbName);
        Geotime::clean();
    }


    protected function tearDown() {
        Geotime::clean();
    }

    public function testReferencedTerritoriesStringToTerritoryArray() {
        $this->assertEquals(0, ReferencedTerritory::count(array()));

        $alreadyImportedTerritory = new ReferencedTerritory(array('name' => 'A territory'));
        $alreadyImportedTerritory->save();

        $this->assertEquals(1, ReferencedTerritory::count(array()));

        $territoriesAsString = 'A territory|A new territory';
        ReferencedTerritory::referencedTerritoriesStringToTerritoryArray($territoriesAsString);

        $this->assertEquals(2, ReferencedTerritory::count(array()));
    }
}
 