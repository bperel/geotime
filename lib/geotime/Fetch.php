<?php

namespace geotime;

use geotime\models\Criteria;
use geotime\models\CriteriaGroup;

include_once('Util.php');

class Fetch {

    static $cache_dir_json = "cache/json/";
    static $cache_dir_svg = "cache/svg/";

    /**
     * @var \Purekid\Mongodm\Collection
     */
    static $criteriaGroups;

    static function initCriteriaGroups() {
        if (!isset(self::$criteriaGroups)) {
            self::$criteriaGroups = CriteriaGroup::find();
        }
    }

    function execute($clean) {

        self::initCriteriaGroups();

        if ($clean) {
            Criteria::drop();
            CriteriaGroup::drop();
        }

        foreach(self::$criteriaGroups as $criteriaGroupName => $criteriaGroup) {

            $query_criteriaGroup_is_cached = array( "criteriaGroup" => $criteriaGroupName );
            $cached_criteria_group = Criteria::find( $query_criteriaGroup_is_cached );

            $fileName = self::$cache_dir_json . $criteriaGroupName . ".json";

            if ($cached_criteria_group->count() === 0 || !file_exists($fileName)) {

                $imageNames = $this->fetchSvgFilenamesFromCriteriaGroup($criteriaGroup, $fileName);
                $svgUrls = $this->getCommonsURLs($imageNames);

                foreach ($svgUrls as $imageMapFullName => $imageMapUrl) {
                    $this->fetchImage($imageMapUrl, $imageMapFullName);
                }
            }
        }
    }

    function showCache() {
        $cache = Database::$db->selectCollection("cache");
        $cursor = $cache->find();

        foreach ($cursor as $document) {
            echo "<pre>".print_r($document, true)."</pre>";
        }
    }

    function getSparqlQueryResults($criteriaGroup) {
        $query = "SELECT *
                          WHERE
                            {
                             ";
        $criteriaStrings = array();
        foreach($criteriaGroup["fields"] as $key =>$value) {
            $criteriaStrings[]= "?e $key $value";
        }

        $query.=implode(" . \n", $criteriaStrings)."}\n";
        if (isset($criteriaStrings["sort"])) {
            $query.="ORDER BY ".implode(", ".$criteriaStrings["sort"]);
        }

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
                                $query",
            "output" => "json"
        );

        return \Util::curl_get_contents("http://dbpedia.org/sparql", $parameters);
    }

    function getCommonsImageURL($imageMapFullName) {
        $xmlFormatedPage = new \SimpleXMLElement($this->getCommonsImageXMLInfo($imageMapFullName));
        if (isset($xmlFormatedPage->error)) {
            echo '<b>Error : '.$xmlFormatedPage->error.'</b><br />';
            return null;
        }
        else {
            return $xmlFormatedPage->file->urls->file;
        }
    }

    function getCommonsImageXMLInfo($imageMapFullName) {
        $url = "http://tools.wmflabs.org/magnus-toolserver/commonsapi.php";
        return \Util::curl_get_contents($url, array("image" => $imageMapFullName), "GET");
    }

    /**
     * @param $imageMapUrl
     * @param $fileName
     * @return mixed|null
     */
    function fetchImage($imageMapUrl, $fileName = null) {
        if (!is_null($imageMapUrl)) {
            $svg = \Util::curl_get_contents($imageMapUrl, array(), "GET");
            if (!empty($svg)) {
                echo 'Fetched ' . $imageMapUrl . '<br />';
                if (!is_null($fileName)) {
                    file_put_contents(self::$cache_dir_svg.$fileName, $svg);
                }
                return $svg;
            }
        }
        return null;
    }

    /**
     * Fetch the image filenames corresponding to a criteria group
     * @param $criteriaGroup
     * @param $fileName
     * @return array
     */
    public function fetchSvgFilenamesFromCriteriaGroup($criteriaGroup, $fileName = null)
    {
        $page = $this->getSparqlQueryResults($criteriaGroup);
        if (!is_null($fileName)) {
            file_put_contents($fileName, $page);
        }
        $pageAsJson = json_decode($page);

        return $this->fetchSvgFilenamesFromSparqlResults($pageAsJson);
    }

    /**
     * Fetch the image filenames from a JSON-formatted SPARQL page
     * @param $pageAsJson
     * @return array
     */
    public function fetchSvgFilenamesFromSparqlResults($pageAsJson)
    {
        $imageNames = array();
        foreach ($pageAsJson->results->bindings as $result) {
            $imageMapFullName = \Util::cleanupImageName($result->imageMap->value);

            if (strtolower(\Util::getImageExtension($imageMapFullName)) === ".svg") {
                $imageNames[]=$imageMapFullName;
            }
        }

        return $imageNames;
    }

    /**
     * Get the images' Wikimedia Commons URLs
     * @param $imageNames
     * @return array An associative name=>URL array
     */
    public function getCommonsURLs($imageNames) {
        $urls = array();
        foreach($imageNames as $imageName) {
            $imageMapUrl = $this->getCommonsImageURL($imageName);
            $urls[$imageName] = $imageMapUrl;
        }

        return $urls;
    }
}

?>