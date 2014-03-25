<?php
namespace geotime\Test;

use geotime\models\TerritoryWithPeriod;
use geotime\models\Territory;
use geotime\models\Period;

use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

use geotime\Import;
use geotime\Database;

class NaturalEarthImporterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \geotime\NaturalEarthImporter
     */
    var $neImport;

    static function setUpBeforeClass() {
        Import::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Import::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect("geotime_test");

        $this->neImport = new NaturalEarthImporter();
        $this->neImport->import('test/geotime/_data/countries.json');
    }

    protected function tearDown() {
        $this->neImport->clean();
    }

    /* Util functions */

    /**
     * @param Territory $territory
     * @return float
     */
    private function getCoordinatesCount($territory) {

        /** @var TerritoryWithPeriod $territoryWithPeriod */
        $territoryWithPeriod = TerritoryWithPeriod::one(array('territory.$id'=>new \MongoId($territory->getId())));

        $coordinates = $territoryWithPeriod->getTerritory()->getPolygon();
        return (count($coordinates, COUNT_RECURSIVE) - 2*count($coordinates)) / 3;
    }

    /* Tests */

    public function testCleanAfterJsonImport() {

        $this->neImport->clean();
        $this->assertEquals(0, Period::count());
        $this->assertEquals(0, Territory::count());
        $this->assertEquals(0, TerritoryWithPeriod::count());
    }

    public function testCleanAfterManualImport() {

        $this->neImport->clean();

        $p = new Period();
        $p->save();
        $this->assertEquals(1, Period::count());

        $t = new Territory();
        $t->save();
        $this->assertEquals(1, Territory::count());

        $tp = new TerritoryWithPeriod();
        $tp->save();
        $this->assertEquals(1, TerritoryWithPeriod::count());

        $this->neImport->clean();

        $this->assertEquals(0, Period::count());
        $this->assertEquals(0, Territory::count());
        $this->assertEquals(0, TerritoryWithPeriod::count());
    }

    public function testImportFromJson() {

        $this->neImport->clean();
        $nbCountriesImported = $this->neImport->import('test/geotime/_data/countries.json');
        $this->assertEquals(3, $nbCountriesImported);
    }

    public function testFullImportedCountry() {

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(array('name'=>'Luxembourg'));

        /** @var TerritoryWithPeriod $territoryWithPeriod */
        $territoryWithPeriod = TerritoryWithPeriod::one(array('territory.$id'=>new \MongoId($luxembourg->getId())));

        $this->assertNotNull($territoryWithPeriod->getPeriod());
        $this->assertNotNull($territoryWithPeriod->getTerritory());
        $this->assertNotNull($territoryWithPeriod->getTerritory()->getArea());
        $this->assertGreaterThan(0, $territoryWithPeriod->getTerritory()->getArea()); // The area should also exist (calculated in preSave method)
    }

    public function testCountImportedCountries() {

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(array('name'=>'Luxembourg'));
        $this->assertEquals(7, $this->getCoordinatesCount($luxembourg));

        /** @var Territory $japan */
        $japan = Territory::one(array('name'=>'Japan'));
        $this->assertEquals(12 + 37 + 16, $this->getCoordinatesCount($japan)); // Japan is made up, in the map, of 3 islands => 12 + 37 + 16 coordinates.
    }
} 