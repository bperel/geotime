<?php
namespace geotime\Test;

use geotime\Database;
use geotime\models\Map;
use geotime\models\Period;

use geotime\Geotime;
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
        Database::connect("geotime_test");

        $neImport = new NaturalEarthImporter();
        $neImport->clean();
        $neImport->import('test/geotime/_data/countries.json');

        Map::drop();
        $map = Map::generateAndSaveReferences('testImage.svg', '1980-01-02', '1991-02-03');
        $map->save();
    }

    protected function tearDown() {
        $neImport = new NaturalEarthImporter();
        $neImport->clean();

        Map::drop();
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
        $this->assertEquals(2, $territoriesCountNEData['located']);
        $this->assertEquals(2, $territoriesCountNEData['total']);
    }
} 