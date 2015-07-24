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
    public static function generateAndSaveReferences($imageMapFullName, $startDateStr = null, $endDateStr = null)
    {
        self::$log->debug('Generating references for map '.$imageMapFullName);

        $map = new Map();
        $map->setFileName($imageMapFullName);

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
     * @param $result \stdClass
     * @return Map|null
     */
    public static function buildAndSaveFromObject($result)
    {
        $imageMapFullName = Util::cleanupImageName($result->imageMap->value);
        $startAndEndDates = Util::getDatesFromSparqlResult($result);
        if (is_null($startAndEndDates)) {
            $map = MapHelper::generateAndSaveReferences($imageMapFullName);
        }
        else {
            $map = MapHelper::generateAndSaveReferences($imageMapFullName, $startAndEndDates->startDate, $startAndEndDates->endDate);
        }
        $imageMapUrlAndUploadDate = Import::instance()->getCommonsImageInfos($map->getFileName());

        // The map image couldn't be retrieved => the Map object that we started to fill and its references are deleted
        if (is_null($imageMapUrlAndUploadDate)) {
            MapHelper::deleteTerritories($map);
        }
        else {
            $imageMapUrl = $imageMapUrlAndUploadDate['url'];
            $imageMapUploadDate = $imageMapUrlAndUploadDate['uploadDate'];
            Import::instance()->fetchAndStoreImage($map, $imageMapFullName, $imageMapUploadDate, $imageMapUrl);
            return $map;
        }
        return null;
    }

    public static function findOneSvgByFileName($imageMapFullName, $previouslyCreatedMaps = array()) {
        if (strtolower(Util::getImageExtension($imageMapFullName)) === ".svg") {
            $map = MapHelper::findOneByFileName($imageMapFullName);
            if (is_null($map) || array_key_exists($imageMapFullName, $previouslyCreatedMaps)) {
                return $map;
            }
        }
        return null;
    }

    /**
     * @param $map Map
     * @param $territory Territory
     */
    public static function addTerritory($map, $territory) {
        $map->addTerritory($territory);
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
     * @return object
     */
    public function __toSimplifiedObject() {
        // TODO
        /*
        $territories = $this->getTerritories();
        $simplifiedTerritories = array();
        foreach($territories as $territory) {
            $territory->loadReferencedTerritory();
            $simplifiedTerritories[] = $territory->__toSimplifiedObject(true);
        }

        $simplifiedMap = parent::__toSimplifiedObject();
        $simplifiedMap->territories = $simplifiedTerritories;

        return $simplifiedMap;
        */
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
     * @param $fileName
     * @return Map|object
     */
    public static function findOneByFileName($fileName) {
        return ModelHelper::getEm()->getRepository(Map::CLASSNAME)
            ->findOneBy(array('fileName' => $fileName));
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
     * @return int
     */
    public static function count() {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(map.id)');
        $qb->from(Map::CLASSNAME,'map');

        return $qb->getQuery()->getSingleScalarResult();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(Map::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}

MapHelper::$log = Logger::getLogger("main");