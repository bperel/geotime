<?php

namespace geotime;

use geotime\helpers\CalibrationPointHelper;
use geotime\helpers\CriteriaGroupHelper;
use geotime\helpers\ModelHelper;
use geotime\models\Map;
use geotime\models\ReferencedTerritory;
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

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->select('map')
            ->from(models\mariadb\Map::CLASSNAME,'map');

        if ($svgOnly) {
            $qb->where(
                $qb->expr()->like('map.fileName', $qb->expr()->literal('%.svg'))
            );
        }

        /** @var models\mariadb\Map[] $maps */
        $maps = $qb->getQuery()->getResult();

        $result = array();

        array_walk(
            $maps,
            function(models\mariadb\Map $map) use (&$result) {
                $territories = $map->getTerritories()->getValues();
                $result[$map->getFileName()] = array(
                    'count' => count($territories),
                    'area'  => array_sum(
                        array_map(function (models\mariadb\Territory $territory) {
                            return $territory->getArea();
                        }, $territories)
                    )
                );
            }
        );

        return $result;
    }

    /**
     * @param $startingWith
     * @return array|string
     */
    static function getReferencedTerritories($startingWith) {
        if (is_null($startingWith) || strlen($startingWith) === 0) {
            return 'At least the first letter of the territory name must me given.';
        }
        return array_map(
            function(ReferencedTerritory $referencedTerritory) {
                return array('id' => $referencedTerritory->getId()->__toString(), 'name' => $referencedTerritory->getName());
            },
            ReferencedTerritory::find(array('name' => array('$regex' => '^'.$startingWith)), array('name' => 1), array('name'=> 1))->toArray()
        );
    }


    /**
     * @return int
     */
    static function getImportedTerritoriesCount() {
        return Territory::count(array('userMade' => false));
    }

    /**
     * Get the land coverage stored for each period
     *
     * @return array An associative (Period string) => (coverage integer) array
     */
    static function getCoverageInfo() {

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->select('territory.startDate, territory.endDate, territory.userMade, sum(territory.area) as areaSum')
            ->from(models\mariadb\Territory::CLASSNAME,'territory')
            ->groupBy('territory.startDate, territory.endDate, territory.userMade')
            ->orderBy('territory.userMade DESC, territory.startDate, territory.endDate');

        $periodsAndCoverage = $qb->getQuery()->getArrayResult();

        $formattedPeriodsAndCoverage = array();
        foreach($periodsAndCoverage as $periodAndCoverage) {
            $coverage = new \stdClass();
            if ($periodAndCoverage['userMade']) {
                $coverage->start = $periodAndCoverage['startDate']->format('Y');
                $coverage->end = $periodAndCoverage['endDate']->format('Y');
            }
            else {  // Natural earth data
                $coverage->start = date('Y', strtotime(NaturalEarthImporter::$dataDate));
                $coverage->end = date('Y', strtotime(NaturalEarthImporter::$dataDate));
            }
            $coverage->coverage = $periodAndCoverage['areaSum'];
            $formattedPeriodsAndCoverage[] = $coverage;
        }

        return array('periodsAndCoverage' => $formattedPeriodsAndCoverage, 'optimalCoverage' => self::$optimalCoverage);

    }

    /**
     * @return object|null
     */
    public static function getIncompleteMapInfo()
    {
        /** @var Map $incompleteMap */
        $incompleteMap = Map::one(array('uploadDate'=>array('$exists' => true)));
        if (!is_null($incompleteMap)) {
            return $incompleteMap->__toSimplifiedObject();
        }

        return null;
    }

    /**
     * @param $mapId
     * @param $mapProjection string|null
     * @param $mapRotation float[]|null
     * @param $mapCenter string[]|null
     * @param $mapScale int|null
     * @param $calibrationPoints string[]
     * @return Map|null
     */
    public static function updateMap($mapId, $mapProjection = null, $mapRotation = null, $mapCenter = null, $mapScale = null, $calibrationPoints = null) {
        /** @var Map $map */
        $map = Map::one(array('_id' => new \MongoId($mapId)));
        if (is_null($map)) {
            return null;
        }
        else {
            if (!empty($mapProjection)) {
                $map->setProjection($mapProjection);
            }
            if (!empty($mapRotation)) {
                array_walk_recursive($mapRotation, function (&$item) {
                    $item = floatval($item);
                });
                $map->setRotation($mapRotation);
            }
            if (!empty($mapCenter)) {
                array_walk_recursive($mapCenter, function (&$item) {
                    $item = floatval($item);
                });
                $map->setCenter($mapCenter);
            }
            if (!empty($mapScale)) {
                $map->setScale($mapScale);
            }
            if (!empty($calibrationPoints)) {
                $calibrationPoints = array_map(function (&$calibrationPoint) {
                    return CalibrationPointHelper::generateFromStrings(json_decode(json_encode($calibrationPoint)));
                }, $calibrationPoints);
                $map->setCalibrationPoints($calibrationPoints);
            }
            if (!empty($mapProjection) && !empty($mapCenter) && !empty($mapScale)) {
                $map->save();
            }
            return $map;
        }
    }

    /**
     * @param $mapId string
     * @param $territoryId string
     * @param $xpath string
     * @param $territoryPeriodStart string
     * @param $territoryPeriodEnd string
     * @return mixed|null
     */
    public static function saveLocatedTerritory($mapId, $territoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd)
    {
        /** @var Map $map */
        $map = Map::one(array('_id' => new \MongoId($mapId)));
        if (is_null($map)) {
            return null;
        }

        /** @var ReferencedTerritory $referencedTerritory */
        $referencedTerritory = ReferencedTerritory::one(array('_id' => new \MongoId($territoryId)));
        if (is_null($referencedTerritory)) {
            return null;
        }

        $territory = Territory::buildAndCreateWithReferencedTerritory(
            $referencedTerritory, true, $territoryPeriodStart, $territoryPeriodEnd, $xpath
        );
        $geocoordinates = $territory->calculateCoordinates($map);
        $territory->setPolygon(array(array($geocoordinates)));
        $territory->save();
        $map->addTerritory($territory);
        $map->save();
    }

    public static function getCriteriaGroupsNumber() {
        return CriteriaGroupHelper::count();
    }

    /**
     * Removes maps, territories and periods from the DB
     * @param bool $keepMaps
     */
    public static function clean($keepMaps=false) {
        $connection = ModelHelper::getEm()->getConnection();
        $platform = $connection->getDatabasePlatform();

        if (!$keepMaps) {
            $connection->executeUpdate($platform->getTruncateTableSQL('maps', true));
        }
        else {
            $connection->executeUpdate($platform->getTruncateTableSQL('territories', true));
        }
    }
}

Geotime::$log = Logger::getLogger("main");

?>