<?php
namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\Import;
use geotime\models\ReferencedTerritory;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

class NaturalEarthImporterTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \geotime\NaturalEarthImporter
     */
    var $neImport;

    static $neSovereignties = array('Japan', 'Luxembourg', 'France');

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
        $this->neImport->import('test/phpunit/_data/countries.json');
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

        $nbCountriesImported = $this->neImport->import('test/phpunit/_data/countries.json');
        $this->assertEquals(count(self::$neSovereignties), $nbCountriesImported);
    }

    public function testImportFromJsonTwice() {

        Geotime::clean();

        $this->neImport->import('test/phpunit/_data/countries.json');
        $nbCountriesImported = $this->neImport->import('test/phpunit/_data/countries.json');
        $this->assertEquals(count(self::$neSovereignties), $nbCountriesImported);
    }

    public function testFullyImportedCountry() {

        /** @var ReferencedTerritory $referencedTerritory */
        $referencedTerritory = ReferencedTerritory::one(array('name'=>'Luxembourg'));
        $this->assertNotNull($referencedTerritory);

        /** @var Territory $territory */
        $territory = Territory::one(Territory::getReferencedTerritoryFilter($referencedTerritory));
        $this->assertNotNull($territory);

        $this->assertNull($territory->getPeriod());
        $this->assertNotNull($territory->getArea());
        $this->assertGreaterThan(0, $territory->getArea()); // The area should also exist (calculated in preSave method)
    }

    public function testCountImportedCountries() {

        /** @var ReferencedTerritory $referencedTerritory */
        $referencedTerritory = ReferencedTerritory::one(array('name'=>'Luxembourg'));

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(Territory::getReferencedTerritoryFilter($referencedTerritory));
        $this->assertEquals(7, $this->getCoordinatesCount($luxembourg));

        /** @var ReferencedTerritory $referencedTerritoryJapan */
        $referencedTerritoryJapan = ReferencedTerritory::one(array('name'=>'Japan'));

        /** @var Territory $japan */
        $japan = Territory::one(Territory::getReferencedTerritoryFilter($referencedTerritoryJapan));
        $this->assertEquals(12 + 37 + 16, $this->getCoordinatesCount($japan)); // Japan is made up, in the map, of 3 islands => 12 + 37 + 16 coordinates.
    }
} 