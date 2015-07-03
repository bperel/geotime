<?php

namespace geotime\Test;

use Doctrine\ORM\EntityRepository;
use geotime\helpers\ModelHelper;
use geotime\models\mariadb\ReferencedTerritory;
use geotime\models\mariadb\Territory;
use geotime\NaturalEarthImporter;
use geotime\Test\Helper\MariaDbTestHelper;

include_once('MariaDbTestHelper.php');
include_once('ReferencedTerritoryHelper.php');
include_once('TerritoryHelper.php');
include_once('NaturalEarthImporter.php');

class TerritoryTest extends MariaDbTestHelper {

    static function setUpBeforeClass() {
        // Territory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        // Territory::$log->info(__CLASS__." tests ended");
    }

    public function setUp() {
        parent::setUp();
        $all = $this->getRepository()->findAll();
        $neImport = new NaturalEarthImporter();
        $neImport->import('test/phpunit/_data/countries.json');
    }

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        return ModelHelper::getEm()->getRepository(Territory::CLASSNAME);
    }

    /**
     * @return EntityRepository
     */
    public function getReferencedTerritoryRepository()
    {
        return ModelHelper::getEm()->getRepository(ReferencedTerritory::CLASSNAME);
    }

    public function testGetTerritoryArea() {

        /** @var ReferencedTerritory $japanReferencedTerritory */
        $japanReferencedTerritory = $this->getReferencedTerritoryRepository()->findOneBy(array('name' => 'Japan'));

        /** @var Territory $japan */
        $japan = $this->getRepository()->findOneBy(array('referencedTerritory' => $japanReferencedTerritory));
        $this->assertEquals(405267, $japan->getArea());

        /** @var ReferencedTerritory $luxembourgReferencedTerritory */
        $luxembourgReferencedTerritory = $this->getReferencedTerritoryRepository()->findOneBy(array('name' => 'Luxembourg'));

        /** @var Territory $luxembourg */
        $luxembourg = $this->getRepository()->findOneBy(array('referencedTerritory' => $luxembourgReferencedTerritory));
        $this->assertEquals(2412, $luxembourg->getArea());
    }
}
 