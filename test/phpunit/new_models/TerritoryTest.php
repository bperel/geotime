<?php

namespace geotime\Test;

use Doctrine\ORM\EntityRepository;
use geotime\helpers\ModelHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\mariadb\ReferencedTerritory;
use geotime\models\mariadb\Territory;
use geotime\NaturalEarthImporter;
use geotime\Test\Helper\EntityTestHelper;

include_once('EntityTestHelper.php');
class TerritoryTest extends EntityTestHelper {

    static function setUpBeforeClass() {
        // Territory::$log->info(__CLASS__." tests started");
    }

    static function tearDownAfterClass() {
        // Territory::$log->info(__CLASS__." tests ended");
    }

    public function setUp() {
        parent::setUp();
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

    public function testCountForPeriod() {
        $territory = new Territory(null, true, null, 0, null, new \DateTime('2000-01-01'), new \DateTime('2004-12-31'));
        ModelHelper::getEm()->persist($territory);

        $territory2 = new Territory(null, true, null, 0, null, new \DateTime('2003-01-01'), new \DateTime('2006-01-01'));
        ModelHelper::getEm()->persist($territory2);

        ModelHelper::getEm()->flush();

        $this->assertEquals(2, TerritoryHelper::countForPeriod(new \DateTime('2002-01-01'),new \DateTime('2004-01-01')));
        $this->assertEquals(2, TerritoryHelper::countForPeriod(new \DateTime('1999-01-01'),new \DateTime('2020-01-01')));
        $this->assertEquals(1, TerritoryHelper::countForPeriod(new \DateTime('1999-01-01'),new \DateTime('2000-01-01')));
        $this->assertEquals(0, TerritoryHelper::countForPeriod(new \DateTime('2006-01-02'),new \DateTime('2010-01-01')));
    }
}
 