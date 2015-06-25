<?php

namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\models\Map;
use PHPUnit_Framework_TestCase;


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
        Geotime::clean();
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

    public function testDeleteReferences() {
        $date1Str = '2011-01-02';
        $date2Str = '2013-07-15';
        $imageFileName = 'testImage.svg';

        $map = Map::generateAndSaveReferences($imageFileName, $date1Str, $date2Str);
        $map->save();
        $map->deleteTerritories();

        /** @var Map $map */
        $map = Map::one(array());
        $this->assertEquals(0, count($map->getTerritories()));

    }
}
 