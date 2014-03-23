<?php

namespace geotime;

use geotime\models\Criteria;
use geotime\models\CriteriaGroup;
use geotime\models\Period;
use geotime\models\Territory;
use geotime\models\TerritoryWithPeriod;
use Logger;

Logger::configure(stream_resolve_include_path("logger.xml"));

include_once('Util.php');

class NaturalEarthImporter {

    /** @var \Logger */
    static $log;

    static $dataYear = 2012;

    /**
     * @param string $fileName
     * @return int
     */
    function import($fileName) {

        $p = new Period();
        $p->setStart(new \MongoDate(strtotime('01-01-'.self::$dataYear)));
        $p->setEnd(new \MongoDate(strtotime('01-01-'.self::$dataYear)));
        $p->save();


        $countriesAndCoordinates = array();

        $content = json_decode(file_get_contents($fileName));

        foreach($content->features as $country) {
            $countryName = $country->properties->sovereignt;

            if (!array_key_exists($countryName, $countriesAndCoordinates)) {
                $countriesAndCoordinates[$countryName] = array();
            }

            $countriesAndCoordinates[$countryName] = array_merge($countriesAndCoordinates[$countryName], $country->geometry->coordinates[0]);
        }

        foreach($countriesAndCoordinates as $countryName=>$coordinates) {
            $polygon = array('$geoWithin' => array('$polygon'=>$coordinates));

            $t = new Territory();
            $t->setName($countryName);
            $t->setPolygon(json_decode(json_encode($polygon)));
            $t->save();

            $tp = new TerritoryWithPeriod();
            $tp->setPeriod($p);
            $tp->setTerritory($t);
            $tp->save();
        }

        return count($countriesAndCoordinates);
    }

    function clean() {
        TerritoryWithPeriod::drop();
        Territory::drop();
        Period::drop();
    }
}

NaturalEarthImporter::$log = Logger::getLogger("main");

?>