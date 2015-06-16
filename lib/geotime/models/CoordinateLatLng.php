<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class CoordinateLatLng extends Model {

    protected static $attrs = array(
        'lat' => array('type' => 'float'),
        'lng' => array('type' => 'float')
    );

    // @codeCoverageIgnoreStart
    /**
     * @return double
     */
    public function getLat()
    {
        return $this->__getter('lat');
    }

    /**
     * @param double $lat
     */
    public function setLat($lat)
    {
        $this->__setter('lat', $lat);
    }

    /**
     * @return double
     */
    public function getLng()
    {
        return $this->__getter('lng');
    }

    /**
     * @param double $lng
     */
    public function setLng($lng)
    {
        $this->__setter('lng', $lng);
    }
    // @codeCoverageIgnoreEnd

}