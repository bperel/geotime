<?php

namespace geotime\Test;
use geotime\helpers\ModelHelper;
use geotime\helpers\SparqlEndpointHelper;
use geotime\models\mariadb\SparqlEndpoint;
use geotime\Test\Helper\MariaDbTestHelper;

include_once('MariaDbTestHelper.php');
include_once('SparqlEndpointHelper.php');

class SparqlEndpointTest extends MariaDbTestHelper {

    static function setUpBeforeClass() {
    }

    static function tearDownAfterClass() {
    }

    public function getRepository()
    {
        return ModelHelper::getEm()->getRepository(SparqlEndpoint::CLASSNAME);
    }

    public function testImportFromJson() {
        SparqlEndpointHelper::importFromJson("test/phpunit/_data/sparqlEndpoints.json");

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(sparqlEndPoint.id)');
        $qb->from(SparqlEndpoint::CLASSNAME,'sparqlEndPoint');

        $count = $qb->getQuery()->getSingleScalarResult();
        $this->assertEquals(1, $count);
    }
}
 