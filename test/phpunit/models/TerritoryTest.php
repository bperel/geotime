<?php

namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\models\ReferencedTerritory;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;


class TerritoryTest extends \PHPUnit_Framework_TestCase {

    static function setUpBeforeClass() {
        Territory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Territory::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect(Database::$testDbName);

        Geotime::clean();

        $neImport = new NaturalEarthImporter();
        $neImport->import('test/phpunit/_data/countries.json');
    }


    protected function tearDown() {
        Geotime::clean();
    }

    public function testGetTerritoryArea() {

        /** @var ReferencedTerritory $japanReferencedTerritory */
        $japanReferencedTerritory = ReferencedTerritory::one(array('name'=>'Japan'));

        /** @var Territory $japan */
        $japan = Territory::one(Territory::getReferencedTerritoryFilter($japanReferencedTerritory));
        $this->assertEquals(405267, $japan->getArea());

        /** @var ReferencedTerritory $luxembourgReferencedTerritory */
        $luxembourgReferencedTerritory = ReferencedTerritory::one(array('name'=>'Luxembourg'));

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(Territory::getReferencedTerritoryFilter($luxembourgReferencedTerritory));
        $this->assertEquals(2412, $luxembourg->getArea());
    }
}
 