<?php

namespace geotime\models;

use geotime\Util;
use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");

class Territory extends Model {
    static $collection = "territories";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'referencedTerritory' => array('type' => 'reference', 'model' => 'geotime\models\ReferencedTerritory'),
        'polygon'             => array('type' => 'object'),
        'area'                => array('type' => 'int'),
        'xpath'               => array('type' => 'string'),
        'period'              => array('type' => 'embed', 'model' => 'geotime\models\Period'),
        'userMade'            => array('type' => 'boolean')
    );

    static $equatorialRadius = 6378137;

    /**
     * @param ReferencedTerritory $referencedTerritory
     * @param $object \stdClass
     * @return Territory
     */
    public static function buildAndSaveFromObjectAndReferencedTerritory($referencedTerritory, $object) {
        $fields = array(
            'startDate' => 'date1',
            'endDate' => 'date2'
        );
        $fieldValues = array();
        foreach($fields as $mappedField => $optionalField) {
            if (isset($object->$optionalField)) {
                $fieldValues[$mappedField] = $object->$optionalField->value;
            }
            else {
                $fieldValues[$mappedField] = '';
            }
        }

        return self::buildAndCreateWithReferencedTerritory($referencedTerritory, false, $fieldValues['startDate'], $fieldValues['endDate']
        );
    }

    public static function buildAndCreateFromNEData($referencedTerritory, $coordinates) {
        $territory = new Territory();
        $territory->setPolygon($coordinates);
        return $territory->buildAndSave($referencedTerritory, false);
    }

    /**
     * @param ReferencedTerritory $referencedTerritory
     * @param $usermade
     * @param string $startDate
     * @param string $endDate
     * @param string $xpath
     * @return Territory
     */
    public static function buildAndCreateWithReferencedTerritory($referencedTerritory, $usermade, $startDate = '', $endDate = '', $xpath = '') {
        $territory = new Territory();
        return $territory->buildAndSave($referencedTerritory, $usermade, $startDate, $endDate, $xpath);
    }

    /**
     * @param $referencedTerritory ReferencedTerritory
     * @param $userMade boolean
     * @param $startDate string
     * @param $endDate string
     * @param $xpath string
     * @return Territory
     */
    private function buildAndSave($referencedTerritory, $userMade, $startDate = '', $endDate = '', $xpath = null) {
        $this->setReferencedTerritory($referencedTerritory);
        if (!empty($startDate) && !empty($endDate)) {
            $this->setPeriod(Period::generate($startDate, $endDate));
        }
        if (!empty($xpath)) {
            $this->setXpath($xpath);
        }
        $this->setUserMade($userMade);
        $this->save();

        return $this;
    }

    /**
     * @param ReferencedTerritory $referencedTerritory
     * @return array
     */
    public static function getReferencedTerritoryFilter($referencedTerritory) {
        return array('referencedTerritory.$id' => new \MongoId($referencedTerritory->getId()));
    }

    // @codeCoverageIgnoreStart

    /**
     * @return ReferencedTerritory
     */
    public function getReferencedTerritory()
    {
        return $this->__getter('referencedTerritory');
    }

    /**
     * @param ReferencedTerritory $referencedTerritory
     */
    public function setReferencedTerritory($referencedTerritory)
    {
        $this->__setter('referencedTerritory', $referencedTerritory);
    }

    public function loadReferencedTerritory() {
        $this->setReferencedTerritory($this->getReferencedTerritory());
    }

    /**
     * @return int
     */
    public function getArea()
    {
        return $this->__getter('area');
    }

    /**
     * @param int $area
     */
    public function setArea($area)
    {
        $this->__setter('area', $area);
    }

    /**
     * @return Period
     */
    public function getPeriod()
    {
        return $this->__getter('period');
    }

    /**
     * @param Period $period
     */
    public function setPeriod($period)
    {
        $this->__setter('period', $period);
    }

    /**
     * @return string
     */
    public function getXpath()
    {
        return $this->__getter('xpath');
    }

    /**
     * @param string $xpath
     */
    public function setXpath($xpath)
    {
        $this->__setter('xpath', $xpath);
    }

    /**
     * @return boolean
     */
    public function getUserMade()
    {
        return $this->__getter('userMade');
    }

    /**
     * @param boolean $userMade
     */
    public function setUserMade($userMade)
    {
        $this->__setter('userMade', $userMade);
    }

    // @codeCoverageIgnoreEnd

    /**
     * @return array
     */
    public function getPolygon()
    {
        return (array) $this->__getter('polygon');
    }

    /**
     * @param array $polygon
     */
    public function setPolygon($polygon)
    {
        $this->__setter('polygon', $polygon);
    }

    protected function __preSave()
    {
        $this->setArea($this->calculateArea());
    }

    private function getElementIdFromPath() {
        return preg_replace('#^\/\/path\[id="([^"]+)"\]$#', '$1', $this->getXpath());
    }

    /**
     * @param Map $map
     * @return string
     */
    public function calculateCoordinates($map)
    {
        return Util::calculatePathCoordinates(
            $map->getFileName(),
            $this->getElementIdFromPath(),
            $map->getProjection(),
            $map->getCenter(),
            $map->getScale(),
            $map->getRotation()
        );
    }

    /**
     * Calculate the approximate area of the polygon were it projected onto
     *     the earth.  Note that this area will be positive if ring is oriented
     *     clockwise, otherwise it will be negative.
     *
     * Reference:
     * Robert. G. Chamberlain and William H. Duquette, "Some Algorithms for
     *     Polygons on a Sphere", JPL Publication 07-03, Jet Propulsion
     *     Laboratory, Pasadena, CA, June 2007 http://trs-new.jpl.nasa.gov/dspace/handle/2014/40409
     *
     * Adapted from https://github.com/mapbox/geojson-area
     *
     * @return int|null The approximate signed geodesic area of the polygon in square kilometers.
     */
    function calculateArea() {
        $coords = $this->getPolygon();
        if (!is_null($coords)) {
            $area = 0;
            foreach($coords as $landCoords) {
                $area += $this->calculateLandArea($landCoords[0]); // The natural data export encapsulates the coords in an extra array
            }

            return intval(abs($area) / pow(10,6));
        }
        return null;
    }

    /**
     * @param $landCoords
     * @return float
     */
    public function calculateLandArea($landCoords)
    {
        $area = 0;
        if (count($landCoords) > 2) {
            for ($i = 0; $i < count($landCoords) - 1; $i++) {
                $p1 = $landCoords[$i];
                $p2 = $landCoords[$i + 1];
                $area += self::rad($p2[0] - $p1[0]) * (2 + sin(self::rad($p1[1])) + sin(self::rad($p2[1])));
            }
            $area = $area * self::$equatorialRadius * self::$equatorialRadius / 2;
            return $area;
        }
        return $area;
    }

    private static function rad($measure) {
        return $measure * pi() / 180;
    }

    /**
     * @param Period $period
     * @param bool $locatedTerritoriesOnly
     * @return int
     */
    public static function countForPeriod($period, $locatedTerritoriesOnly=false) {
        $parameters = array('period.$id'=>new \MongoId($period->getId()));
        if ($locatedTerritoriesOnly) {
            $parameters['polygon'] = array('$exists'=>true);
        }

        return Territory::count($parameters);
    }
}

Territory::$log = Logger::getLogger("main");