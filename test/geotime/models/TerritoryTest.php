<?php

namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\models\Territory;
use geotime\NaturalEarthImporter;
use geotime\Database;


class TerritoryTest extends \PHPUnit_Framework_TestCase {

    static function setUpBeforeClass() {
        Territory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Territory::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect("geotime_test");

        $neImport = new NaturalEarthImporter();
        $neImport->clean();
        $neImport->import('test/geotime/_data/countries.json');
    }


    protected function tearDown() {
        $neImport = new NaturalEarthImporter();
        $neImport->clean();
    }

    public function testGetTerritoryArea() {

        /** @var Territory $japan */
        $japan = Territory::one(array('name'=>'Japan'));
        $this->assertEquals(405267, $japan->getArea());

        /** @var Territory $luxembourg */
        $luxembourg = Territory::one(array('name'=>'Luxembourg'));
        $this->assertEquals(2412, $luxembourg->getArea());
    }
}
 