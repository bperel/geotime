<?php

namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\models\Map;


class MapTest extends \PHPUnit_Framework_TestCase {

    static function setUpBeforeClass() {
        Map::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        Map::$log->info(__CLASS__." tests ended");
    }

    public function testGenerateMap() {
        $date1Str = '2011-01-02';
        $date2Str = '2013-07-15';
        $imageFileName = 'testImage.svg';

        $map = Map::generateAndSaveReferences($imageFileName, $date1Str, $date2Str);
        $this->assertNotNull($map);
        $this->assertEquals($imageFileName, $map->getFileName());

        $territoriesWithPeriods = $map->getTerritoriesWithPeriods();
        $this->assertInstanceOf('MongoDate', $territoriesWithPeriods[0]->getPeriod()->getStart());
        $this->assertInstanceOf('MongoDate', $territoriesWithPeriods[0]->getPeriod()->getEnd());
    }
}
 