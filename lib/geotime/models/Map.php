<?php

namespace geotime\models;

use Purekid\Mongodm\Model;
use Logger;

Logger::configure("lib/geotime/logger.xml");


class Map extends Model {
    static $collection = "maps";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'fileName' => array('type' => 'string'),
        'uploadDate' => array('type' => 'date'),
        'territoriesWithPeriods' => array('model' => 'geotime\models\TerritoryWithPeriod', 'type' => 'references')
    );

    /**
     * @return string
     */
    public function getFileName() {
        return $this->__getter('fileName');
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName) {
        $this->__setter('fileName', $fileName);
    }

    /**
     * @return \MongoDate
     */
    public function getUploadDate() {
        return $this->__getter('uploadDate');
    }

    /**
     * @param \MongoDate $uploadDate
     */
    public function setUploadDate($uploadDate) {
        $this->__setter('uploadDate', $uploadDate);
    }

    /**
     * @return TerritoryWithPeriod[]
     */
    public function getTerritoriesWithPeriods() {
        return $this->__getter('territoriesWithPeriods');
    }

    /**
     * @param TerritoryWithPeriod[] $territoriesWithPeriods
     */
    public function setTerritoriesWithPeriods($territoriesWithPeriods) {
        $this->__setter('territoriesWithPeriods', $territoriesWithPeriods);
    }

    /**
     * @param $imageMapFullName
     * @param $startDateStr
     * @param $endDateStr
     * @return Map
     */
    public static function generateAndSaveReferences($imageMapFullName, $startDateStr, $endDateStr)
    {
        $period = Period::generate($startDateStr, $endDateStr);
        $period->save();

        $territoryWithPeriod = new TerritoryWithPeriod();
        $territoryWithPeriod->setPeriod($period);
        $territoryWithPeriod->save();

        $map = new Map();
        $map->setFileName($imageMapFullName);
        $map->setTerritoriesWithPeriods(array($territoryWithPeriod));

        return $map;
    }
}

Map::$log = Logger::getLogger("main");