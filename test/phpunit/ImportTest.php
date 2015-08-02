<?php
namespace geotime\Test;

use geotime\helpers\MapHelper;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\SparqlEndpointHelper;
use geotime\helpers\TerritoryHelper;
use geotime\Import;
use geotime\models\mariadb\Map;
use geotime\models\mariadb\Territory;
use geotime\Test\Helper\MariaDbTestHelper;
use geotime\Util;

class ImportTest extends MariaDbTestHelper {

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

        Util::$cache_dir_svg = "test/phpunit/cache/svg/";
        Util::$cache_dir_json = "test/phpunit/cache/json/";
        Util::$data_dir_sparql = "test/phpunit/_fixtures/sparql/";

        copy("test/phpunit/_fixtures/json/Former Empires.json", Util::$cache_dir_json."Former Empires.json");
    }

    static function tearDownAfterClass() {
        Import::$log->info(__CLASS__." tests ended !");

        unlink(Util::$cache_dir_json."Former Empires.json");
    }

    public function setUp() {

        parent::setUp();

        $this->mock = $this->getMockBuilder('geotime\Import')
            ->setMethods(array('getCommonsImageXMLInfo', 'getSparqlQueryResultsFromQuery'))
            ->getMock();

        $this->import = new Import();

        SparqlEndpointHelper::importFromJson("test/phpunit/_data/sparqlEndpoints.json");
    }

    /* Fixtures */

    private function setCommonsXMLFixture($fixtureFilename) {
        $response = new \SimpleXMLElement(file_get_contents('test/phpunit/_fixtures/xml/'.$fixtureFilename));

        $this->mock->expects($this->any())
            ->method('getCommonsImageXMLInfo')
            ->will($this->returnValue($response));
    }

    private function setFetchSvgUrlsFixture() {
        $urls = json_decode(file_get_contents('test/phpunit/_fixtures/urls.json'));

        $this->mock->expects($this->any())
            ->method('getCommonsURLs')
            ->will($this->returnValue($urls));
    }

    private function setSparqlJsonFixture($fixtureFilename) {
        $response = file_get_contents('test/phpunit/_fixtures/json/'.$fixtureFilename);

        $this->mock->expects($this->any())
            ->method('getSparqlQueryResultsFromQuery')
            ->will($this->returnValue($response));
    }

    /* Util methods for tests */

    /**
     * @param string $fileName
     * @param \DateTime $uploadDate
     * @return Map
     */
    private function generateAndSaveSampleMap($fileName, $uploadDate) {

        $map = new Map();
        $map->setFileName($fileName);
        $map->setUploadDate($uploadDate);

        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();

        return MapHelper::findOneByFileName($fileName);
    }

    /* Tests */

    public function testGetInstance() {
        $this->assertEquals(new Import(), Import::instance());
    }

    public function testGetSparqlHttpParametersWithQuery() {
        $parameters = array(
            array('test' => 'value'),
            array('queryContainer' => 'The query should be there : <<query>>')
        );
        $parametersWithQuery = $this->import->getSparqlHttpParametersWithQuery($parameters, 'A query');

        $expectedParametersWithQuery = array(
            'test' => 'value',
            'queryContainer' => 'The query should be there : A query'
        );
        $this->assertEquals($expectedParametersWithQuery, $parametersWithQuery);
    }
