<?php

namespace geotime;

use geotime\models\CriteriaGroup;
use geotime\models\Map;
use geotime\models\Period;
use geotime\models\Territory;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Geotime {

    /** @var \Logger */
    static $log;

    /**
     * @var int Natural Earth data coverage
     */
    static $optimalCoverage = 145389748;

    /**
     * @param $svgOnly boolean
     * @return array
     */
    static function getMapsAndLocalizedTerritoriesCount($svgOnly) {

        $filters = $svgOnly ? array('fileName' => array('$regex' => '.svg$')) : array();

        return array_reduce(
            Map::find($filters)->toArray(),
            function($result, Map $map) {
                $territories = $map->getTerritories()->toArray();
                $result[$map->getFileName()] = array(
                    'count' => count($territories),
                    'area'  => array_sum(
                        array_map(function (Territory $territory) {
                            return $territory->getArea();
                        }, $territories)
                    )
                );
                return $result;
            }
        );
    }

    /**
     * @param $startingWith
     * @return array|string
     */
    static function getTerritories($startingWith) {
        if (is_null($startingWith) || strlen($startingWith) === 0) {
            return 'At least the first letter of the territory name must me given.';
        }
        return array_map(
            function(Territory $territory) {
                return array('name' => $territory->getName());
            },
            Territory::find(array('name' => array('$regex' => '^'.$startingWith)), array('name' => 1), array('name'=> 1, '_id'=> -1))->toArray()
        );
    }

    /**
     * Get the land coverage stored for each period
     *
     * @return array An associative (Period string) => (coverage integer) array
     */
    static function getCoverageInfo() {

        $periodsAndCoverage = Territory::aggregate(
            array(
                array(
                    '$group' => array(
                        '_id' => '$period',
                        'areaSum' => array(
                            '$sum' => '$area'
                        )
                    )
                )
            )
        );

        $formattedPeriodsAndCoverage = array();
        foreach($periodsAndCoverage['result'] as $periodAndCoverage) {
            $periodArray = $periodAndCoverage['_id'];

            if (is_null($periodArray)) { // No period specified <=> Natural earth data
                $period = new Period();
                $period->setStart(new \MongoDate(strtotime(NaturalEarthImporter::$dataDate)));
                $period->setEnd(new \MongoDate(strtotime(NaturalEarthImporter::$dataDate)));
            }
            else {
                $period = new Period($periodArray);
            }
            $coverage = new \stdClass();
            $coverage->start = $period->getStartYear();
            $coverage->end = $period->getEndYear();
            $coverage->coverage = $periodAndCoverage['areaSum'];

            $formattedPeriodsAndCoverage[] = $coverage;
        }

        return array('periodsAndCoverage' => $formattedPeriodsAndCoverage, 'optimalCoverage' => self::$optimalCoverage);

    }

    /**
     * @param $year string
     * @param $ignored array
     * @return object|null
     */
    public static function getIncompleteMapInfo($year, $ignored = array())
    {
        $ignoredIds = array();
        foreach($ignored as $ignoredIdString) {
            $ignoredIds[] = new \MongoId($ignoredIdString);
        }

        $year=new \MongoDate(strtotime($year.'-01-01'));
        /** @var Territory $matchingTerritories */
        $matchingTerritory = Territory::one(array(
            'polygon' => array('$exists' => false),
            'period.start' => array('$lte' => $year),
            'period.end' => array('$gte' => $year)));

        if (!is_null($matchingTerritory)) {
            /** @var Map $incompleteMap */
            $incompleteMap = Map::one(array(
                'territories.$id' => new \MongoId($matchingTerritory->getId()),
                '_id' => array('$nin' => $ignoredIds)
            ));
            if (!is_null($incompleteMap)) {
                return $incompleteMap->__toSimplifiedObject();
            }
        }

        return null;
    }

    /**
     * @param $mapId
     * @param $mapProjection string
     * @param $mapPosition string[]
     * @return Map|null
     */
    public static function updateMap($mapId, $mapProjection, $mapPosition) {
        /** @var Map $map */
        $map = Map::one(array('_id' => new \MongoId($mapId)));
        if (is_null($map)) {
            return null;
        }
        else {
            $map->setProjection($mapProjection);
            array_walk_recursive($mapPosition, function(&$item) {
                $item = floatval($item);
            });
            $map->setPosition($mapPosition);
            $map->save();
            return $map;
        }
    }

    public static function addLocatedTerritory($territoryName, $coordinates, $xpath)
    {
        $territory = new Territory();
        $territory->setName($territoryName);
        $territory->setPolygon($coordinates);
        $territory->setXpath($xpath);
        $territory->save();

        return $coordinates;
    }

    public static function getCriteriaGroupsNumber() {
        return CriteriaGroup::count();
    }

    /**
     * Removes maps, territories and periods from the DB
     * @param bool $keepMaps
     */
    public static function clean($keepMaps=false) {
        if (!$keepMaps) {
            Map::drop();
        }
        Territory::drop();
        Period::drop();
    }
}

Geotime::$log = Logger::getLogger("main");

?>