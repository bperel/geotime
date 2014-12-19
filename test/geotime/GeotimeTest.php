<?php
namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\models\Map;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

class GeotimeTest extends \PHPUnit_Framework_TestCase {

    static $neMapName = 'test/geotime/_data/countries.json';
    static $customMapName = 'testImage.svg';

    static $neAreas = array(
        405267, /* Japan */
          2412, /* Luxembourg */
        633743, /* France */
         11578  /* French Southern and Antarctic Lands */);

    static $neSovereignties = array('Japan', 'Luxembourg', 'France');

    static function setUpBeforeClass() {
        Geotime::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Geotime::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect(Database::$testDbName);

        Geotime::clean();

        $neImport = new NaturalEarthImporter();
        $neImport->import(self::$neMapName);

        $map = Map::generateAndSaveReferences(self::$customMapName, '1980-01-02', '1991-02-03');
        $map->save();
    }

    protected function tearDown() {
        Geotime::clean();
    }

    public function testGetPeriodsAndTerritoriesData() {

        $periodsAndTerritoriesCount = Geotime::getMapsAndLocalizedTerritoriesCount(false);

        $this->assertEquals(2, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[self::$customMapName];
        $this->assertEquals(1, $territoriesCountSvgData['count']);
        $this->assertEquals(0, $territoriesCountSvgData['area']);

        $territoriesCountNEData = $periodsAndTerritoriesCount[self::$neMapName];
        $this->assertEquals(array_sum(self::$neAreas), $territoriesCountNEData['area']);
        $this->assertEquals(count(self::$neSovereignties), $territoriesCountNEData['count']);
    }

    public function testGetPeriodsAndTerritoriesDataSvgOnly() {

        $periodsAndTerritoriesCount = Geotime::getMapsAndLocalizedTerritoriesCount(true);

        $this->assertEquals(1, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[self::$customMapName];
        $this->assertEquals(1, $territoriesCountSvgData['count']);
        $this->assertEquals(0, $territoriesCountSvgData['area']);
    }

    public function testClean() {

        Geotime::clean();
        $this->assertEquals(0, Territory::count());
        $this->assertEquals(0, Map::count());
    }

    public function testCleanAfterManualImport() {

        Geotime::clean();

        $t = new Territory();
        $t->save();
        $this->assertEquals(1, Territory::count());

        Geotime::clean();

        $this->assertEquals(0, Territory::count());
    }

    public function testGetPeriodsAndCoverage() {
        $coverageInfo = Geotime::getCoverageInfo();

        $optimalCoverage = $coverageInfo['optimalCoverage'];
        $this->assertEquals(Geotime::$optimalCoverage, $optimalCoverage);

        $periodsAndCoverage = $coverageInfo['periodsAndCoverage'];

        $this->assertEquals('1980', $periodsAndCoverage[0]->start);
        $this->assertEquals('1991', $periodsAndCoverage[0]->end);
        $this->assertEquals(0, $periodsAndCoverage[0]->coverage);

        $this->assertEquals('2012', $periodsAndCoverage[1]->start);
        $this->assertEquals('2012', $periodsAndCoverage[1]->end);
        $this->assertEquals(array_sum(self::$neAreas), $periodsAndCoverage[1]->coverage);
    }

    function testGetIncompleteMapInfoFound() {
        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo();
        $this->assertNotNull($incompleteMap);
        $this->assertEquals('testImage.svg', $incompleteMap->fileName);

        $startDate = new \MongoDate(strtotime('1980-01-02'));
        $this->assertEquals($startDate->sec, $incompleteMap->territories[0]->period->start->sec);

        $endDate = new \MongoDate(strtotime('1991-02-03'));
        $this->assertEquals($endDate->sec, $incompleteMap->territories[0]->period->end->sec);
    }

    function testGetIncompleteMapInfoNotFound() {

        Geotime::clean();

        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo();
        $this->assertNull($incompleteMap);
    }

    function testGetTerritories() {
        $this->assertEquals(1, count(Geotime::getTerritories('Fr')));
        $this->assertEquals(0, count(Geotime::getTerritories('fr')));

        $this->assertEquals(1, count(Geotime::getTerritories('J')));
        $this->assertEquals(0, count(Geotime::getTerritories('K')));
    }

    function testGetTerritoriesEmptyParameter()
    {
        $this->assertTrue(is_string(Geotime::getTerritories(null)));
        $this->assertTrue(is_string(Geotime::getTerritories('')));
    }

    function testUpdateMapInexisting() {
        $map = new Map();
        $map->save();
        $mapId = $map->getIdAsString();
        $map->delete();

        $updatedMap = Geotime::updateMap($mapId, 'mercator', array(array('0', '0'), array('10', '10')));
        $this->assertNull($updatedMap);
    }

    function testUpdateMap() {
        $map = new Map();
        $map->setFileName(self::$customMapName);
        $map->setProjection('mercator');
        $map->save();
        $mapId = $map->getIdAsString();

        $updatedMap = Geotime::updateMap($mapId, 'mercator2', array(array('0', '0'), array('10', '10')));
        $this->assertNotNull($updatedMap);
        $this->assertEquals($updatedMap->getFileName(), $map->getFileName());
        $this->assertEquals($updatedMap->getProjection(), 'mercator2');
        $this->assertEquals($updatedMap->getPosition(), array(array(0, 0), array(10, 10)));
    }

    function testAddLocatedTerritory() {
        $territoryName = 'myTerritory';
        $this->assertNull(Territory::one(array('name' => $territoryName)));

        $coordinates = array(
            array(-76.73647242455775, 19.589864044838837),
            array(-76.67084026038955, 19.24637514426756),
            array(-76.51475666216831, 18.926012649077077)
        );

        $xpath = '//path[id="My territory"]';
        $territoryPeriodStart = '1980-01-02';
        $territoryPeriodEnd = '1991-04-06';

        Geotime::addLocatedTerritory($territoryName, $coordinates, $xpath, $territoryPeriodStart, $territoryPeriodEnd);

        /** @var Territory $createdTerritory */
        $createdTerritory = Territory::one(array('name' => $territoryName));;
        $this->assertNotEmpty($createdTerritory);
        $this->assertEquals($createdTerritory->getUserMade(), true);
        $this->assertEquals($xpath, $createdTerritory->getXpath());
        $this->assertEquals($coordinates, $createdTerritory->getPolygon());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodStart)), $createdTerritory->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodEnd)), $createdTerritory->getPeriod()->getEnd());
    }
} 