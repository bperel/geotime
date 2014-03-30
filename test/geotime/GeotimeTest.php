<?php
namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\models\Map;
use geotime\models\Period;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

class GeotimeTest extends \PHPUnit_Framework_TestCase {

    static $neMapName = 'test/geotime/_data/countries.json';
    static $customMapName = 'testImage.svg';

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

    public function testGetPeriodsAndTerritoriesCount() {

        $periodsAndTerritoriesCount = Geotime::getMapsAndLocalizedTerritoriesCount();

        $this->assertEquals(2, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[self::$customMapName];
        $this->assertEquals(1, $territoriesCountSvgData);

        $territoriesCountNEData = $periodsAndTerritoriesCount[self::$neMapName];
        $this->assertEquals(3, $territoriesCountNEData);
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

        $this->assertEquals('1980-1991', $periodsAndCoverage[0]->period);
        $this->assertEquals(0, $periodsAndCoverage[0]->coverage);

        $this->assertEquals('2012-2012', $periodsAndCoverage[1]->period);
        $this->assertEquals(
            405267 /* Japan */
           +  2412 /* Luxembourg */
           + 11578 /* French Southern and Antarctic Lands */,
            $periodsAndCoverage[1]->coverage);
    }

    function testGetIncompleteMapInfo() {
        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo(1985);
        $this->assertNotNull($incompleteMap);
        $this->assertEquals('testImage.svg', $incompleteMap->fileName);

        $startDate = new \MongoDate(strtotime('1980-01-02'));
        $this->assertEquals($startDate->sec, $incompleteMap->territories[0]->period->start->sec);

        $endDate = new \MongoDate(strtotime('1991-02-03'));
        $this->assertEquals($endDate->sec, $incompleteMap->territories[0]->period->end->sec);

        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo(1970);
        $this->assertNull($incompleteMap);

        /** @var object $incompleteMap */
        $incompleteMap = Geotime::getIncompleteMapInfo(2012);
        $this->assertNull($incompleteMap);
    }
} 