/*
    public function testGetMapsFromSparqlQueryCachedJson() {
        $sparqlQuery = file_get_contents(Util::$data_dir_sparql.'Former Empires.sparql');

        $maps = $this->mock->storeMapsFromSparqlQuery(
            $sparqlQuery,
            Util::$cache_dir_json."Former Empires.json"
        );

        $this->assertEquals(2, count($maps));
    }

    public function testGetMapsFromSparqlQueryExistingMap() {
        $sparqlQuery = file_get_contents(Util::$data_dir_sparql.'Former Empires.sparql');

        $this->setSparqlJsonFixture('Former Empires.json');

        $this->generateAndSaveSampleMap('German Empire 1914.svg', new \DateTime());

        $maps = $this->mock->storeMapsFromSparqlQuery($sparqlQuery);
        $this->assertEquals(2, count($maps));

        //   The first map already exists, that's why its ID is not null
        $firstMap = $maps[key($maps)];
        $this->assertNotNull($firstMap->getId());
    }

    public function testGetMapsFromSparqlQuery() {
        $sparqlQuery = file_get_contents(Util::$data_dir_sparql.'Former Empires.sparql');

        $this->setSparqlJsonFixture('Former Empires.json');
        $this->setFetchSvgUrlsFixture();

        $maps = $this->mock->storeMapsFromSparqlQuery($sparqlQuery);
        $this->assertEquals(2, count($maps));

        $firstMap = current($maps);
        $this->assertNull($firstMap->getId());
        $this->assertEquals('German Empire 1914.svg', $firstMap->getFileName());
        $this->assertEquals(1, count($firstMap->getTerritories()));

        $territories = $firstMap->getTerritories();
        $this->assertEquals(new \DateTime('1871-01-18'), $territories[0]->getStartDate());
        $this->assertEquals(new \DateTime('1918-11-18'), $territories[0]->getEndDate());

        $secondMap = next($maps);
        $this->assertNull($secondMap->getId());
        $this->assertEquals('Frankish Empire 481 to 814-en.svg', $secondMap->getFileName());
        $this->assertEquals(1, count($secondMap->getTerritories()));

        $territories = $secondMap->getTerritories();
        $this->assertEquals(new \DateTime('0003-01-01T00:00:00+02:00'), $territories[0]->getStartDate());
        $this->assertEquals(new \DateTime('0843-01-01T00:00:00+02:00'), $territories[0]->getEndDate());
    }
*/
    public function testStoreTerritoriesFromSparqlQuery()
    {
        $sparqlQuery = file_get_contents(Util::$data_dir_sparql.'Former Empires.sparql');
        $this->setSparqlJsonFixture('Former countries in Europe.json');

        $territories = $this->mock->storeTerritoriesFromSparqlQuery($sparqlQuery);
        $this->assertEquals(1, count($territories));

        $this->assertEquals(1, TerritoryHelper::count());
    }

    public function testGetAlreadyExistingTerritoriesFromSparqlQuery()
    {
        $sparqlQuery = file_get_contents(Util::$data_dir_sparql.'Former Empires.sparql');
        $this->setSparqlJsonFixture('Former countries in Europe.json');

        $territories = $this->mock->storeTerritoriesFromSparqlQuery($sparqlQuery);
        $this->assertEquals(1, count($territories));
        $this->assertEquals(1, TerritoryHelper::count());

        $territories = $this->mock->storeTerritoriesFromSparqlQuery($sparqlQuery);
        $this->assertEquals(0, count($territories));
        $this->assertEquals(1, TerritoryHelper::count());
    }

    public function testGetTerritoriesFromSparqlQueryInvalidQuery() {
        $this->setSparqlJsonFixture('invalid.json');

        ob_start();
        $territories = $this->mock->storeTerritoriesFromSparqlQuery('');
        $echoOutput = ob_get_clean();

        $this->assertEmpty($territories);
        $this->assertRegExp('# - ERROR - #', $echoOutput);
    }

    public function testGetInaccessibleImageURL()
    {
        $this->setCommonsXMLFixture('inaccessible.png.xml');

        ob_start();
        $imageInfos = $this->mock->getCommonsImageInfos('inaccessible.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageInfos);
        $this->assertRegExp('# - ERROR - #', $echoOutput);
    }

    public function testGetNonexistantImageURL()
    {
        $this->setCommonsXMLFixture('nonexistent.png.xml');

        ob_start();
        $imageInfos = $this->mock->getCommonsImageInfos('nonexistent.png');
        $echoOutput = ob_get_clean();

        $this->assertNull($imageInfos);
        $this->assertRegExp('# - WARN - #', $echoOutput);
    }

    public function testGetImageInfo()
    {
        $this->setCommonsXMLFixture('Wiki-commons.png.xml');

        $imageInfos = $this->mock->getCommonsImageInfos('Wiki-commons.png');

        $this->assertNotNull($imageInfos);
        $this->assertEquals('https://upload.wikimedia.org/wikipedia/commons/7/79/Wiki-commons.png', $imageInfos['url']);
        $this->assertEquals(new \DateTime('2006-10-02T01:19:24Z'), $imageInfos['uploadDate']);
    }

    /* This test uses the live toolserver */
    public function testGetCommonsImageInfos() {
        $xmlInfo = $this->import->getCommonsImageXMLInfo('Wiki-commons.png');
        $fixtureXML = new \SimpleXMLElement(file_get_contents('test/phpunit/_fixtures/xml/Wiki-commons.png.xml'));
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
        $mapFileName = 'testImage.svg';
        $map = MapHelper::generateAndSaveReferences('testImage.svg', '1980-01-02', '1991-02-03');
        $hasCreatedMap = $this->import->fetchAndStoreImage($map, $mapFileName, new \DateTime('2013-07-25T17:33:40Z'));

        $this->assertTrue($hasCreatedMap);
        $this->assertEquals(1, MapHelper::count());

        /** @var Map $storedMap */
        $storedMap = MapHelper::findAll()[0];
        $territories = $storedMap->getTerritories();
        $this->assertEquals(new \DateTime('1980-01-02'), $territories[0]->getStartDate());
        $this->assertEquals(new \DateTime('1991-02-03'), $territories[0]->getEndDate());
    }

    public function testFetchAndStoreImageExistingMap() {
        $uploadDate = new \DateTime('2013-01-02T03:04:05Z');

        $mapFileName = 'testImage.svg';
        $map = $this->generateAndSaveSampleMap($mapFileName, $uploadDate);

        $hasCreatedMap = $this->import->fetchAndStoreImage($map, $mapFileName, $uploadDate);

        $this->assertFalse($hasCreatedMap);
        $this->assertEquals(1, MapHelper::count());
    }

    public function testFetchAndStoreImageOutdatedMap()
    {
        $storedMapUploadDate = new \DateTime('2012-01-02T03:04:05Z');
        $uploadDate = new \DateTime('2013-01-02T03:04:05Z');

        $mapFileName = 'testImage.svg';
        $map = $this->generateAndSaveSampleMap($mapFileName, $storedMapUploadDate);

        $hasCreatedMap = $this->import->fetchAndStoreImage($map, $mapFileName, $uploadDate);

        $this->assertTrue($hasCreatedMap);
        $this->assertEquals(1, MapHelper::count());
    }

    public function testImportTerritoriesFromSparqlQuery() {
        $resultFile = 'Former Empires with previous and next.json';
        $this->setSparqlJsonFixture($resultFile);
        $this->mock->importReferencedTerritoriesFromQuery($resultFile.'.sparql', $resultFile, true);

        $this->assertEquals(12, ReferencedTerritoryHelper::count());

        /** @var Territory[] $initialTerritories */
        $initialTerritories = TerritoryHelper::findWithPeriod();
        $referencedTerritoryNames = array_map(function(Territory $value) {
            return $value->getReferencedTerritory()->getName();
        }, $initialTerritories);
        $this->assertEquals(array('Abbasid Caliphate', 'Alania'), $referencedTerritoryNames);

        /** @var \geotime\models\mariadb\ReferencedTerritory $firstTerritory */
        $firstTerritory = ReferencedTerritoryHelper::findOneByName('Abbasid Caliphate');
        $firstTerritoryPreviousTerritories = $firstTerritory->getPrevious();
        $this->assertEquals(4, count($firstTerritoryPreviousTerritories));
        $this->assertEquals('Dabuyid dynasty', $firstTerritoryPreviousTerritories[0]->getName());

        $firstTerritoryNextTerritories = $firstTerritory->getNext();
        $this->assertEquals(5, count($firstTerritoryNextTerritories));
        $this->assertEquals('Aghlabids', $firstTerritoryNextTerritories[0]->getName());

        /** @var \geotime\models\mariadb\ReferencedTerritory $secondTerritory */
        $secondTerritory = ReferencedTerritoryHelper::findOneByName('Alania');
        $secondTerritoryPreviousTerritories = $secondTerritory->getPrevious();
        $this->assertEquals(0, count($secondTerritoryPreviousTerritories));
    }
}