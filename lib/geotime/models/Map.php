<?php

namespace geotime\models;

use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");


class Map extends Model {
    static $collection = "maps";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'fileName' => array('type' => 'string'),
        'uploadDate' => array('type' => 'date'),
        'territories' => array('model' => 'geotime\models\Territory', 'type' => 'references'),
        'projection' => array('type' => 'string'),
        'rotation' => array('type' => 'array'),
        'center' => array('type' => 'array'),
        'scale' => array('type' => 'int'),
        'calibrationPoints' => array('model' => 'geotime\models\CalibrationPoint', 'type' => 'embeds')
    );

    // @codeCoverageIgnoreStart
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
     * @return \Purekid\Mongodm\Collection|Territory[]
     */
    public function getTerritories() {
        return $this->__getter('territories');
    }

    /**
     * @param Territory[] $territories
     */
    public function setTerritories($territories) {
        $this->__setter('territories', $territories);
    }

    public function loadTerritories() {
        $territories = $this->getTerritories();
        foreach($territories as $territory) {
            $territory->loadReferencedTerritory();
        }
        $this->__setter('territories', $territories);
    }

    /**
     * @return string
     */
    public function getProjection() {
        return $this->__getter('projection');
    }

    /**
     * @param string $projection
     */
    public function setProjection($projection) {
        $this->__setter('projection', $projection);
    }

    /**
     * @return float[]
     */
    public function getRotation() {
        return $this->__getter('rotation');
    }

    /**
     * @param string $rotation
     */
    public function setRotation($rotation) {
        $this->__setter('rotation', $rotation);
    }

    /**
     * @return array
     */
    public function getCenter() {
        return $this->__getter('center');
    }

    /**
     * @param array $center
     */
    public function setCenter($center) {
        $this->__setter('center', $center);
    }

    /**
     * @return int
     */
    public function getScale() {
        return $this->__getter('scale');
    }

    /**
     * @param int $scale
     */
    public function setScale($scale) {
        $this->__setter('scale', $scale);
    }

    /**
     * @return \Purekid\Mongodm\Collection|CalibrationPoint[]
     */
    public function getCalibrationPoints() {
        return $this->__getter('calibrationPoints');
    }

    /**
     * @param CalibrationPoint[] $calibrationPoints
     */
    public function setCalibrationPoints($calibrationPoints) {
        $this->__setter('calibrationPoints', $calibrationPoints);
    }

    // @codeCoverageIgnoreEnd

    /**
     * @param $imageMapFullName
     * @param $startDateStr
     * @param $endDateStr
     * @return Map
     */
    public static function generateAndSaveReferences($imageMapFullName, $startDateStr, $endDateStr)
    {
        self::$log->debug('Generating references for map '.$imageMapFullName);

        $period = Period::generate($startDateStr, $endDateStr);

        $territory = new Territory();
        $territory->setPeriod($period);
        $territory->save();

        $map = new Map();
        $map->setFileName($imageMapFullName);
        $map->setTerritories(array($territory));

        return $map;
    }

    /**
     * @param $territory Territory
     */
    public function addTerritory($territory) {
        $this->getTerritories()->add($territory);
    }

    public function deleteTerritories() {
        self::$log->debug('Deleting territories from map '.$this->getFileName());

        foreach($this->getTerritories() as $territory) {
            $territory->getPeriod()->delete();
            $territory->delete();
        }
    }

    public function getIdAsString() {
        $subKey='$id';
        $id = $this->getId();
        return $id->$subKey;
    }

    /**
     * @return object
     */
    public function __toSimplifiedObject() {
        $arr = $this->toArray(array('_type','_id'), true, 5);
        $arr['id'] = $this->getIdAsString();
        return json_decode(json_encode($arr));
    }
}

Map::$log = Logger::getLogger("main");