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
        $neImport->import('test/geotime/_data/countries.json');

        $map = Map::generateAndSaveReferences('testImage.svg', '1980-01-02', '1991-02-03');
        $map->save();
    }

    protected function tearDown() {
        Geotime::clean();
    }

    public function testGetPeriodsAndTerritoriesCount() {
        $svgDataPeriod = new Period(array('start'=>'1980-01-02', 'end'=>'1991-02-03'));
        $neDataPeriod = new Period(array('start'=>NaturalEarthImporter::$dataDate, 'end'=>NaturalEarthImporter::$dataDate));

        $periodsAndTerritoriesCount = Geotime::getPeriodsAndTerritoriesCount();

        $this->assertEquals(2, count(array_keys($periodsAndTerritoriesCount)));

        $territoriesCountSvgData = $periodsAndTerritoriesCount[$svgDataPeriod->__toString()];
        $this->assertEquals(0, $territoriesCountSvgData['located']);
        $this->assertEquals(1, $territoriesCountSvgData['total']);

        $territoriesCountNEData = $periodsAndTerritoriesCount[$neDataPeriod->__toString()];
        $this->assertEquals(3, $territoriesCountNEData['located']);
        $this->assertEquals(3, $territoriesCountNEData['total']);
    }

    public function testClean() {

        Geotime::clean();
        $this->assertEquals(0, Period::count());
        $this->assertEquals(0, Territory::count());
        $this->assertEquals(0, Map::count());
    }

    public function testCleanAfterManualImport() {

        Geotime::clean();

        $p = new Period();
        $p->save();
        $this->assertEquals(1, Period::count());

        $t = new Territory();
        $t->save();
        $this->assertEquals(1, Territory::count());

        Geotime::clean();

        $this->assertEquals(0, Period::count());
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
} 