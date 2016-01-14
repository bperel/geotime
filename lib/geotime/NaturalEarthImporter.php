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

        $content = json_decode(file_get_contents($fileName));

        /** @var SimpleReferencedTerritory[] $countriesAndCoordinates */
        $countriesAndCoordinates = array();

        $map = new Map();
        $map->setFileName($fileName);

        foreach($content->features as $country) {
            $countryName = $country->properties->name_long;

            $coordinates = null;
            if (!array_key_exists($countryName, $countriesAndCoordinates)) {
                if (isset($country->geometry->coordinates[0][0][0][0])) { // Ex: Japan, which posesses several separated lands
                    $coordinates = $country->geometry->coordinates;
                }
                else {
                    $coordinates = array($country->geometry->coordinates);
                }
            }
            else { // Ex: France, which also posesses sovereignty over French Southern and Antarctic Lands
                $coordinates = array_merge($countriesAndCoordinates[$countryName]->coordinates, $country->geometry->coordinates);
            }

            $countriesAndCoordinates[$countryName] = new SimpleReferencedTerritory(
                $countryName,
                $coordinates,
                $country->properties->type === 'Dependency' ? $country->properties->sovereignt : null
            );

        }

        uasort($countriesAndCoordinates,
            function($a) {
                return !is_null($a->dependentOf);
            }
        );

        $territories = array();
        foreach($countriesAndCoordinates as $country) {
            if (!is_null($country->dependentOf)) {
                ModelHelper::getEm()->flush();
            }
            $territories[] = $country->persist($map);
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
        $connection->executeQuery('DELETE FROM referencedTerritories WHERE id NOT IN (SELECT DISTINCT referenced_territory FROM territories)');

        self::$log->info('Territories from Natural Earth data have been removed');
    }
}

NaturalEarthImporter::$log = Logger::getLogger("main");


class SimpleReferencedTerritory {
    public $countryName;
    public $coordinates;
    public $dependentOf;

    /**
     * SimpleReferencedTerritory constructor.
     * @param string $countryName
     * @param array $coordinates
     * @param string $dependentOf
     */
    public function __construct($countryName, $coordinates, $dependentOf = null)
    {
        $this->countryName = $countryName;
        $this->coordinates = $coordinates;
        $this->dependentOf = $dependentOf;
    }

    /**
     * @param Map $map
     * @return models\Territory
     */
    public function persist($map) {
        $referencedTerritory = ReferencedTerritoryHelper::buildAndCreate($this->countryName, null, null, $this->dependentOf);
        $t = TerritoryHelper::buildAndCreateFromNEData($referencedTerritory, $this->coordinates);
        $t->setMap($map);
        TerritoryHelper::save($t, false);
        return $t;
    }
}

?>
