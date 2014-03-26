<?php

namespace geotime;

use geotime\models\Criteria;
use geotime\models\CriteriaGroup;
use geotime\models\Map;

use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Import {

    /** @var \Purekid\Mongodm\Collection */
    static $criteriaGroups;

    /** @var \Logger */
    static $log;

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
            $query_criteriaGroup_is_cached = array( "criteriaGroup" => $criteriaGroupName );
            $cached_criteria_group = Criteria::find( $query_criteriaGroup_is_cached );

            $fileName = Util::$cache_dir_json . $criteriaGroupName . ".json";

            if ($cached_criteria_group->count() === 0 || !file_exists($fileName)) {

                $maps = $this->getMapsFromCriteriaGroup($criteriaGroup, $fileName);
                $svgInfos = $this->getCommonsInfos($maps);

                foreach ($svgInfos as $imageMapFullName => $imageMapUrlAndUploadDate) {
                    if (!is_null($imageMapUrlAndUploadDate)) {
                        $imageMapUrl = $imageMapUrlAndUploadDate['url'];
                        $imageMapUploadDate = $imageMapUrlAndUploadDate['uploadDate'];
                        $this->fetchAndStoreImage($maps[$imageMapFullName], $imageMapUploadDate, $imageMapUrl);
                    }
                }
            }
        }

        $importEndTime = time();
        self::$log->info('SVG and JSON importation done in '.($importEndTime-$importStartTime).'s.');
    }

    function getSparqlQueryResults($criteriaGroup) {

        $parameters = array(
            "default-graph-uri" => "http://dbpedia.org",
            "query" => "PREFIX owl: <http://www.w3.org/2002/07/owl#>
                        PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
                        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                        PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                        PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                        PREFIX dc: <http://purl.org/dc/elements/1.1/>
                        PREFIX : <http://dbpedia.org/resource/>
                        PREFIX dbpedia2: <http://dbpedia.org/property/>
                        PREFIX dbpedia: <http://dbpedia.org/>
                        PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                      ".$this->buildSparqlQuery($criteriaGroup),
            "output" => "json"
        );

        return Util::curl_get_contents("http://dbpedia.org/sparql", "POST", $parameters);
    }

    function buildSparqlQuery(CriteriaGroup $criteriaGroup) {
        $criteriaStrings = array();
        foreach($criteriaGroup->getCriteriaList() as $criteria) {
            $criteriaStrings[]= implode(" ", array("?e", $criteria->getKey(), $criteria->getValue()));
        }

        $query = "SELECT * WHERE "
                ."{ "
                .implode(" . ", $criteriaStrings)
                ."} ";

        $sort = $criteriaGroup->getSort();
        if (count($sort) > 0) {
            $query.="ORDER BY ".implode(", ", $sort);
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
        $page = $this->getSparqlQueryResults($criteriaGroup);
        if (!is_null($fileName)) {
            if (false !== file_put_contents($fileName, $page)) {
                self::$log->info('Successfully stored JSON file '.$fileName);
            }
        }
        $pageAsJson = json_decode($page);

        if (is_null($pageAsJson)) {
            self::$log->error('Cannot decode JSON file '.$fileName);
            return array();
        }
        else {
            return $this->getMapsFromSparqlResults($pageAsJson);
        }
    }

    /**
     * Create Map object instances from a JSON-formatted SPARQL page
     * @param object $pageAsJson
     * @return Map[]
     */
    public function getMapsFromSparqlResults($pageAsJson)
    {
        $maps = array();
        foreach ($pageAsJson->results->bindings as $result) {
            $imageMapFullName = Util::cleanupImageName($result->imageMap->value);

            if (strtolower(Util::getImageExtension($imageMapFullName)) === ".svg") {
                $existingMap = Map::one(array('fileName'=>$imageMapFullName));
                if (is_null($existingMap)) {
                    $map = Map::generateAndSaveReferences($imageMapFullName, $result->date1->value, $result->date2->value);
                    $maps[$imageMapFullName]=$map;
                }
                else {
                    $maps[$imageMapFullName]=$existingMap;
                }
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
     * @param \MongoDate $imageMapUploadDate
     * @param string $imageMapUrl NULL if we want the Map object to be stored but not the file to be retrieved
     * @return boolean TRUE if a new map has been stored, FALSE if we keep the existing one
     */
    function fetchAndStoreImage($map, $imageMapUploadDate, $imageMapUrl = null) {
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
        if (is_null($imageMapUrl) || Util::fetchImage($imageMapUrl, $map->getFileName())) {
            $map->setUploadDate($imageMapUploadDate);
            $map->save();
        }
        return true;
    }
}

Import::$log = Logger::getLogger("main");

?>