<?php

namespace geotime\Test;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelperAbstract;
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
        return ModelHelper::getEm()->getRepository(ReferencedTerritory::CLASSNAME);
    }

    public function testReferencedTerritoriesStringToTerritoryArray() {
        $this->assertEquals(0, ReferencedTerritoryHelperAbstract::count());

        $alreadyImportedTerritory = new ReferencedTerritory('A territory');
        ModelHelper::getEm()->persist($alreadyImportedTerritory);
        ModelHelper::getEm()->flush();

        $this->assertEquals(1, ReferencedTerritoryHelperAbstract::count());

        $territoriesAsString = 'A territory|A new territory';
        ReferencedTerritoryHelperAbstract::referencedTerritoriesStringToTerritoryArray($territoriesAsString);

        $this->assertEquals(2, ReferencedTerritoryHelperAbstract::count());
    }

    public function testBuildAndCreate() {
        $newReferencedTerritoryName = 'A new territory';
        $previousReferencedTerritoryName = 'The previous territory';

        $this->assertEquals(0, ReferencedTerritoryHelperAbstract::count());
        ReferencedTerritoryHelperAbstract::buildAndCreate($newReferencedTerritoryName, 'The previous territory', 'The next territory');

        $this->assertEquals(3, ReferencedTerritoryHelperAbstract::count());

        /** @var ReferencedTerritory $newReferencedTerritory */
        $newReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $newReferencedTerritoryName));
        $this->assertNotNull($newReferencedTerritory);
        $this->assertEquals(count($newReferencedTerritory->getPrevious()), 1);
        $this->assertEquals(count($newReferencedTerritory->getNext()), 1);

        $previousReferencedTerritory = $this->getRepository()->findOneBy(array('name' => $previousReferencedTerritoryName));
        $this->assertNotNull($previousReferencedTerritory);
        $this->assertEquals(count($previousReferencedTerritory->getPrevious()), 0);
        $this->assertEquals(count($previousReferencedTerritory->getNext()), 0);

    }
}
 