<?php
namespace geotime\helpers;
use geotime\Import;
use geotime\models\mariadb\Map;
use geotime\models\mariadb\Territory;

use geotime\Util;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class MapHelper extends AbstractEntityHelper
{
    /** @var \Logger */
    static $log;

    /**
     * @param $imageMapFullName
     * @param $startDateStr
     * @param $endDateStr
     * @return Map
     */
    public static function generateAndSave($imageMapFullName, $startDateStr = null, $endDateStr = null)
    {
        self::$log->debug('Generating references for map '.$imageMapFullName);

        $map = new Map();
        $map->setFileName($imageMapFullName);
        self::persist($map);

        if (!is_null($startDateStr)) {
            $startDate = Util::createDateTimeFromString($startDateStr);
            $endDate = Util::createDateTimeFromString($endDateStr);
            $territory = new Territory(null, true, new \stdClass(), 0, '', $startDate, $endDate);
            self::persist($territory);
            $territory->setMap($map);
            ModelHelper::getEm()->flush($territory);
            $map->setTerritories(array($territory));
        }

        self::persist($map);
        ModelHelper::getEm()->flush($map);

        return $map;
    }

    /**
     * @param $imageMapFullName string
     * @param $territory Territory
     * @return Map
     */
    public static function generateAndSaveWithTerritory($imageMapFullName, $territory)
    {
        self::$log->debug('Associating a territory to new map '.$imageMapFullName);

        $map = new Map();
        $map->setFileName($imageMapFullName);
        ModelHelper::getEm()->flush();

        $territory->setMap($map);
        $map->setTerritories(array($territory));
        ModelHelper::getEm()->flush();
        self::persist($territory);

        ModelHelper::getEm()->flush();

        return $map;
    }

    /**
     * @param $mapFileName string
     * @param $territory Territory
     * @return false|Map Returns FALSE if an error occurred while retrieving the SVG image
     */
    public static function buildAndSaveWithTerritoryFromObject($mapFileName, $territory)
    {
        $imageMapFullName = Util::cleanupImageName($mapFileName);
        $imageMapUrlAndUploadDate = Import::instance()->getCommonsImageInfos($imageMapFullName);

        if (!is_null($imageMapUrlAndUploadDate)) {
            $imageMapUrl = $imageMapUrlAndUploadDate['url'];
            $imageMapUploadDate = $imageMapUrlAndUploadDate['uploadDate'];

            $map = MapHelper::generateAndSaveWithTerritory($imageMapFullName, $territory);
            $success = Import::instance()->fetchAndStoreImage($map, $imageMapFullName, $imageMapUploadDate, $imageMapUrl);
            if ($success) {
                return $map;
            }
        }
        return false;
    }

    /**
     * @param $map Map
     * @param $territory Territory
     */
    public static function addTerritory($map, $territory) {
        $map->addOrUpdateTerritory($territory);
    }

    /**
     * @param $map Map
     */
    public static function deleteTerritories($map) {
        self::$log->debug('Deleting territories from map '.$map->getFileName());

        foreach($map->getTerritories() as $territory) {
            ModelHelper::getEm()->remove($territory);
        }
        $map->setTerritories(array());
        self::flush();
    }

    /**
     * @return Map[]
     */
    public static function findAll()
    {
        return ModelHelper::getEm()->getRepository(Map::CLASSNAME)
            ->findAll();
    }

    /**
     * @param mixed $map
     * @return \stdClass
     */
    public static function getTerritories($map)
    {
        $territories = $map->territories->toArray();
        /** @var Territory $territory */
        foreach($territories as $territory) {
            unset($territory->map);
            if (is_null($territory->getStartDate())) {
                $territory->startDate = null;
            } else {
                $territory->startDate = date_format($territory->getStartDate(),'Y-m-d');
            }
            if (is_null($territory->getEndDate())) {
                $territory->endDate = null;
            } else {
                $territory->endDate = date_format($territory->getEndDate(),'Y-m-d');
            }
        }
        return $territories;
    }

    /**
     * @param $mapId int
     * @return null|Map
     */
    public static function find($mapId) {
        return ModelHelper::getEm()->getRepository(Map::CLASSNAME)
            ->find($mapId);
    }

    /**
     * @param string $imageMapFullName
     * @param Map[] $previouslyProcessedMaps array of maps.
     * Can contain NULL values when the map corresponding to the file name couldn't be retrieved previously
     * @return Map|null
     */
    public static function findOneByFileName($imageMapFullName, $previouslyProcessedMaps = array()) {
        if (array_key_exists($imageMapFullName, $previouslyProcessedMaps)) {
            return $previouslyProcessedMaps[$imageMapFullName];
        }
        else {
            return ModelHelper::getEm()->getRepository(Map::CLASSNAME)
                ->findOneBy(array('fileName' => $imageMapFullName));
        }
    }

    /**
     * @param $mapId int
     */
    public static function delete($mapId) {
        $map = self::find($mapId);
        ModelHelper::getEm()->remove($map);
        self::flush();
    }

    /**
     * @param bool $withUploadDate
     * @return int
     */
    public static function count($withUploadDate = false) {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(map.id)');
        $qb->from(Map::CLASSNAME,'map');
        if ($withUploadDate) {
            $qb->where(
                $qb->expr()->isNotNull('map.uploadDate')
            );
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(Map::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param Map $map
     * @return bool
     */
    public static function isCalibratedMap($map)
    {
        return count(
            array_filter(array(
                $map->getProjection(), $map->getRotation(), $map->getCenter(), $map->getScale()
            ), function($member) { return is_null($member); }
            )) === 0
            && count($map->getRotation()) === 3
            && count($map->getCenter()) === 2;
    }
}

MapHelper::$log = Logger::getLogger("main");
