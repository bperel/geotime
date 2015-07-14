<?php

namespace geotime;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\PersistentCollection;
use geotime\helpers\CalibrationPointHelper;
use geotime\helpers\CriteriaGroupHelper;
use geotime\helpers\MapHelper;
use geotime\helpers\ModelHelper;
use geotime\helpers\ReferencedTerritoryHelper;
use geotime\helpers\TerritoryHelper;
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
    public static function getReferencedTerritories($startingWith)
    {
        if (is_null($startingWith) || strlen($startingWith) === 0) {
            return 'At least the first letter of the territory name must me given.';
        }

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->select('referencedTerritory')
            ->from(\geotime\models\mariadb\ReferencedTerritory::CLASSNAME, 'referencedTerritory')
            ->where($qb->expr()->like('referencedTerritory.name', ':prefix'))
            ->setParameter('prefix', $startingWith . '%');

        $results = $qb->getQuery()->getResult();

        return array_map(
            function (\geotime\models\mariadb\ReferencedTerritory $referencedTerritory) {
                return array(
                    'id' => $referencedTerritory->getId(),
                    'name' => $referencedTerritory->getName()
                );
            },
            $results
        );
    }

    /**
     * @return int
     */
    static function getImportedTerritoriesCount() {
        return TerritoryHelper::count(false);
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
     * @return \geotime\models\mariadb\Map|null
     */
    public static function getIncompleteMapInfo()
    {
        $qb = ModelHelper::getEm()->createQueryBuilder();

        $qb
            ->addSelect('map')
            ->from(models\mariadb\Map::CLASSNAME,'map')
            ->where(
                $qb->expr()->isNotNull('map.uploadDate')
            )
        ;

        $query = $qb->getQuery();

        $map = $query->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);

        if (!is_null($map)) {
            /** @var PersistentCollection $mapTerritories */
            $mapTerritories = $map->territories;
            $map->territories = $mapTerritories->toArray();
        }
        return $map;
    }

    /**
     * @param $mapId
     * @param $mapProjection string|null
     * @param $mapRotation float[]|null
     * @param $mapCenter string[]|null
     * @param $mapScale int|null
     * @param $calibrationPoints string[]
     * @return \geotime\models\mariadb\Map|null
     */
    public static function updateMap($mapId, $mapProjection = null, $mapRotation = null, $mapCenter = null, $mapScale = null, $calibrationPoints = null) {
        /** @var \geotime\models\mariadb\Map $map */
        $map = MapHelper::find($mapId);
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
                ModelHelper::getEm()->persist($map);
                ModelHelper::getEm()->flush();
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
     * @return null
     */
    public static function saveLocatedTerritory($mapId, $territoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd)
    {
        /** @var \geotime\models\mariadb\Map $map */
        $map = MapHelper::find($mapId);
        if (is_null($map)) {
            return null;
        }

        $referencedTerritory = ReferencedTerritoryHelper::find($territoryId);
        if (is_null($referencedTerritory)) {
            return null;
        }

        $territory = TerritoryHelper::buildAndCreateWithReferencedTerritory(
            $referencedTerritory, $territoryPeriodStart, $territoryPeriodEnd, $xpath
        );
        $geocoordinates = TerritoryHelper::calculateCoordinates($territory, $map);

        if (!is_null($geocoordinates)) {
            $territory->setPolygon(json_decode(json_encode(array(array($geocoordinates)))));
            TerritoryHelper::save($territory);

            $map->addTerritory($territory);
            ModelHelper::getEm()->persist($map);

            ModelHelper::getEm()->flush();
        }
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

        $connection->executeUpdate($platform->getTruncateTableSQL('territories', true));
    }
}

Geotime::$log = Logger::getLogger("main");

?>