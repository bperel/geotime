<?php
namespace geotime\Test;

use geotime\Database;
use geotime\Geotime;
use geotime\Import;
use geotime\models\Criteria;
use geotime\models\CriteriaGroup;
use geotime\models\Map;
use geotime\models\Territory;
use geotime\Util;
use PHPUnit_Framework_TestCase;

class ImportTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Import
     */
    var $mock;

    /**
     * @var \geotime\Import
     */
    var $import;

    static function setUpBeforeClass() {
        Import::$log->info(__CLASS__." tests started");

        Util::$cache_dir_svg = "test/geotime/cache/svg/";
        Util::$cache_dir_json = "test/geotime/cache/json/";

        copy("test/geotime/_fixtures/json/Former Empires.json", Util::$cache_dir_json."Former Empires.json");
    }

    static function tearDownAfterClass() {
        Import::$log->info(__CLASS__." tests ended");

        unlink(Util::$cache_dir_json."Former Empires.json");
    }

    protected function setUp() {
        $this->mock = $this->getMockBuilder('geotime\Import')
            ->setMethods(array('getCommonsImageXMLInfo', 'getSparqlQueryResults', 'getCommonsURLs'))
            ->getMock();

        $this->import = new Import();

        Database::connect(Database::$testDbName);

        Geotime::clean();

        Criteria::drop();
        CriteriaGroup::drop();
        Database::importFromJson("test/geotime/_data/criteriaGroups.json", CriteriaGroup::$collection);
    }

    protected function tearDown() {
        CriteriaGroup::drop();
        Criteria::drop();

        Geotime::clean();
    }

    /* Fixtures */

    private function setCommonsXMLFixture($fixtureFilename) {
        $response = new \SimpleXMLElement(file_get_contents('test/geotime/_fixtures/xml/'.$fixtureFilename));

        $this->mock->expects($this->any())
            ->method('getCommonsImageXMLInfo')
            ->will($this->returnValue($response));
    }

    private function setFetchSvgUrlsFixture() {
        $urls = json_decode(file_get_contents('test/geotime/_fixtures/urls.json'));

        $this->mock->expects($this->any())
            ->method('getCommonsURLs')
            ->will($this->returnValue($urls));
    }

    private function setSparqlJsonFixture($fixtureFilename) {
        $response = file_get_contents('test/geotime/_fixtures/json/'.$fixtureFilename);

        $this->mock->expects($this->any())
            ->method('getSparqlQueryResults')
            ->will($this->returnValue($response));
    }

    /* Util methods for tests */

    /**
     * @param string $name
     * @return CriteriaGroup
     */
    private function generateSampleCriteriaGroup($name='Former empires') {
        $criteria1 = new Criteria(array('key'=>'field1', 'value'=>'value1'));
        $criteria1->save();

        $criteria2 = new Criteria(array('key'=>'field2', 'value'=>'value2'));
        $criteria2->save();

        $optionalCriteria = new Criteria(array('key'=>'field3', 'value'=>'value3'));
        $optionalCriteria->save();

        $c = new CriteriaGroup();
        $c->setName($name);
        $c->setSort(array("field1", "field2"));
        $c->setCriteria(array($criteria1, $criteria2));
        $c->setOptional(array($optionalCriteria));
        $c->save();

        return CriteriaGroup::one();
    }

    /**
     * @param string $fileName
     * @param \MongoDate $uploadDate
     * @return Map
     */
    private function generateAndSaveSampleMap($fileName, $uploadDate) {

        $map = new Map();
        $map->setFileName($fileName);
        $map->setUploadDate($uploadDate);
        $map->save();

        return Map::one();
    }

    /* Tests */

    public function testImportFromJson() {
        CriteriaGroup::drop();

        $this->assertEquals(0, CriteriaGroup::count());
        $nbImportedObjects = Database::importFromJson('test/geotime/_data/criteriaGroups.json', CriteriaGroup::$collection);
        $this->assertEquals(1, CriteriaGroup::count());
        $this->assertEquals(1, $nbImportedObjects);
    }

    public function testImportFromJsonInvalidFile() {
        try {
            Database::importFromJson('test\geotime\data\criteriaGroups.json', CriteriaGroup::$collection);
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
        $this->assertJson($this->import->getSparqlQueryResults($criteriaGroup));
    }
    */

    public function testBuildSparqlQuery() {
        CriteriaGroup::drop();
        $query = $this->import->buildSparqlQuery($this->generateSampleCriteriaGroup());

        $this->assertEquals("SELECT * WHERE { ?e field1 value1 . ?e field2 value2 . OPTIONAL { ?e field3 value3 } } ORDER BY field1 field2", $query);
    }

    public function testGetMapsFromCriteriaGroupCachedJson() {
        $maps = $this->mock->getMapsFromCriteriaGroup(
            new CriteriaGroup(array('name'=>'Former empires')),
            Util::$cache_dir_json."Former Empires.json"
        );

        $this->assertEquals(2, count($maps));
    }

    public function testGetMapsFromCriteriaGroupInvalidJson() {
        $this->setSparqlJsonFixture('invalid.json');

        ob_start();
        $maps = $this->mock->getMapsFromCriteriaGroup(new CriteriaGroup());
        $echoOutput = ob_get_clean();

        $this->assertEmpty($maps);
        $this->assertStringStartsWith('ERROR - ', $echoOutput);
    }

    public function testGetMapsFromCriteriaGroupExistingMap() {
        CriteriaGroup::drop();
        $criteriaGroup = $this->generateSampleCriteriaGroup();

        $this->setSparqlJsonFixture('Former Empires.json');
        $this->setFetchSvgUrlsFixture();

        $this->generateAndSaveSampleMap('German Empire 1914.svg', new \MongoDate());

        $maps = $this->mock->getMapsFromCriteriaGroup($criteriaGroup);
        $this->assertEquals(2, count($maps));

        //   The first map already exists, that's why its ID is not null
        $firstMap = $maps[key($maps)];
        $this->assertNotNull($firstMap->getId());
    }

    public function testGetMapsFromCriteriaGroup() {
        CriteriaGroup::drop();
        $criteriaGroup = $this->generateSampleCriteriaGroup();

        $this->setSparqlJsonFixture('Former Empires.json');
        $this->setFetchSvgUrlsFixture();

        $maps = $this->mock->getMapsFromCriteriaGroup($criteriaGroup);
        $this->assertEquals(2, count($maps));

        $firstMap = current($maps);
        $this->assertNull($firstMap->getId());
        $this->assertEquals('German Empire 1914.svg', $firstMap->getFileName());
        $this->assertEquals(1, count($firstMap->getTerritories()));

        /** @var Territory[] $territories */
        $territories = $firstMap->getTerritories();
        $this->assertNotNull($territories[0]->getPeriod());
        $this->assertEquals(new \MongoDate(strtotime('1871-01-18')), $territories[0]->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime('1918-11-18')), $territories[0]->getPeriod()->getEnd());

        $secondMap = next($maps);
        $this->assertNull($secondMap->getId());
        $this->assertEquals('Frankish Empire 481 to 814-en.svg', $secondMap->getFileName());
        $this->assertEquals(1, count($secondMap->getTerritories()));

        /** @var Territory[] $territories */
        $territories = $secondMap->getTerritories();
        $this->assertNotNull($territories[0]->getPeriod());
        $this->assertEquals(new \MongoDate(strtotime('0002-12-31 23:00:00')), $territories[0]->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime('0842-12-31 23:00:00')), $territories[0]->getPeriod()->getEnd());
    }

    function testGetDatesFromSparqlResultInvalidCriteriaGroup() {
        CriteriaGroup::drop();
        $criteriaGroup = $this->generateSampleCriteriaGroup('Invalid criteria group name');

        $this->setSparqlJsonFixture('Former Empires.json');
        $this->setFetchSvgUrlsFixture();

        $maps = $this->mock->getMapsFromCriteriaGroup($criteriaGroup);

        $this->assertEquals(0, count($maps));
    }

    public function testGetInaccessibleImageURL()
    {
        $this->setCommonsXMLFixture('inaccessible.png.xml');

        ob_start();
        $imageInfos = $this->mock->getCommonsImageInfos('inaccessible.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageInfos);
        $this->assertStringStartsWith('ERROR - ', $echoOutput);
    }

    public function testGetNonexistantImageURL()
    {
        $this->setCommonsXMLFixture('nonexistent.png.xml');

        ob_start();
        $imageInfos = $this->mock->getCommonsImageInfos('nonexistent.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageInfos);
        $this->assertStringStartsWith('WARN - ', $echoOutput);
    }

    public function testGetImageInfo()
    {
        $this->setCommonsXMLFixture('Wiki-commons.png.xml');

        ob_start();
        $imageInfos = $this->mock->getCommonsImageInfos('Wiki-commons.png');
        $echoOutput = ob_get_clean();

        $this->assertEquals('http://upload.wikimedia.org/wikipedia/commons/7/79/Wiki-commons.png', $imageInfos['url']);
        $this->assertEquals(strtotime('2006-10-02T01:19:24Z'), $imageInfos['uploadDate']->sec);
        $this->assertEmpty($echoOutput);
    }

    /* This test uses the live toolserver */
    public function testGetCommonsImageInfos() {
        $xmlInfo = $this->import->getCommonsImageXMLInfo('Wiki-commons.png');
        $fixtureXML = new \SimpleXMLElement(file_get_contents('test/geotime/_fixtures/xml/Wiki-commons.png.xml'));
        $this->assertEquals(trim($fixtureXML->file->urls->file), trim($xmlInfo->file->urls->file));
    }

    public function testGetMultipleImageInfo()
    {
        $fileName = 'Wiki-commons.png.xml';
        $map = new Map();
        $map->setFileName($fileName);

        $this->setCommonsXMLFixture($fileName);

        $infos = $this->mock->getCommonsInfos(array($map));

        $this->assertInternalType('array', $infos);
        $this->assertArrayHasKey($fileName, $infos);
        $this->assertInternalType('array', $infos[$fileName]);
    }

    public function testFetchAndStoreImageNewMap() {
        $map = Map::generateAndSaveReferences('testImage.svg', '1980-01-02', '1991-02-03');
        $hasCreatedMap = $this->import->fetchAndStoreImage($map, new \MongoDate(strtotime('2013-07-25T17:33:40Z')));

        $this->assertTrue($hasCreatedMap);
        $this->assertEquals(1, Map::count());

        /** @var Map $storedMap */
        $storedMap = Map::one();
        $territories = $storedMap->getTerritories();
        $this->assertEquals(new \MongoDate(strtotime('1980-01-02')), $territories[0]->getPeriod()->getStart());
        $this->assertEquals(new \MongoDate(strtotime('1991-02-03')), $territories[0]->getPeriod()->getEnd());
    }

    public function testFetchAndStoreImageExistingMap() {
        $uploadDate = new \MongoDate(strtotime('2013-01-02T03:04:05Z'));

        $map = $this->generateAndSaveSampleMap('testImage.svg', $uploadDate);

        $hasCreatedMap = $this->import->fetchAndStoreImage($map, $uploadDate);

        $this->assertFalse($hasCreatedMap);
        $this->assertEquals(1, Map::count());
    }

    public function testFetchAndStoreImageOutdatedMap() {
        $storedMapUploadDate = new \MongoDate(strtotime('2012-01-02T03:04:05Z'));
        $uploadDate = new \MongoDate(strtotime('2013-01-02T03:04:05Z'));

        $map = $this->generateAndSaveSampleMap('testImage.svg', $storedMapUploadDate);

        $hasCreatedMap = $this->import->fetchAndStoreImage($map, $uploadDate);

        $this->assertTrue($hasCreatedMap);
        $this->assertEquals(1, Map::count());
    }
} 