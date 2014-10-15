<?php
namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\Import;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

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
        Database::connect(Database::$testDbName);

        Geotime::clean();

        $this->neImport = new NaturalEarthImporter();
        $this->neImport->import('test/geotime/_data/countries.json');
    }

    protected function tearDown() {
        Geotime::clean();
    }

    /* Util functions */

    /**
     * @param Territory $territory
     * @return float
     */
    private function getCoordinatesCount($territory) {

        $coordinates = $territory->getPolygon();
        return (count($coordinates, COUNT_RECURSIVE) - 2*count($coordinates)) / 3;
    }

    /* Tests */

    public function testImportFromJson() {

        Geotime::clean();

        $nbCountriesImported = $this->neImport->import('test/geotime/_data/countries.json');
        $this->assertEquals(3, $nbCountriesImported);
    }

    public function testImportFromJsonTwice() {

        Geotime::clean();

        $this->neImport->import('test/geotime/_data/countries.json');
        $nbCountriesImported = $this->neImport->import('test/geotime/_data/countries.json');
        $this->assertEquals(3, $nbCountriesImported);
    }

    public function testFullyImportedCountry() {

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(array('name'=>'Luxembourg'));

        $this->assertNull($luxembourg->getPeriod());
        $this->assertNotNull($luxembourg->getArea());
        $this->assertGreaterThan(0, $luxembourg->getArea()); // The area should also exist (calculated in preSave method)
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