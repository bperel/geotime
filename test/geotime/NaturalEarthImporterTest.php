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
     * @var \PHPUnit_Framework_MockObject_MockObject|Import
     */
    var $mock;

    /**
     * @var \geotime\NaturalEarthImporter
     */
    var $neImport;

    static function setUpBeforeClass() {
        Import::$log->info("Starting ".__CLASS__." tests");
    }

    static function tearDownAfterClass() {
        Import::$log->info(__CLASS__." Tests ended");
    }

    protected function setUp() {
        Database::connect("geotime_test");

        $this->neImport = new NaturalEarthImporter();

        TerritoryWithPeriod::drop();
        Territory::drop();
        Period::drop();
    }

    protected function tearDown() {
        TerritoryWithPeriod::drop();
        Territory::drop();
        Period::drop();
    }

    /* Tests */

    public function testImportFromJson() {

        $this->neImport->import('test/geotime/data/countries.json');

        /** @var TerritoryWithPeriod $territoryWithPeriod */
        $territoryWithPeriod = TerritoryWithPeriod::one();
        $this->assertNotNull(1, $territoryWithPeriod->getPeriod());
        $this->assertNotNull(1, $territoryWithPeriod->getTerritory());

    }
} 