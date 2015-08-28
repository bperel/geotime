<?php
namespace geotime\Test;

use Doctrine\ORM\EntityRepository;
use geotime\helpers\ModelHelper;

use geotime\Geotime;
use geotime\helpers\MapHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\mariadb\Map;
use geotime\models\mariadb\Territory;
use geotime\NaturalEarthImporter;
use geotime\Test\Helper\MariaDbTestHelper;
use geotime\Util;

include_once('MariaDbTestHelper.php');

class GeotimeTest extends MariaDbTestHelper {
    
    var $map;

    static $neMapName = 'test/phpunit/_data/countries.json';
    static $fixtures_dir_svg = "test/phpunit/_fixtures/svg/";

    static $customMapName = 'testImage.svg';
    static $simpleMapName = 'simpleMap.svg';

    static $neAreas = array(
        405267, /* Japan */
          2412, /* Luxembourg */
        633743, /* France */
         11578  /* French Southern and Antarctic Lands */);

    static $neSovereignties = array('Japan', 'Luxembourg', 'France');

    static function setUpBeforeClass() {
        Geotime::$log->info(__CLASS__." tests started");
        Util::$cache_dir_svg = self::$fixtures_dir_svg;
    }

    static function tearDownAfterClass() {
        Geotime::$log->info(__CLASS__." tests ended");
    }

    public function setUp() {
        parent::setUp();

        $neImport = new NaturalEarthImporter();
        $neImport->import(self::$neMapName);

        $this->map = MapHelper::generateAndSave(self::$customMapName, '1980-01-02', '1991-02-03');
        $this->map->setUploadDate(new \DateTime());

        $referencedTerritory = ReferencedTerritoryHelper::buildAndCreate('A referenced territory');
        $territory = TerritoryHelper::buildWithReferencedTerritory($referencedTerritory, '1985-01-01', '1986-12-21');
        $territory->setPolygon(new \stdClass());
        $this->map->addTerritory($territory);
        $territory->setMap($this->map);
        TerritoryHelper::save($territory);

        ModelHelper::getEm()->persist($this->map);
        ModelHelper::getEm()->flush();
    }

