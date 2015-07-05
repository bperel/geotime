<?php
namespace geotime\Test;

use Doctrine\ORM\EntityRepository;
use geotime\helpers\ModelHelper;

use geotime\Database;
use geotime\Geotime;
use geotime\helpers\MapHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\mariadb\Map;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use geotime\Test\Helper\MariaDbTestHelper;

include_once('MariaDbTestHelper.php');

class GeotimeTest extends MariaDbTestHelper {

    static $neMapName = 'test/phpunit/_data/countries.json';
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

    public function setUp() {
        parent::setUp();
        Database::connect(Database::$testDbName);

        Geotime::clean();

        $neImport = new NaturalEarthImporter();
        $neImport->import(self::$neMapName);

        $map = MapHelper::generateAndSaveReferences(self::$customMapName, '1980-01-02', '1991-02-03');
        $map->setUploadDate(new \DateTime());

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();
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

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');
        $t = new \geotime\models\mariadb\Territory($referencedTerritory, true);
        ModelHelper::getEm()->persist($t);
        ModelHelper::getEm()->flush();

        $this->assertNotEquals(0, TerritoryHelper::count());
        $this->assertNotEquals(0, MapHelper::count());

        Geotime::clean();

        $this->assertEquals(0, Territory::count());
        $this->assertEquals(0, MapHelper::count());
    }

    public function testCleanKeepMaps() {

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');
        $t = new \geotime\models\mariadb\Territory($referencedTerritory, true);
        ModelHelper::getEm()->persist($t);
        ModelHelper::getEm()->flush();

        $this->assertNotEquals(0, TerritoryHelper::count());
        $this->assertNotEquals(0, MapHelper::count());

        Geotime::clean(true);

        $this->assertEquals(0, Territory::count());
        $this->assertNotEquals(0, MapHelper::count());
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

    function testGetImportedTerritoriesCount() {
        $this->assertEquals(Geotime::getImportedTerritoriesCount(), 3);
    }

    function testGetTerritories() {
        $this->assertEquals(1, count(Geotime::getReferencedTerritories('Fr')));
        $this->assertEquals(0, count(Geotime::getReferencedTerritories('fr')));

        $this->assertEquals(1, count(Geotime::getReferencedTerritories('J')));
        $this->assertEquals(0, count(Geotime::getReferencedTerritories('K')));
    }

    function testGetTerritoriesEmptyParameter()
    {
        $this->assertTrue(is_string(Geotime::getReferencedTerritories(null)));
        $this->assertTrue(is_string(Geotime::getReferencedTerritories('')));
    }

    function testUpdateMapInexisting() {
        $map = new Map();

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();

        MapHelper::delete($map->getId());

        $updatedMap = Geotime::updateMap($map->getId(), 'mercator', array('0', '0', '0'), array(array('0', '10')), 200);
        $this->assertNull($updatedMap);
    }

    function testUpdateMap() {
        $map = new Map();
        $map->setFileName(self::$customMapName);
        $map->setProjection('mercator');

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();

        $mapId = $map->getId();

        $updatedMap = Geotime::updateMap(
            $mapId, 'mercator2', array('10', '20', '30'), array('5', '5'), 200,
            array(array('bgMap' => array('lng' => -4.574, 'lat' => 48.567), 'fgMap' => array('x' => 88, 'y' => 70)))
        );
        $this->assertNotNull($updatedMap);
        $this->assertEquals($updatedMap->getFileName(), $map->getFileName());
        $this->assertEquals($updatedMap->getProjection(), 'mercator2');
        $this->assertEquals($updatedMap->getCenter(), array(5, 5));
        $this->assertEquals($updatedMap->getRotation(), array(10, 20, 30));
        $this->assertEquals($updatedMap->getScale(), 200);

        $calibrationPoints = $updatedMap->getCalibrationPoints();
        $this->assertInstanceOf('geotime\models\CalibrationPoint', $calibrationPoints[0]);
    }

    function testUpdateMapMissingData() {
        $map = new Map();
        $map->setFileName(self::$customMapName);
        $map->setProjection('mercator');

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();
        $mapId = $map->getId();

        $updatedMap = Geotime::updateMap($mapId, null);
        $this->assertNotNull($updatedMap);
        $this->assertEquals($updatedMap->getFileName(), $map->getFileName());
        $this->assertEquals($updatedMap->getProjection(), $map->getProjection());
    }

    function testAddLocatedTerritory() {
        $map = new Map();

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();
        $mapId = $map->getId();

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');

        $coordinates = array(
            array(-76.73647242455775, 19.589864044838837),
            array(-76.67084026038955, 19.24637514426756),
            array(-76.51475666216831, 18.926012649077077)
        );

        $xpath = '//path[id="My territory"]';
        $territoryPeriodStart = '1980-01-02';
        $territoryPeriodEnd = '1991-04-06';

        Geotime::saveLocatedTerritory($mapId, $referencedTerritory->getId(), $xpath, $territoryPeriodStart, $territoryPeriodEnd);

        /** @var Territory $createdTerritory */
        $createdTerritory = Territory::one(array('xpath' => $xpath));
        $this->assertNotEmpty($createdTerritory);
        $this->assertEquals($createdTerritory->getUserMade(), true);
        $this->assertEquals($xpath, $createdTerritory->getXpath());
        $this->assertEquals(array(array($coordinates)), $createdTerritory->getPolygon());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodStart)), $createdTerritory->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodEnd)), $createdTerritory->getPeriod()->getEnd());
        $this->assertGreaterThan(0, $createdTerritory->getArea());

        /** @var Map $mapWithTerritory */
        $mapWithTerritory = MapHelper::find($mapId);
        $this->assertEquals(count($mapWithTerritory->getTerritories()), 1);

        $this->assertEquals(Geotime::getImportedTerritoriesCount(), 3);
    }

    function testUpdateLocatedTerritory() {
        $map = new Map();

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();
        $mapId = $map->getId();

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');

        $coordinates = array(
            array(-76.73647242455775, 19.589864044838837),
            array(-76.67084026038955, 19.24637514426756),
            array(-76.51475666216831, 18.926012649077077)
        );

        $xpath = '//path[id="My territory"]';
        $territoryPeriodStart = '1980-01-02';
        $territoryPeriodEnd = '1991-04-06';

        Geotime::saveLocatedTerritory(
            $mapId, $referencedTerritory->getId(), $xpath, $territoryPeriodStart, $territoryPeriodEnd
        );

        /** @var Territory $territoryWithReference */
        $territoryWithReference = Territory::one(array('xpath' => $xpath));
        $this->assertNotEmpty($territoryWithReference);
        $this->assertEquals($territoryWithReference->getReferencedTerritory(), $referencedTerritory);
        $this->assertEquals($territoryWithReference->getUserMade(), true);
        $this->assertEquals($xpath, $territoryWithReference->getXpath());
        $this->assertEquals(array(array($coordinates)), $territoryWithReference->getPolygon());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodStart)), $territoryWithReference->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime($territoryPeriodEnd)), $territoryWithReference->getPeriod()->getEnd());
        $this->assertGreaterThan(0, $territoryWithReference->getArea());

        /** @var Map $mapWithTerritory */
        $mapWithTerritory = MapHelper::find($mapId);
        $this->assertEquals(count($mapWithTerritory->getTerritories()), 1);

    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        // TODO: Implement getRepository() method.
    }
}