<?php
namespace geotime\helpers;
use geotime\models\mariadb\Map;
use geotime\models\mariadb\Territory;
use geotime\new_models\AbstractEntityHelper;

use Logger;

Logger::configure("lib/geotime/logger.xml");

class MapHelper implements AbstractEntityHelper
{
    /** @var \Logger */
    static $log;

    /**
     * @param $imageMapFullName
     * @param $startDateStr
     * @param $endDateStr
     * @return Map
     */
    public static function generateAndSaveReferences($imageMapFullName, $startDateStr, $endDateStr)
    {
        self::$log->debug('Generating references for map '.$imageMapFullName);

        $territory = new Territory(null, new \stdClass(), 0, '', new \DateTime($startDateStr), new \DateTime($endDateStr), true);

        ModelHelper::getEm()->persist($territory);
        ModelHelper::getEm()->flush();

        $map = new Map();
        $map->setFileName($imageMapFullName);
        $map->setTerritories(array($territory));

        return $map;
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
        ModelHelper::getEm()->flush();
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

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(Map::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}

MapHelper::$log = Logger::getLogger("main");