    public function testGetPeriodsAndTerritoriesData() {

        $periodsAndTerritoriesCount = Geotime::getMapsAndLocalizedTerritoriesCount(false);

        $this->assertEquals(2, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[self::$customMapName];
        $this->assertEquals(2, $territoriesCountSvgData['count']);
        $this->assertEquals(0, $territoriesCountSvgData['area']);

        $territoriesCountNEData = $periodsAndTerritoriesCount[self::$neMapName];
        $this->assertEquals(array_sum(self::$neAreas), $territoriesCountNEData['area']);
        $this->assertEquals(count(self::$neSovereignties), $territoriesCountNEData['count']);
    }

    public function testGetPeriodsAndTerritoriesDataSvgOnly() {

        $periodsAndTerritoriesCount = Geotime::getMapsAndLocalizedTerritoriesCount(true);

        $this->assertEquals(1, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[self::$customMapName];
        $this->assertEquals(2, $territoriesCountSvgData['count']);
        $this->assertEquals(0, $territoriesCountSvgData['area']);
    }

    public function testClean() {

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');
        $t = new Territory($referencedTerritory, true);
        $t->setMap($this->map);
        ModelHelper::getEm()->persist($t);
        ModelHelper::getEm()->flush();

        $this->assertNotEquals(0, TerritoryHelper::count());
        $this->assertNotEquals(0, MapHelper::count());

        Geotime::clean();

        $this->assertEquals(0, TerritoryHelper::count());
        $this->assertEquals(0, MapHelper::count());
    }

    public function testGetPeriodsAndCoverage() {
        $coverageInfo = Geotime::getCoverageInfo();

        $optimalCoverage = $coverageInfo['optimalCoverage'];
        $this->assertEquals(Geotime::$optimalCoverage, $optimalCoverage);

        $periodsAndCoverage = $coverageInfo['periodsAndCoverage'];

        $this->assertEquals('1980', $periodsAndCoverage[0]->start);
        $this->assertEquals('1991', $periodsAndCoverage[0]->end);
        $this->assertEquals(0, $periodsAndCoverage[0]->coverage);

        $this->assertEquals('1985', $periodsAndCoverage[1]->start);
        $this->assertEquals('1986', $periodsAndCoverage[1]->end);
        $this->assertEquals(0, $periodsAndCoverage[0]->coverage);

        $this->assertEquals('2012', $periodsAndCoverage[2]->start);
        $this->assertEquals('2012', $periodsAndCoverage[2]->end);
        $this->assertEquals(array_sum(self::$neAreas), $periodsAndCoverage[2]->coverage);
    }

    function testGetIncompleteMapInfoFound() {
        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo();
        $this->assertNotNull($incompleteMap);
        $this->assertEquals('testImage.svg', $incompleteMap->fileName);

        $startDate = new \DateTime('1985-01-01');
        $this->assertEquals($startDate, $incompleteMap->territories[0]->startDate);

        $endDate = new \DateTime('1986-12-21');
        $this->assertEquals($endDate, $incompleteMap->territories[0]->endDate);
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

        $this->assertEquals(1, count(Geotime::getReferencedTerritories('J')));
        $this->assertEquals(0, count(Geotime::getReferencedTerritories('K')));
    }

    function testGetTerritoriesEmptyParameter()
    {
        $this->assertTrue(is_string(Geotime::getReferencedTerritories(null)));
        $this->assertTrue(is_string(Geotime::getReferencedTerritories('')));
    }

    function testUpdateMapInexisting() {
        $this->map = MapHelper::generateAndSave(self::$customMapName, '1980-01-02', '1991-02-03');

        ModelHelper::getEm()->persist($this->map);
        ModelHelper::getEm()->flush();

        $mapId = $this->map->getId();

        MapHelper::delete($mapId);

        $updatedMap = Geotime::updateMap($mapId, 'mercator', array('0', '0', '0'), array(array('0', '10')), 200);
        $this->assertNull($updatedMap);
    }

    function testUpdateMap() {
        $this->map = MapHelper::generateAndSave(self::$customMapName, '1980-01-02', '1991-02-03');
        $this->map->setProjection('mercator');

        ModelHelper::getEm()->persist($this->map);
        ModelHelper::getEm()->flush();

        $mapId = $this->map->getId();

        $updatedMap = Geotime::updateMap(
            $mapId, 'mercator2', array('10', '20', '30'), array('5', '5'), 200,
            array(
                array('pointId' => 0,
                      'type' => 'bgMap',
                      'coordinates' => array('lng' => -4.574, 'lat' => 48.567)
                ),
                array('pointId' => 0,
                      'type' => 'fgMap',
                      'coordinates' => array('x' => 88, 'y' => 70))
            )
        );
        $this->assertNotNull($updatedMap);
        $this->assertEquals($updatedMap->getFileName(), $this->map->getFileName());
        $this->assertEquals($updatedMap->getProjection(), 'mercator2');
        $this->assertEquals($updatedMap->getCenter(), array(5, 5));
        $this->assertEquals($updatedMap->getRotation(), array(10, 20, 30));
        $this->assertEquals($updatedMap->getScale(), 200);

        $calibrationPoints = $updatedMap->getCalibrationPoints();
        $this->assertInstanceOf('geotime\models\mariadb\CalibrationPoint', $calibrationPoints[0]);
    }

    function testUpdateMapMissingData() {
        $this->map = MapHelper::generateAndSave(self::$customMapName, '1980-01-02', '1991-02-03');
        $this->map->setProjection('mercator');

        ModelHelper::getEm()->persist($this->map);
        ModelHelper::getEm()->flush();

        $mapId = $this->map->getId();

        $updatedMap = Geotime::updateMap($mapId, null);
        $this->assertNotNull($updatedMap);
        $this->assertEquals($updatedMap->getFileName(), $this->map->getFileName());
        $this->assertEquals($updatedMap->getProjection(), $this->map->getProjection());
    }

    function testAddLocatedTerritory() {
        $this->map = MapHelper::generateAndSave(self::$simpleMapName, '1980-01-02', '1991-02-03');
        $this->map->setProjection('mercator');
        $this->map->setCenter(array(0,0));
        $this->map->setScale(700);
        $this->map->setRotation(array(0,0,0));

        ModelHelper::getEm()->persist($this->map);
        ModelHelper::getEm()->flush();

        $mapId = $this->map->getId();

        $referencedTerritory = ReferencedTerritoryHelper::findOneByName('France');

        $xpath = '//path[id="simplePath"]';
        $territoryPeriodStart = '1980-01-02';
        $territoryPeriodEnd = '1991-04-06';

        Geotime::saveLocatedTerritory($mapId, $referencedTerritory->getId(), $xpath, $territoryPeriodStart, $territoryPeriodEnd);

        $createdTerritory = TerritoryHelper::findOneByXpath($xpath);
        $this->assertNotEmpty($createdTerritory);
        $this->assertEquals($createdTerritory->getUserMade(), true);
        $this->assertEquals($xpath, $createdTerritory->getXpath());
        $this->assertInternalType('array', $createdTerritory->getPolygon()[0][0]);
        $this->assertEquals(new \DateTime($territoryPeriodStart), $createdTerritory->getStartDate());
        $this->assertEquals(new \DateTime($territoryPeriodEnd), $createdTerritory->getEndDate());
        $this->assertGreaterThan(0, $createdTerritory->getArea());

        /** @var Map $mapWithTerritory */
        $mapWithTerritory = MapHelper::find($mapId);
        $this->assertEquals(count($mapWithTerritory->getTerritories()), 1);

        $this->assertEquals(Geotime::getImportedTerritoriesCount(), 3);
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        // TODO: Implement getRepository() method.
    }
}
