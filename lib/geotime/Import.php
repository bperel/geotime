<?php

namespace geotime;

use geotime\helpers\AbstractEntityHelper;
use geotime\helpers\MapHelper;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\SparqlEndpointHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\mariadb\Map;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Import {

    /** @var \Logger */
    static $log;

    /** @var Import */
    static $instance = null;

    static $sparqlEndpoints = array(
        'dbpedia' => array(
            'rootUrl'  => 'http://dbpedia.org',
            'endpoint' => 'sparql')
    );

    /**
     * An ugly trick to prevent test failures due to static mock methods not working
     * @return Import
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new Import();
        }
        return self::$instance;
    }

    function importReferencedTerritories($contentName, $useCachedJson = true) {
        $this->importReferencedTerritoriesFromQuery(
            file_get_contents(Util::$data_dir_sparql . $contentName . '.sparql'),
            $contentName.'.json',
            $useCachedJson
        );
    }

    function importReferencedTerritoriesFromQuery($sparqlQuery, $fileName, $useCachedJson) {
        $fileNameWithPath = Util::$cache_dir_json . $fileName;
        $this->storeTerritoriesFromSparqlQuery($sparqlQuery, $fileNameWithPath, $useCachedJson);
    }

    /**
     * @param array $parameters
     * @param string $query
     * @return array
     */
    function getSparqlHttpParametersWithQuery($parameters, $query) {
        $replacements = array('<<query>>' => $query);

        array_walk($parameters, function(&$parameter, $key, $replacements) {
            $parameter = str_replace(array_keys($replacements), array_values($replacements), (array) $parameter);
        }, $replacements);

        $parametersFlattened = array();
        foreach($parameters as $parameterSubArray) {
            foreach($parameterSubArray as $key=>$value) {
                $parametersFlattened[$key] = $value;
            }
        }

        return $parametersFlattened;
    }

    /**
     * @param string $sparqlEndpointName
     * @param string $sparqlQuery
     * @return string|null
     */
    function getSparqlQueryResultsFromQuery($sparqlEndpointName, $sparqlQuery) {
        $urlParts = $this->getSparqlRequestUrlPartsFromQuery($sparqlEndpointName, $sparqlQuery);
        if (is_null($urlParts)) {
            return null;
        }
        else {
            list($rootUrlWithEndpoint, $type, $parameters) = $urlParts;
            return Util::curl_get_contents($rootUrlWithEndpoint, $type, $parameters);
        }
    }

    /**
     * @param string $sparqlEndpointName
     * @param string $sparqlQuery
     * @return array|null
     */
    function getSparqlRequestUrlPartsFromQuery($sparqlEndpointName, $sparqlQuery) {

        /** @var \geotime\models\mariadb\SparqlEndpoint $sparqlEndpoint */
        $sparqlEndpoint = SparqlEndpointHelper::findOneByName($sparqlEndpointName);

        if (is_null($sparqlEndpoint)) {
            self::$log->error('Sparql endpoint '.$sparqlEndpointName.' not found');
            return null;
        }

        return array(
            implode('/', array(
                $sparqlEndpoint->getRootUrl(),
                $sparqlEndpoint->getEndpoint())),
            $sparqlEndpoint->getMethod(),
            self::getSparqlHttpParametersWithQuery($sparqlEndpoint->getParameters(), $sparqlQuery)
        );
    }
    /**
     * Create Territory object instances from the results of a Sparql query
     * @param string $sparqlQuery
     * @param string $fileName|null Cache file name to put the results into
     * @param boolean $getFromCachedJson If FALSE, the results will be queried online
     * @return models\mariadb\Territory[]|null
     */
    public function storeTerritoriesFromSparqlQuery($sparqlQuery, $fileName = null, $getFromCachedJson = true) {
        $pageAsJson = $this->getJsonDataFromSparqlQuery(
            $sparqlQuery,
            $fileName,
            $getFromCachedJson
        );
        if (is_null($pageAsJson)) {
            return null;
        }
        return self::storeTerritoriesFromSparqlResults($pageAsJson);
    }

    /**
     * Create map object instances from the results of a Sparql query
     * @param string $sparqlQuery
     * @param string $fileName|null Cache file name to put the results into
     * @param boolean $getFromCachedJson If FALSE, the results will be queried online
     * @return object|null
     */
    public function getJsonDataFromSparqlQuery($sparqlQuery, $fileName = null, $getFromCachedJson = true)
    {
        $cacheFileExists = file_exists($fileName);
        if ($getFromCachedJson && $cacheFileExists) {
            self::$log->info('Using cached JSON file '.$fileName);
            $pageAsJson = json_decode(file_get_contents($fileName));
        }
        else {
            if ($getFromCachedJson && !$cacheFileExists) {
                self::$log->warn('Requested cached JSON file '.$fileName.' doesn\'t exist, retrieving Sparql results online');
            }
            $start = microtime(true);

            $pageAsJson = Util::getStringAsJson(
                $this->getSparqlQueryResultsFromQuery('Dbpedia', $sparqlQuery),
                $fileName
            );

            if (!is_null($pageAsJson)) {
                $end = microtime(true);
                $timeSpent = (intval($end - $start)) / 1000;
                self::$log->info('Retrieved Sparql results in ' . $timeSpent . 's.');
            }
        }
        return $pageAsJson;
    }

    /**
     * Create Territory object instances from a JSON-formatted SPARQL page
     * @param object $pageAsJson
     * @return models\mariadb\Territory[]
     */
    public function storeTerritoriesFromSparqlResults($pageAsJson)
    {
        $territories = array();
        $skippedTerritoriesCount = 0;
        $maps = array();
        $skippedMapsCount = 0;

        AbstractEntityHelper::setFlushMode(false);

        foreach ($pageAsJson->results->bindings as $i=>$result) {
            $territoryName = $result->name->value;

            if (is_null(ReferencedTerritoryHelper::findOneByName($territoryName)) || in_array($territoryName, $territories)) {
                $referencedTerritory = ReferencedTerritoryHelper::buildAndSaveFromObject($result);
                $territory = TerritoryHelper::buildFromObjectAndReferencedTerritory($referencedTerritory, $result);
                $territories[] = $territoryName;

                if (isset($result->imageMap)) {
                    $mapFileName = $result->imageMap->value;
                    if (Util::isSvg($mapFileName)) {
                        $map = MapHelper::findOneByFileName($mapFileName, $maps);
                        if (is_null($map)) {
                            $map = MapHelper::buildAndSaveWithTerritoryFromObject($mapFileName, $territory);
                            $maps[$mapFileName] = $map;
                        }
                        else if ($map !== false) { // If FALSE, an error occurred for this map
                            self::$log->debug('Map ' . $mapFileName . ' already exists, skipping');
                            $skippedMapsCount++;
                        }
                    }
                    else {
                        self::$log->debug('Map ' . $mapFileName . ' is not an SVG, ignoring');
                    }
                }
            }
            else {
                self::$log->debug('Referenced territory '.$territoryName.' already exists, skipping');
                $skippedTerritoriesCount++;
            }
        }

        AbstractEntityHelper::setFlushMode(true);
        AbstractEntityHelper::flush();

        self::$log->info('Referenced territories importation done.');
        self::$log->info(count($territories). ' territories were stored.');
        if ($skippedTerritoriesCount > 0) {
            self::$log->info($skippedTerritoriesCount. ' territories were skipped because they already exist.');
        }
        self::$log->info(count($maps). ' maps were stored.');
        if ($skippedMapsCount > 0) {
            self::$log->info($skippedMapsCount. ' maps were skipped because they already exist.');
        }
        return $territories;
    }

    /**
     * Get the images' Wikimedia Commons URLs and upload dates
     * @param \geotime\models\mariadb\Map[] $maps
     * @return array An associative array in the form 'name'=>['url'=>url, 'uploadDate'=>uploadDate]
     */
    public function getCommonsInfos($maps) {
        $imageInfos = array();
        foreach($maps as $map) {
            $fileName = $map->getFileName();
            $imageInfos[$fileName] = $this->getCommonsImageInfos($fileName);
        }

        return $imageInfos;
    }

    /**
     * Get the Wikimedia Commons URL of an image
     * @param string $imageMapFullName
     * @return array|null
     */
    function getCommonsImageInfos($imageMapFullName) {
        $xmlFormatedPage = $this->getCommonsImageXMLInfo($imageMapFullName);
        if (is_null($xmlFormatedPage)) {
            return null;
        }
        if (isset($xmlFormatedPage->error)) {
            $firstLevelChildren = (array) $xmlFormatedPage->children();
            $error = $firstLevelChildren['error'];
            if (strpos($error, 'File does not exist') !== false) {
                self::$log->warn($error);
            }
            else {
                self::$log->error($error);
            }
            return null;
        }
        else {
            return array(
                'url'=>trim($xmlFormatedPage->file->urls->file),
                'uploadDate'=>new \DateTime($xmlFormatedPage->file->upload_date)
            );
        }
    }

    /**
     * Get the informations about a Wikimedia Commons image
     * @param string $imageMapFullName
     * @return \SimpleXMLElement|null
     */
    function getCommonsImageXMLInfo($imageMapFullName) {
        $url = "http://tools.wmflabs.org/magnus-toolserver/commonsapi.php";
        $contents = Util::curl_get_contents($url, "GET", array("image" => $imageMapFullName));
        if ($contents === false) {
            return null;
        }
        else {
            self::$log->info('Successfully retrieved XML file for image '.$imageMapFullName);
        }
        return new \SimpleXMLElement($contents);
    }

    /**
     * @param $imageUploadDate \DateTime
     * @param $existingMap Map
     * @return bool
     */
    function isSvgToBeDownloaded($imageUploadDate, $existingMap) {
        if (is_null($existingMap->getUploadDate())) {
            self::$log->info('SVG file is not in cache and will be retrieved : '.$existingMap->getFileName());
            return true;
        }
        else if ($existingMap->getUploadDate() !== $imageUploadDate) {
            self::$log->info('SVG file is already in cache but outdated, it will be retrieved again : ' . $existingMap->getFileName());
            return true;
        }
        else {
            self::$log->info('SVG file is already in cache and up-to-date : '.$existingMap->getFileName());
            return false;
        }
    }

    /**
     * @param \geotime\models\mariadb\Map $map
     * @param string $imageMapFullName
     * @param \DateTime $imageMapUploadDate
     * @param string $imageMapUrl NULL if we want the Map object to be stored but not the file to be retrieved
     * @return boolean TRUE if a new map has been stored, FALSE if we keep the existing one or if an error occurred
     */
    function fetchAndStoreImage($map, $imageMapFullName, $imageMapUploadDate, $imageMapUrl = null) {
        // Check if map exists in DB
        // If the retrieved image upload date is the same as the stored map, we keep the map in DB
        if (!is_null($imageMapUploadDate)) {
            if (!$this->isSvgToBeDownloaded($imageMapUploadDate, $map)) {
                return false;
            }
        }
        $svgRetrievalSuccess = null;
        if (is_null($imageMapUrl)
         || ($svgRetrievalSuccess = Util::fetchSvgWithThumbnail($imageMapUrl, $imageMapFullName, $map->getFileName()))) {
            $map->setUploadDate($imageMapUploadDate);
            ModelHelper::getEm()->persist($map);
            ModelHelper::getEm()->flush();
        }
        return is_null($svgRetrievalSuccess) || $svgRetrievalSuccess === true;
    }
}

Import::$log = Logger::getLogger("main");
$rootlogger = Import::$log->getParent();
$fileAppenderName = "MyFileAppender";
$appender = new \LoggerAppenderRollingFile($fileAppenderName);
$appender->setFile("/var/www/html/geotime/geotime.log");
$appenderlayout = new \LoggerLayoutPattern();
$pattern = '%date{Y-m-d H:i:s,u} [%logger] %F(%M):%L - %message%newline';
$appenderlayout->setConversionPattern($pattern);
$appender->setLayout($appenderlayout);
$appender->activateOptions();

$rootlogger->addAppender($appender);
?>
