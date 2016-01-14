<?php

namespace geotime;

use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\Map;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class NaturalEarthImporter {

    /** @var \Logger */
    static $log;

    static $dataDate = '01-01-2012';

    /**
     * @param string $fileName
     * @param boolean $clean
     * @return int
     */
    function import($fileName, $clean=false) {

        $start = microtime(true);
        if ($clean) {
            $this->cleanNaturalEarthTerritories();
        }
        else {
            /** @var Map $naturalDataMap */
            $naturalDataMap = ModelHelper::getEm()->getRepository(Map::CLASSNAME)
                ->findOneBy(array('fileName' => $fileName));

            if (!is_null($naturalDataMap)) {
                $nbImportedCountries = count($naturalDataMap->getTerritories());
                self::$log->info('The Natural Earth data has already been imported');
                self::$log->info($nbImportedCountries.' country positions from Natural Earth data are stored');

                return $nbImportedCountries;
            }
        }

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

        $map = new Map();
        $map->setFileName($fileName);

        $territories = array();
        foreach($countriesAndCoordinates as $countryName=>$coordinates) {
            $referencedTerritory = ReferencedTerritoryHelper::buildAndCreate($countryName);
            $t = TerritoryHelper::buildAndCreateFromNEData($referencedTerritory, $coordinates, new \DateTime(self::$dataDate));
            $t->setMap($map);
            TerritoryHelper::save($t);
            $territories[] = $t;
        }


        $map->setTerritories($territories);
        ModelHelper::getEm()->persist($map);
        ModelHelper::getEm()->flush();

        $nbImportedCountries = count($countriesAndCoordinates);

        $end = microtime(true);
        $timeSpent = (intval($end-$start))/1000;

        self::$log->info($nbImportedCountries.' country positions have been imported from Natural Earth data in '.$timeSpent.'s');

        return $nbImportedCountries;
    }

    function cleanNaturalEarthTerritories() {
        $connection = ModelHelper::getEm()->getConnection();
        $connection->executeQuery('DELETE FROM territories WHERE userMade=0');

        self::$log->info('Territories from Natural Earth data have been removed');
    }
}

NaturalEarthImporter::$log = Logger::getLogger("main");

?>
