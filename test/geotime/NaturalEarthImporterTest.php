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

        $nbCountriesImported = $this->neImport->import('test/geotime/data/countries.json');

        $this->assertEquals(2, $nbCountriesImported);

        /** @var TerritoryWithPeriod $territoryWithPeriod */
        $territoryWithPeriod = TerritoryWithPeriod::one();
        $this->assertNotNull(1, $territoryWithPeriod->getPeriod());
        $this->assertNotNull(2, $territoryWithPeriod->getTerritory());

    }
} 