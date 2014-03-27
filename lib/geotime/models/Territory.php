<?php

namespace geotime\models;

use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");

class Territory extends Model {
    static $collection = "territories";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'name' => array('type' => 'string'),
        'polygon' => array('type' => 'object'),
        'area' => array('type' => 'int'),
        'period' => array('model' => 'geotime\models\Period', 'type' => 'reference')
    );

    static $equatorialRadius = 6378137;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->__getter('name');
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->__setter('name', $name);
    }

    /**
     * @return array
     */
    public function getPolygon()
    {
        $field = $this->__getter('polygon');
        if (!is_null($field)) {
            return $field->{'$geoWithin'}['$polygon'];
        }
        return null;
    }

    /**
     * @param array $polygon
     */
    public function setPolygon($polygon)
    {
        if (!is_null($polygon)) {
            $polygon = array('$geoWithin' => array('$polygon'=>$polygon));
        }
        $this->__setter('polygon', $polygon);
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

    protected function __preSave()
    {
        $this->setArea($this->calculateArea());
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