<?php

namespace geotime;

include_once('Database.php');
include_once('Util.php');

class Fetch {

    function fetchSvgs() {

        $criteriaGroups = array(
            "Former Empires" => array(
                "<http://purl.org/dc/terms/subject>"            => "<http://dbpedia.org/resource/Category:Former_empires>",
                "<http://dbpedia.org/ontology/foundingDate>"    => "?date1",
                "<http://dbpedia.org/ontology/dissolutionDate>" => "?date2",
                "<http://dbpedia.org/property/imageMap>"        => "?imageMap"
            )
        );

        $cache = Database::$db->selectCollection("cache");

        if (isset($_GET['clean'])) {
            $cache->drop();
        }

        foreach($criteriaGroups as $criteriaGroupName => $criteriaGroup) {

            $query_criteriaGroup_is_cached = array( "criteriaGroup" => $criteriaGroupName );
            $cached_criteria_group = $cache->findOne( $query_criteriaGroup_is_cached );

            $fileName = "cache/json/" . $criteriaGroupName . ".json";

            if (!isset($cached_criteria_group) || !file_exists($fileName)) {
                $query = "SELECT *
                          WHERE
                            {
                             ";
                $criteriaStrings = array();
                foreach($criteriaGroup as $key =>$value) {
                    $criteriaStrings[]= "?e $key $value";
                }

                $query.=implode(" . \n", $criteriaStrings)
                      ."}\n"
                      ."ORDER BY DESC(?date1)";

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

                $page = \Util::curl_get_contents("http://dbpedia.org/sparql", $parameters);
                file_put_contents($fileName, $page);

                $pageAsJson = json_decode($page);

                foreach($pageAsJson->results->bindings as $result) {
                    $imageMap = $result->imageMap->value;

                    $imageMapExtension = substr($imageMap, strrpos($imageMap, "."));
                    $imageMapName = substr($imageMap, 0, strlen($imageMap) - strlen($imageMapExtension));
                    if (strtolower($imageMapExtension) === ".svg") {
                        $imageMapUrl = self::getCommonsImageURL($imageMapName, $imageMapExtension);
                        if (!is_null($imageMapUrl)) {
                            echo 'Fetched '.$imageMapUrl.'<br />';
                            $svg = \Util::curl_get_contents($imageMapUrl, array(), "GET");
                            if (!empty($svg)) {
                                file_put_contents("cache/svg/".$imageMap, $svg);
                            }
                        }
                    }
                }

                foreach($criteriaGroup as $key =>$value) {
                    $document = array( "criteriaGroup" => $criteriaGroupName, "key" => $key, "value" => $value );
                    $cache->remove($document);
                    $cache->insert($document);
                }
            }
        }

        $cursor = $cache->find();

        foreach ($cursor as $document) {
            echo "<pre>".print_r($document, true)."</pre>";
        }
    }

    static function getCommonsImageURL($imageMapName, $imageMapExtension) {
        $url = "http://tools.wmflabs.org/magnus-toolserver/commonsapi.php";
        $page = \Util::curl_get_contents($url, array("image" => trim($imageMapName).$imageMapExtension), "GET");

        $xmlFormatedPage = new \SimpleXMLElement($page);

        if (isset($xmlFormatedPage->error)) {
            echo '<b>Error : '.$xmlFormatedPage->error.'</b><br />';
            return null;
        }
        else {
            return $xmlFormatedPage->file->urls->file;
        }
    }
}

?>