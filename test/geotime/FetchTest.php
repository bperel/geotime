<?php
namespace geotime\Test;

use PHPUnit_Framework_TestCase;
use geotime\Fetch;
use geotime\Database;
use geotime\models\CriteriaGroup;

class FetchTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Fetch
     */
    var $mock;

    /**
     * @var \geotime\Fetch
     */
    var $f;

    protected function setUp() {
        $this->mock = $this->getMockBuilder('geotime\Fetch')
            ->setMethods(array('getCommonsImageXMLInfo', 'getSparqlQueryResults', 'getCommonsURLs'))
            ->getMock();

        $this->f = new Fetch();

        Database::connect("geotime_test");

        CriteriaGroup::drop();
        CriteriaGroup::importFromJson("test/geotime/data/criteriaGroups.json");
    }

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

    /* Tests */

    public function testImportFromJson() {
        CriteriaGroup::drop();

        $this->assertEquals(0, CriteriaGroup::count());
        CriteriaGroup::importFromJson("test/geotime/data/criteriaGroups.json");
        $this->assertEquals(1, CriteriaGroup::count());
    }

    public function testInitCriteriaGroups() {
        $this->assertEmpty(Fetch::$criteriaGroups);
        Fetch::initCriteriaGroups();
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

    public function testFetchSvgUrlsFromSparqlResults() {
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
        $xmlInfo = new \SimpleXMLElement($this->f->getCommonsImageXMLInfo('Wiki-commons.png'));
        $fixtureXML = new \SimpleXMLElement(file_get_contents('test/geotime/fixtures/xml/Wiki-commons.png.xml'));
        $this->assertEquals(trim($fixtureXML->file->urls->file), trim($xmlInfo->file->urls->file));
    }
} 