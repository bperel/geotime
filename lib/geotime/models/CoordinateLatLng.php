<?php

namespace geotime\models\mariadb;


class CoordinateLatLng {

    /** @var $lat float */
    var $lat;

    /** @var $lng float */
    var $lng;

    /**
     * CoordinateLatLng constructor.
     * @param float $lat
     * @param float $lng
     */
    public function __construct($lat, $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     */
    public function setLng($lng)
    {
        $this->lng = $lng;
    }
    // @codeCoverageIgnoreEnd
}
