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
        'position' => array('type' => 'array'),
        'projection' => array('type' => 'string')
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
     * @return \Purekid\Mongodm\Collection
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

    /**
     * @return array
     */
    public function getPosition() {
        return $this->__getter('position');
    }

    /**
     * @param array $position
     */
    public function setPosition($position) {
        $this->__setter('position', $position);
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

    public function deleteReferences() {
        self::$log->debug('Deleting references of map '.$this->getFileName());

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