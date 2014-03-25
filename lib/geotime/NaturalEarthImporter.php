<?php

namespace geotime;

use geotime\models\Period;
use geotime\models\Territory;
use geotime\models\TerritoryWithPeriod;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class NaturalEarthImporter {

    /** @var \Logger */
    static $log;

    static $dataDate = '01-01-2012';

    /**
     * @param string $fileName
     * @return int
     */
    function import($fileName) {

        $p = new Period();
        $p->setStart(new \MongoDate(strtotime(self::$dataDate)));
        $p->setEnd(new \MongoDate(strtotime(self::$dataDate)));
        $p->save();


        $countriesAndCoordinates = array();

        $content = json_decode(file_get_contents($fileName));

        foreach($content->features as $country) {
            $countryName = $country->properties->sovereignt;

            if (!array_key_exists($countryName, $countriesAndCoordinates)) {
                if (isset($country->geometry->coordinates[0][0][0][0])) { // Japan, for instance, which posesses several separated lands
                    $countriesAndCoordinates[$countryName] = $country->geometry->coordinates;
                }
                else {
                    $countriesAndCoordinates[$countryName] = array($country->geometry->coordinates);
                }
            }
            else {
                $countriesAndCoordinates[$countryName] = array_merge($countriesAndCoordinates[$countryName], $country->geometry->coordinates);
            }
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