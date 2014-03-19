<?php
namespace geotime\Test;

use PHPUnit_Framework_TestCase;

use geotime\models\CriteriaGroup;
use geotime\models\Criteria;

use geotime\Import;
use geotime\Database;

class ImportTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Import
     */
    var $mock;

    /**
     * @var \geotime\Import
     */
    var $import;

    protected function setUp() {
        $this->mock = $this->getMockBuilder('geotime\Import')
            ->setMethods(array('getCommonsImageXMLInfo', 'getSparqlQueryResults', 'getCommonsURLs'))
            ->getMock();

        $this->import = new Import();

        Database::connect("geotime_test");

        Criteria::drop();
        CriteriaGroup::drop();
        CriteriaGroup::importFromJson("test/geotime/data/criteriaGroups.json");
    }

    protected function tearDown() {
        CriteriaGroup::drop();
        Criteria::drop();
    }

    /* Fixtures */

    private function setCommonsXMLFixture($fixtureFilename) {
        $response = file_get_contents('test/geotime/fixtures/xml/'.$fixtureFilename);

        $this->mock->expects($this->any())
            ->method('getCommonsImageXMLInfo')
            ->will($this->returnValue($response));
    }

    private function setFetchSvgUrlsFixture() {
        $urls = json_decode(file_get_contents('test/geotime/fixtures/urls.json'));

        $this->mock->expects($this->any())
            ->method('getCommonsURLs')
            ->will($this->returnValue($urls));
    }

    private function setSparqlJsonFixture($fixtureFilename) {
        $response = file_get_contents('test/geotime/fixtures/json/'.$fixtureFilename);

        $this->mock->expects($this->any())
            ->method('getSparqlQueryResults')
            ->will($this->returnValue($response));
    }

    /* Util methods for tests */

    private function generateSampleCriteriaGroup() {
        $criteria1 = new Criteria(array('key'=>'field1', 'value'=>'value1'));
        $criteria1->save();

        $criteria2 = new Criteria(array('key'=>'field2', 'value'=>'value2'));
        $criteria2->save();

        $c = new CriteriaGroup();
        $c->setSort(array("field1"));
        $c->setCriteriaList(array($criteria1, $criteria2));
        $c->save();

        return CriteriaGroup::one();
    }

    /* Tests */

    public function testImportFromJson() {
        CriteriaGroup::drop();

        $this->assertEquals(0, CriteriaGroup::count());
        CriteriaGroup::importFromJson('test/geotime/data/criteriaGroups.json');
        $this->assertEquals(1, CriteriaGroup::count());
    }

    public function testImportFromJsonInvalidFile() {
        try {
            CriteriaGroup::importFromJson('test\geotime\data\criteriaGroups.json');
            $this->fail();
        }
        catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('Invalid file name for JSON import', $e->getMessage());
        }
    }

    public function testInitCriteriaGroups() {
        $this->assertEmpty(Import::$criteriaGroups);
        Import::initCriteriaGroups();
        $this->assertEquals(1, CriteriaGroup::count());

    }

    /* This test uses the live Dbpedia results */
    /*
    public function testGetSparqlLiveResults() {
        $criteriaGroup = array(
            "fields" => array(
                "<http://purl.org/dc/terms/subject>"            => "<http://dbpedia.org/resource/Category:Former_empires>",
                "<http://dbpedia.org/ontology/foundingDate>"    => "?date1",
                "<http://dbpedia.org/ontology/dissolutionDate>" => "?date2",
                "<http://dbpedia.org/property/imageMap>"        => "?imageMap"
            ),
            "sort" => array(
                "DESC(?date1)"
            )
        );
        $this->assertJson($this->f->getSparqlQueryResults($criteriaGroup));
    }
    */

    public function testBuildSparqlQuery() {
        CriteriaGroup::drop();
        $query = $this->import->buildSparqlQuery($this->generateSampleCriteriaGroup());

        $this->assertEquals("SELECT * WHERE { ?e field1 value1 . ?e field2 value2} ORDER BY field1", $query);
    }

    public function testFetchSvgUrlsFromSparqlResults() {
        CriteriaGroup::drop();
        $criteriaGroup = $this->generateSampleCriteriaGroup();

        $this->setSparqlJsonFixture('Former Empires.json');
        $this->setFetchSvgUrlsFixture();

        $svgUrls = $this->mock->fetchSvgFilenamesFromCriteriaGroup($criteriaGroup);

        $this->assertEquals(1, count($svgUrls));
    }

    public function testGetNonexistantImageURL()
    {
        $this->setCommonsXMLFixture('nonexistent.png.xml');

        ob_start();
        $imageURL = $this->mock->getCommonsImageURL('nonexistent.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageURL);
        $this->assertStringStartsWith('<b>Error', $echoOutput);
    }
    public function testGetImageURL()
    {
        $this->setCommonsXMLFixture('Wiki-commons.png.xml');

        ob_start();
        $imageURL = $this->mock->getCommonsImageURL('Wiki-commons.png');
        $echoOutput = ob_get_clean();

        $this->assertNotEmpty($imageURL);
        $this->assertEmpty($echoOutput);
    }

    /* This test uses the live toolserver */
    public function testGetCommonsImageURL() {
        $xmlInfo = new \SimpleXMLElement($this->import->getCommonsImageXMLInfo('Wiki-commons.png'));
        $fixtureXML = new \SimpleXMLElement(file_get_contents('test/geotime/fixtures/xml/Wiki-commons.png.xml'));
        $this->assertEquals(trim($fixtureXML->file->urls->file), trim($xmlInfo->file->urls->file));
    }
} 