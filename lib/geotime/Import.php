<?php

namespace geotime;

use geotime\models\Criteria;
use geotime\models\CriteriaGroup;
use geotime\models\Map;
use geotime\models\SparqlEndpoint;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Import {

    /** @var \Purekid\Mongodm\Collection */
    static $criteriaGroups;

    /** @var \Logger */
    static $log;

    static $sparqlEndpoints = array(
        'dbpedia' => array(
            'rootUrl'  => 'http://dbpedia.org',
            'endpoint' => 'sparql')
    );

    static function initCriteriaGroups() {
        if (!isset(self::$criteriaGroups)) {
            self::$criteriaGroups = CriteriaGroup::find();
            self::$log->info(self::$criteriaGroups->count()." criteria groups found");
        }
    }

    function execute() {

        $importStartTime = time();
        self::$log->info('Starting SVG and JSON importation.');

        self::initCriteriaGroups();

        /** @var CriteriaGroup $criteriaGroup */
        foreach(self::$criteriaGroups as $criteriaGroup) {
            $criteriaGroupName = $criteriaGroup->getName();
            $query_criteriaGroup_is_cached = array( "criteria" => $criteriaGroupName );
            $cached_criteria_group = Criteria::find( $query_criteriaGroup_is_cached );

            $fileName = Util::$cache_dir_json . $criteriaGroupName . ".json";

            if ($cached_criteria_group->count() === 0 || !file_exists($fileName)) {

                $maps = $this->getMapsFromCriteriaGroup($criteriaGroup, $fileName);
                $svgInfos = $this->getCommonsInfos($maps);

                foreach ($svgInfos as $imageMapFullName => $imageMapUrlAndUploadDate) {
                    $currentMap = $maps[$imageMapFullName];

                    // The map image couldn't be retrieved => the Map object that we started to fill and its references are deleted
                    if (is_null($imageMapUrlAndUploadDate)) {
                        $currentMap->deleteReferences();
                    }
                    else {
                        $imageMapUrl = $imageMapUrlAndUploadDate['url'];
                        $imageMapUploadDate = $imageMapUrlAndUploadDate['uploadDate'];
                        $this->fetchAndStoreImage($currentMap, $imageMapFullName, $imageMapUploadDate, $imageMapUrl);
                    }
                }
            }
        }

        $importEndTime = time();
        self::$log->info('SVG and JSON importation done in '.($importEndTime-$importStartTime).'s.');
    }

    /**
     * @param array $parameters
     * @param string $query
     * @return array
     */
    function getSparqlHttpParametersWithQuery($parameters, $query) {
        $replacements = array('<<query>>' => $query);

        array_walk($parameters, function(&$parameter, $key, $replacements) {
            $parameter = str_replace(array_keys($replacements), array_values($replacements), $parameter);
        }, $replacements);

        return $parameters;
    }

    /**
     * @param string $sparqlEndpointName
     * @param CriteriaGroup $criteriaGroup
     * @return string
     */
    function getSparqlQueryResults($sparqlEndpointName, CriteriaGroup $criteriaGroup) {
        list($rootUrlWithEndpoint, $type, $parameters) = $this->getSparqlRequestUrlParts($sparqlEndpointName, $criteriaGroup);
        return Util::curl_get_contents($rootUrlWithEndpoint, $type, $parameters);
    }

    /**
     * @param string $sparqlEndpointName
     * @param CriteriaGroup $criteriaGroup
     * @return string
     */
    function getSparqlRequestUrlParts($sparqlEndpointName, CriteriaGroup $criteriaGroup) {

        /** @var SparqlEndpoint $sparqlEndpoint */
        $sparqlEndpoint = SparqlEndpoint::one(array('name' => $sparqlEndpointName));

        if (is_null($sparqlEndpoint)) {
            self::$log->error('Sparql endpoint '.$sparqlEndpointName.' not found');
            return array();
        }

        $query = $this->buildSparqlQuery($criteriaGroup);

        return array(
            implode('/', array(
                $sparqlEndpoint->getRootUrl(),
                $sparqlEndpoint->getEndpoint())),
            $sparqlEndpoint->getMethod(),
            $this->getSparqlHttpParametersWithQuery($sparqlEndpoint->getParameters(), $query)
        );
    }

    /**
     * @param CriteriaGroup $criteriaGroup
     * @return string
     */
    function buildSparqlQuery(CriteriaGroup $criteriaGroup) {
        $criteriaStrings = array();
        $criteriaOptionalStrings = array();

        foreach($criteriaGroup->getCriteria() as $criteria) {
            $criteriaStrings[]= implode(" ", array("?e", $criteria->getKey(), $criteria->getValue()));
        }
        foreach($criteriaGroup->getOptional() as $criteria) {
            $criteriaOptionalStrings[]= implode(" ", array("?e", $criteria->getKey(), $criteria->getValue()));
        }

        $query = "SELECT * WHERE "
                ."{ "
                .implode(" . ", $criteriaStrings);

        if (count($criteriaOptionalStrings) > 0) {
            $query.=" . OPTIONAL { ".implode(" . ", $criteriaOptionalStrings)." } ";
        }

        $query.="} ";

        $sort = $criteriaGroup->getSort();
        if (count($sort) > 0) {
            $query.="ORDER BY ".implode(" ", $sort);
        }

        return $query;
    }

    /**
     * Create Map object instances from the results of a criteria group
     * @param CriteriaGroup $criteriaGroup
     * @param string $fileName
     * @return Map[]
     */
    public function getMapsFromCriteriaGroup(CriteriaGroup $criteriaGroup, $fileName = null)
    {
        if (!is_null($fileName) && file_exists($fileName)) {
            self::$log->info('Using cached JSON file '.$fileName);
            $pageAsJson = json_decode(file_get_contents($fileName));
        }
        else {
            $page = $this->getSparqlQueryResults('Dbpedia', $criteriaGroup);
            $pageAsJson = json_decode($page);

            if (is_null($pageAsJson)) {
                self::$log->error('Cannot decode JSON file '.$fileName);
                return array();
            }

            if (!is_null($fileName)) {
                if (false !== file_put_contents($fileName, $page)) {
                    self::$log->info('Successfully stored JSON file '.$fileName);
                }
            }
        }
        return $this->getMapsFromSparqlResults($pageAsJson, $criteriaGroup);
    }

    /**
     * @param \stdClass $result
     * @param CriteriaGroup $criteriaGroup
     * @return \stdClass Object with start and end dates
     */
    public function getDatesFromSparqlResult($result, $criteriaGroup) {
        switch($criteriaGroup->getName()) {
            case 'Former empires':
                $objectWithDates = new \stdClass();
                if (isset($result->date1_precise)) {
                    $objectWithDates->startDate = $result->date1_precise->value;
                }
                else {
                    $objectWithDates->startDate = $result->date1->value;
                }
                if (isset($result->date2_precise)) {
                    $objectWithDates->endDate = $result->date2_precise->value;
                }
                else {
                    $objectWithDates->endDate = $result->date2->value;
                }
                return $objectWithDates;
            break;

            default:
                self::$log->error('Invalid criteria group : '.$criteriaGroup->getName());
                return null;
        }
    }

    /**
     * Create Map object instances from a JSON-formatted SPARQL page
     * @param object $pageAsJson
     * @param CriteriaGroup $criteriaGroup
     * @return Map[]
     */
    public function getMapsFromSparqlResults($pageAsJson, $criteriaGroup)
    {
        $maps = array();
        foreach ($pageAsJson->results->bindings as $result) {
            $imageMapFullName = Util::cleanupImageName($result->imageMap->value);

            if (strtolower(Util::getImageExtension($imageMapFullName)) === ".svg") {
                $existingMap = Map::one(array('fileName'=>$imageMapFullName));
                if (is_null($existingMap)) {
                    $startAndEndDates = $this->getDatesFromSparqlResult($result, $criteriaGroup);
                    if (is_null($startAndEndDates)) {
                        continue;
                    }
                    else {
                        $map = Map::generateAndSaveReferences($imageMapFullName, $startAndEndDates->startDate, $startAndEndDates->endDate);
                    }
                }
                else {
                    $map = $existingMap;
                }
                $maps[$imageMapFullName]=$map;
            }
        }

        return $maps;
    }

    /**
     * Get the images' Wikimedia Commons URLs and upload dates
     * @param Map[] $maps
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
                'uploadDate'=>new \MongoDate(strtotime($xmlFormatedPage->file->upload_date))
            );
        }
    }

    /**
     * Get the informations about a Wikimedia Commons image
     * @param string $imageMapFullName
     * @return \SimpleXMLElement
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
     * @param Map $map
     * @param string $imageMapFullName
     * @param \MongoDate $imageMapUploadDate
     * @param string $imageMapUrl NULL if we want the Map object to be stored but not the file to be retrieved
     * @return boolean TRUE if a new map has been stored, FALSE if we keep the existing one
     */
    function fetchAndStoreImage($map, $imageMapFullName, $imageMapUploadDate, $imageMapUrl = null) {
        // Check if map exists in DB
        // If the retrieved image upload date is the same as the stored map, we keep the map in DB
        if (!is_null($map->getId())) {
            if ($map->getUploadDate()->sec === $imageMapUploadDate->sec)
            {
                self::$log->info('SVG file is already in cache : '.$map->getFileName());
                return false;
            }
            else {
                self::$log->info('SVG file is outdated and will be retrieved again : '.$map->getFileName());
            }
        }
        if (is_null($imageMapUrl) || Util::fetchSvgWithThumbnail($imageMapUrl, $imageMapFullName, $map->getFileName())) {
            $map->setUploadDate($imageMapUploadDate);
            $map->save();
        }
        return true;
    }
}

Import::$log = Logger::getLogger("main");

?>