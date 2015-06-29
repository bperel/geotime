<?php

namespace geotime\Test;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\Test\Helper\MariaDbTestHelper;
use geotime\models\mariadb\ReferencedTerritory;

include_once('MariaDbTestHelper.php');
include_once('ReferencedTerritoryHelper.php');

class ReferencedTerritoryTest extends MariaDbTestHelper {

    static function setUpBeforeClass() {
        //ReferencedTerritory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        //ReferencedTerritory::$log->info(__CLASS__." tests ended");
    }

    public function testReferencedTerritoriesStringToTerritoryArray() {
        $this->assertEquals(0, ReferencedTerritoryHelper::count());

        $alreadyImportedTerritory = new ReferencedTerritory('A territory');
        ModelHelper::$em->persist($alreadyImportedTerritory);
        ModelHelper::$em->flush();

        $this->assertEquals(1, ReferencedTerritoryHelper::count());

        $territoriesAsString = 'A territory|A new territory';
        ReferencedTerritoryHelper::referencedTerritoriesStringToTerritoryArray($territoriesAsString);

        $this->assertEquals(2, ReferencedTerritoryHelper::count());
    }
}
 