<?php

namespace geotime\Test;

use geotime\helpers\MapHelper;
use geotime\helpers\ModelHelper;
use geotime\models\mariadb\Map;
use geotime\Test\Helper\EntityTestHelper;

include_once('EntityTestHelper.php');

class MapTest extends EntityTestHelper {

    static function setUpBeforeClass() {
        //Map::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        //Map::$log->info(__CLASS__." tests ended");
    }

    public function getRepository()
    {
        return ModelHelper::getEm()->getRepository(Map::CLASSNAME);
    }

    public function testGenerateMap() {
        $date1Str = '2011-01-02';
        $date2Str = '2013-07-15';
        $imageFileName = 'testImage.svg';

        $map = MapHelper::generateAndSave($imageFileName, $date1Str, $date2Str);
        $this->assertNotNull($map);
        $this->assertEquals($imageFileName, $map->getFileName());

        $territories = $map->getTerritories();
        $this->assertInstanceOf('DateTime', $territories[0]->getStartDate());
        $this->assertInstanceOf('DateTime', $territories[0]->getEndDate());
    }

    public function testDeleteReferences() {
        $date1Str = '2011-01-02';
        $date2Str = '2013-07-15';
        $imageFileName = 'testImage.svg';

        $map = MapHelper::generateAndSave($imageFileName, $date1Str, $date2Str);
        $map->setUploadDate(new \DateTime());

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();

        MapHelper::deleteTerritories($map);

        /** @var Map $map */
        $map = $this->getRepository()->findOneBy(array());
        $this->assertEquals(0, count($map->getTerritories()));
    }
}
 