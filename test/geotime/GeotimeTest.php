<?php
namespace geotime\Test;

use geotime\Geotime;
use geotime\NaturalEarthImporter;
use PHPUnit_Framework_TestCase;

use geotime\Database;

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
        $neImport->import('test/geotime/data/countries.json');
    }

    protected function tearDown() {
        $neImport = new NaturalEarthImporter();
        $neImport->clean();
    }

    public function testGetPeriodsAndTerritoriesCount() {

        $periodsAndTerritoriesCount = Geotime::getPeriodsAndTerritoriesCount();

        $this->assertEquals(1, count(array_keys($periodsAndTerritoriesCount)));
        $this->assertEquals(2, $periodsAndTerritoriesCount[key($periodsAndTerritoriesCount)]);
    }
} 