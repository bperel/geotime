<?php

namespace geotime;

use Doctrine\ORM\AbstractQuery;
use geotime\helpers\CalibrationPointHelper;
use geotime\helpers\MapHelper;
use geotime\helpers\ModelHelper;
use geotime\helpers\TerritoryHelper;
use geotime\models\mariadb\CalibrationPoint;
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
            ->select('map.fileName')
            ->addSelect('count(territory.id) as territoryNumber, coalesce(sum(territory.area), 0) as territoryAreaSum')
            ->from(models\mariadb\Map::CLASSNAME,'map')
            ->leftJoin('map.territories', 'territory')
            ->groupBy('map.fileName');
        if ($svgOnly) {
            $qb->where(
                $qb->expr()->like('map.fileName', $qb->expr()->literal('%.svg'))
            );
        }

        $query = $qb->getQuery();

        /** @var models\mariadb\Map[] $maps */
        $mapsAndTerritoryInfo = $query->getResult();

        $result = array();

        array_walk(
            $mapsAndTerritoryInfo,
            function($mapAndTerritoryInfo) use (&$result) {
                $result[$mapAndTerritoryInfo['fileName']] = array(
                    'count' => intval($mapAndTerritoryInfo['territoryNumber']),
                    'area' => intval($mapAndTerritoryInfo['territoryAreaSum'])
                );
            }
        );

        return $result;
    }

    /**
     * @param $like string
     * @return array|string
     */
    public static function getReferencedTerritories($like)
    {
        if (is_null($like) || strlen($like) === 0) {
            return 'At least one letter of the territory name must me given.';
        }

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->select('referencedTerritory')
            ->from(models\mariadb\ReferencedTerritory::CLASSNAME, 'referencedTerritory')
            ->where($qb->expr()->like('referencedTerritory.name', ':prefix'))
            ->setParameter('prefix', '%' . $like . '%');

        $results = $qb->getQuery()->getResult();

        return array_map(
            function (models\mariadb\ReferencedTerritory $referencedTerritory) {
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
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('territory.userMade', 'false'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('territory.userMade', 'true'),
                        $qb->expr()->isNotNull('territory.startDate'),
                        $qb->expr()->isNotNull('territory.endDate')
                    )
                )
            )
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
     * @return \string[]
     */
    public static function getMaps()
    {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->addSelect('map.fileName')
            ->from(models\mariadb\Map::CLASSNAME,'map')
            ->where(
                $qb->expr()->isNotNull('map.uploadDate')
            )
            ->orderBy('map.fileName')
        ;

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @param string $fileName
     * @return models\mariadb\Map|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public static function getIncompleteMapInfo($fileName = null)
    {
        //Get the number of rows of the table
        $rows = MapHelper::count(true);
        $offset = max(0, rand(0, $rows-1));

        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb
            ->addSelect('map')
            ->from(models\mariadb\Map::CLASSNAME,'map')
            ->where(
                $qb->expr()->isNotNull('map.uploadDate')
            );

        if (!is_null($fileName)) {
            $qb->andWhere($qb->expr()->eq('map.fileName', $qb->expr()->literal($fileName)));
        }
        else {
            $qb
                ->setMaxResults(1)
                ->setFirstResult($offset);
        }

        $map = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SIMPLEOBJECT);

        if (!is_null($map)) {
            $map->territories = MapHelper::getTerritories($map);
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
                $newCalibrationPoints = array();
                $calibrationPoints = array_map(function($calibrationPoint) {
                    return json_decode(json_encode($calibrationPoint));
                }, $calibrationPoints);
                foreach($calibrationPoints as $calibrationPoint) {
                    if (!array_key_exists($calibrationPoint->pointId, $newCalibrationPoints)) {
                        $newCalibrationPoints[$calibrationPoint->pointId] = new CalibrationPoint();
                    }
                    CalibrationPointHelper::addCoordinatesForCalibrationPoint(
                        $newCalibrationPoints[$calibrationPoint->pointId],
                        $calibrationPoint->type,
                        $calibrationPoint->coordinates
                    );
                }
                $map->setCalibrationPoints($newCalibrationPoints);
            }
            if (!empty($mapProjection) && !empty($mapCenter) && !empty($mapScale)) {
                ModelHelper::getEm()->persist($map);
                ModelHelper::getEm()->flush();
                return MapHelper::find($mapId);
            }
            return $map;
        }
    }

    /**
     * @param $mapId integer
     * @param $referencedTerritoryId integer
     * @param $xpath string
     * @param $territoryPeriodStart string
     * @param $territoryPeriodEnd string
     * @param $territoryId integer
     * @return bool success
     */
    public static function saveLocatedTerritory($mapId, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd, $territoryId = null)
    {
        $map = MapHelper::find($mapId);
        if (is_null($map)) {
            return false;
        }

        return TerritoryHelper::saveLocatedTerritory($territoryId, $map, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd);
    }

    /**
     * @param $dateStart string
     * @param $dateEnd string
     * @return \stdClass
     */
    public static function countForPeriod($dateStart, $dateEnd) {
        $result = new \stdClass();
        $result->count = TerritoryHelper::countForPeriod(new \DateTime($dateStart), new \DateTime($dateEnd), true);
        return $result;
    }

    /**
     * @param $dateStart string
     * @param $dateEnd string
     * @return \stdClass
     */
    public static function getTerritoriesForPeriod($dateStart, $dateEnd) {
        $result = new \stdClass();
        $result->territories = TerritoryHelper::getTerritoriesForPeriod(new \DateTime($dateStart), new \DateTime($dateEnd));
        return $result;
    }

    /**
     * Removes maps, territories and periods from the DB
     */
    public static function clean() {
        $connection = ModelHelper::getEm()->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeUpdate($platform->getTruncateTableSQL('territories', true));
        $connection->executeQuery('DELETE FROM maps');
        $connection->executeQuery('DELETE FROM referencedTerritories');
    }
}

Geotime::$log = Logger::getLogger("main");

?>
