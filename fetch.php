<?php

    $criteriaGroups = array(
        "Former Empires" => array(
            "<http://purl.org/dc/terms/subject>"            => "<http://dbpedia.org/resource/Category:Former_empires>",
            "<http://dbpedia.org/ontology/foundingDate>"    => "?date1",
            "<http://dbpedia.org/ontology/dissolutionDate>" => "?date2",
            "<http://dbpedia.org/property/imageMap>"        => "?imageMap"
        )
    );

    $m = new MongoClient();
    $db = $m->geotime;
    $cache = $db->cache;

    if (isset($_GET['clean'])) {
        $cache->drop();
    }

    foreach($criteriaGroups as $criteriaGroupName => $criteriaGroup) {

        $query_criteriaGroup_is_cached = array( "criteriaGroup" => $criteriaGroupName );
        $cached_criteria_group = $cache->findOne( $query_criteriaGroup_is_cached );

        $fileName = "cache/" . $criteriaGroupName . ".json";

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

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "http://dbpedia.org/sparql");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");

            $page = curl_exec($ch);
            file_put_contents($fileName, $page);

            curl_close($ch);

            foreach($criteriaGroup as $key =>$value) {
                $document = array( "criteriaGroup" => $criteriaGroupName, "key" => $key, "value" => $value );
                $cache->remove($document);
                $cache->insert($document);
            }
        }
    }

    $cursor = $cache->find();

    // traverse les résultats
    foreach ($cursor as $document) {
        echo "<pre>".print_r($document, true)."</pre>";
    }

?>