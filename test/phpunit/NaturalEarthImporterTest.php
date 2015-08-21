<?php
namespace geotime\Test;

use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\TerritoryHelper;
use geotime\Import;
use geotime\NaturalEarthImporter;
use geotime\Test\Helper\MariaDbTestHelper;

class NaturalEarthImporterTest extends MariaDbTestHelper {

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

    public function setUp() {
        parent::setUp();

        $this->neImport = new NaturalEarthImporter();
        $this->neImport->import('test/phpunit/_data/countries.json');
    }

    /* Util functions */

    /**
     * @param \geotime\models\mariadb\Territory $territory
     * @return float
     */
    private function getCoordinatesCount($territory) {

        $coordinates = $territory->getPolygon();
        return (count((array)$coordinates, COUNT_RECURSIVE) - 2*count((array)$coordinates)) / 3;
    }

    /* Tests */

    public function testImportFromJson() {
        $nbCountriesImported = $this->neImport->import('test/phpunit/_data/countries.json');
        $this->assertEquals(count(self::$neSovereignties), $nbCountriesImported);
    }

    public function testImportFromJsonTwice() {
        $this->neImport->import('test/phpunit/_data/countries.json');
        $nbCountriesImported = $this->neImport->import('test/phpunit/_data/countries.json');
        $this->assertEquals(count(self::$neSovereignties), $nbCountriesImported);
    }

    public function testFullyImportedCountry() {

        /** @var \geotime\models\mariadb\ReferencedTerritory $referencedTerritory */
        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('Luxembourg');
        $this->assertNotNull($referencedTerritory);

        /** @var \geotime\models\mariadb\Territory $territory */
        $territory = TerritoryHelper::findOneByReferencedTerritoryId($referencedTerritory->getId());
        $this->assertNotNull($territory);

        $this->assertNull($territory->getStartDate());
        $this->assertNotNull($territory->getArea());
        $this->assertGreaterThan(0, $territory->getArea()); // The area should also exist (calculated in preSave method)
    }

    public function testCountImportedCountries() {

        /** @var \geotime\models\mariadb\ReferencedTerritory $referencedTerritory */
        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('Luxembourg');

        /** @var \geotime\models\mariadb\Territory $luxembourg */
        $luxembourg = TerritoryHelper::findOneByReferencedTerritoryId($referencedTerritory->getId());
        $this->assertEquals(7, $this->getCoordinatesCount($luxembourg));

        /** @var \geotime\models\mariadb\ReferencedTerritory $referencedTerritoryJapan */
        $referencedTerritoryJapan = ReferencedTerritoryHelper::findOneByName('Japan');

        /** @var \geotime\models\mariadb\Territory $japan */
        $japan = TerritoryHelper::findOneByReferencedTerritoryId($referencedTerritoryJapan->getId());
        $this->assertEquals(12 + 37 + 16, $this->getCoordinatesCount($japan)); // Japan is made up, in the map, of 3 islands => 12 + 37 + 16 coordinates.
    }
}
