<?php

namespace geotime\Test;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\Test\Helper\MariaDbTestHelper;
use geotime\models\mariadb\ReferencedTerritory;

include_once('MariaDbTestHelper.php');
include_once('ReferencedTerritoryHelper.php');

class ReferencedTerritoryTest extends MariaDbTestHelper {

    static function setUpBeforeClass() {
        //ReferencedTerritory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        //ReferencedTerritory::$log->info(__CLASS__." tests ended");
    }

    public function getRepository()
    {
        return ModelHelper::getEm()->getRepository(ReferencedTerritory::class);
    }

    public function testReferencedTerritoriesStringToTerritoryArray() {
        $this->assertEquals(0, ReferencedTerritoryHelper::count());

        $alreadyImportedTerritory = new ReferencedTerritory('A territory');
        ModelHelper::getEm()->persist($alreadyImportedTerritory);
        ModelHelper::getEm()->flush();

        $this->assertEquals(1, ReferencedTerritoryHelper::count());

        $territoriesAsString = 'A territory|A new territory';
        ReferencedTerritoryHelper::referencedTerritoriesStringToTerritoryArray($territoriesAsString);

        $this->assertEquals(2, ReferencedTerritoryHelper::count());
    }

    public function testBuildAndCreate() {
        $newReferencedTerritoryName = 'A new territory';
        $previousReferencedTerritoryName = 'The previous territory';

        $this->assertEquals(0, ReferencedTerritoryHelper::count());
        ReferencedTerritoryHelper::buildAndCreate($newReferencedTerritoryName, 'The previous territory', 'The next territory');

        $this->assertEquals(3, ReferencedTerritoryHelper::count());

        /** @var ReferencedTerritory $newReferencedTerritory */
        $newReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $newReferencedTerritoryName));
        $this->assertNotNull($newReferencedTerritory);
        $this->assertEquals(count($newReferencedTerritory->getPrevious()), 1);
        $this->assertEquals(count($newReferencedTerritory->getNext()), 1);

        /** @var ReferencedTerritory $previousReferencedTerritory */
        $previousReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $previousReferencedTerritoryName));
        $this->assertNotNull($previousReferencedTerritory);
        $this->assertEquals(count($previousReferencedTerritory->getPrevious()), 0);
        $this->assertEquals(count($previousReferencedTerritory->getNext()), 0);
    }

    public function testBuildAndSaveFromObject() {
        $object = json_decode(json_encode(array(
            'name' => array('value' => 'New territory'),
            'previous' => array('value' => 'The previous territory'),
            'next' => array('value' => 'The next territory|Another next territory')
        )));
        ReferencedTerritoryHelper::buildAndSaveFromObject($object);

        /** @var ReferencedTerritory $newReferencedTerritory */
        $newReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $object->name->value));
        $this->assertNotNull($newReferencedTerritory);
        $this->assertEquals(count($newReferencedTerritory->getPrevious()), 1);
        $this->assertEquals(count($newReferencedTerritory->getNext()), 2);

        /** @var ReferencedTerritory $previousReferencedTerritory */
        $previousReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $object->previous->value));
        $this->assertNotNull($previousReferencedTerritory);
        $this->assertEquals(count($previousReferencedTerritory->getPrevious()), 0);
        $this->assertEquals(count($previousReferencedTerritory->getNext()), 0);
    }

    public function testBuildAndSaveFromIncompleteObject() {
        $object = json_decode(json_encode(array(
            'name' => array('value' => 'Another new territory')
        )));
        ReferencedTerritoryHelper::buildAndSaveFromObject($object);

        /** @var ReferencedTerritory $newReferencedTerritory */
        $newReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $object->name->value));
        $this->assertNotNull($newReferencedTerritory);
        $this->assertEquals(count($newReferencedTerritory->getPrevious()), 1);
        $this->assertEquals(count($newReferencedTerritory->getNext()), 2);
    }
}
 