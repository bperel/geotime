<?php

namespace geotime;

use geotime\models\Map;
use geotime\models\Period;
use geotime\models\ReferencedTerritory;
use geotime\models\Territory;
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

        $start = microtime(true);
        /** @var Map $naturalDataMap */
        $naturalDataMap = Map::one(array('fileName'=>$fileName));
        if (!is_null($naturalDataMap)) {
            $nbImportedCountries = count($naturalDataMap->getTerritories());
            self::$log->info('The Natural Earth data has already been imported');
            self::$log->info($nbImportedCountries.' country positions from Natural Earth data are stored');

            return $nbImportedCountries;
        }

        $p = new Period();
        $p->setStart(new \MongoDate(strtotime(self::$dataDate)));
        $p->setEnd(new \MongoDate(strtotime(self::$dataDate)));

        $countriesAndCoordinates = array();

        $content = json_decode(file_get_contents($fileName));

        foreach($content->features as $country) {
            $countryName = $country->properties->sovereignt;

            if (!array_key_exists($countryName, $countriesAndCoordinates)) {
                if (isset($country->geometry->coordinates[0][0][0][0])) { // Ex: Japan, which posesses several separated lands
                    $countriesAndCoordinates[$countryName] = $country->geometry->coordinates;
                }
                else {
                    $countriesAndCoordinates[$countryName] = array($country->geometry->coordinates);
                }
            }
            else { // Ex: France, which also posesses sovereignty over French Southern and Antarctic Lands
                $countriesAndCoordinates[$countryName] = array_merge($countriesAndCoordinates[$countryName], $country->geometry->coordinates);
            }
        }

        $territories = array();
        foreach($countriesAndCoordinates as $countryName=>$coordinates) {
            $referencedTerritory = ReferencedTerritory::buildAndCreate($countryName);
            $t = Territory::buildAndCreateFromNEData($referencedTerritory, $coordinates);
            $territories[] = $t;
        }

        $map = new Map();
        $map->setFileName($fileName);
        $map->setTerritories($territories);
        $map->save();

        $nbImportedCountries = count($countriesAndCoordinates);

        $end = microtime(true);
        $timeSpent = (intval($end-$start))/1000;

        self::$log->info($nbImportedCountries.' country positions have been imported from Natural Earth data in '.$timeSpent.'s');

        return $nbImportedCountries;
    }
}

NaturalEarthImporter::$log = Logger::getLogger("main");

?>