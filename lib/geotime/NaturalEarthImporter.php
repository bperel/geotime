<?php

namespace geotime;

use geotime\models\Map;
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

        /** @var Map $naturalDataMap */
        $naturalDataMap = Map::one(array('fileName'=>$fileName));
        if (!is_null($naturalDataMap)) {
            $nbImportedCountries = count($naturalDataMap->getTerritoriesWithPeriods());
            self::$log->info('The Natural Earth data has already been imported');
            self::$log->info($nbImportedCountries.' country positions from Natural Earth data are stored');

            return $nbImportedCountries;
        }

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

        $territoriesWithPeriods = array();
        foreach($countriesAndCoordinates as $countryName=>$coordinates) {

            $t = new Territory();
            $t->setName($countryName);
            $t->setPolygon($coordinates);
            $t->save();

            $tp = new TerritoryWithPeriod();
            $tp->setPeriod($p);
            $tp->setTerritory($t);
            $tp->save();

            $territoriesWithPeriods[] = $tp;
        }

        $map = new Map();
        $map->setFileName($fileName);
        $map->setTerritoriesWithPeriods($territoriesWithPeriods);
        $map->save();

        $nbImportedCountries = count($countriesAndCoordinates);

        if (is_int($nbImportedCountries)) {
            self::$log->info($nbImportedCountries.' country positions have been imported from Natural Earth data');
        }

        return $nbImportedCountries;
    }
}

NaturalEarthImporter::$log = Logger::getLogger("main");

?>