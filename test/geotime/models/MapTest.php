<?php

namespace geotime\Test;

use geotime\models\Period;
use geotime\models\Territory;
use PHPUnit_Framework_TestCase;
use geotime\models\Map;
use geotime\Database;


class MapTest extends \PHPUnit_Framework_TestCase {

    static function setUpBeforeClass() {
        Map::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Map::$log->info(__CLASS__." tests ended");
    }

    protected function setUp() {
        Database::connect(Database::$testDbName);
    }

    protected function tearDown() {
        Territory::drop();
        Period::drop();
    }

    public function testGenerateMap() {
        $date1Str = '2011-01-02';
        $date2Str = '2013-07-15';
        $imageFileName = 'testImage.svg';

        $map = Map::generateAndSaveReferences($imageFileName, $date1Str, $date2Str);
        $this->assertNotNull($map);
        $this->assertEquals($imageFileName, $map->getFileName());

        $territories = $map->getTerritories();
        $this->assertInstanceOf('MongoDate', $territories[0]->getPeriod()->getStart());
        $this->assertInstanceOf('MongoDate', $territories[0]->getPeriod()->getEnd());
    }
}
 