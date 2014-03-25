<?php
namespace geotime\Test;

use geotime\models\TerritoryWithPeriod;
use geotime\models\Territory;
use geotime\models\Period;

use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

use geotime\models\CriteriaGroup;
use geotime\models\Criteria;

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
        $this->neImport->clean();
    }

    protected function tearDown() {
        $this->neImport->clean();
    }

    /* Util functions */

    /**
     * @param string $territoryName
     * @return float
     */
    private function getCoordinatesCount($territoryName) {

        /** @var TerritoryWithPeriod $territoryWithPeriod */
        $territory = Territory::one(array('name'=>$territoryName));
        $territoryWithPeriod = TerritoryWithPeriod::one(array('territory.$id'=>new \MongoId($territory->getId())));
        $this->assertNotNull($territoryWithPeriod->getPeriod());
        $this->assertNotNull($territoryWithPeriod->getTerritory());

        $coordinates = $territoryWithPeriod->getTerritory()->getPolygon()->{'$geoWithin'}['$polygon'];
        return (count($coordinates, COUNT_RECURSIVE) - 2*count($coordinates)) / 3;
    }

    /* Tests */

    public function testClean() {
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

        $nbCountriesImported = $this->neImport->import('test/geotime/_data/countries.json');

        $this->assertEquals(3, $nbCountriesImported);

        $this->assertEquals(7, $this->getCoordinatesCount('Luxembourg'));
        $this->assertEquals(12 + 37 + 16, $this->getCoordinatesCount('Japan')); // Japan is made up, in the map, of 3 islands => 12 + 37 + 16 coordinates.
    }
} 