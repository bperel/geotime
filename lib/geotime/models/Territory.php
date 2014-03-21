<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class Territory extends Model {
    static $collection = "territories";

    protected static $attrs = array(
        'name' => array('type' => 'string'),
        'polygon' => array('type' => 'object')
    );

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
     * @return object
     */
    public function getPolygon()
    {
        return $this->__getter('polygon');
    }

    /**
     * @param object $polygon
     */
    public function setPolygon($polygon)
    {
        $this->__setter('polygon', $polygon);
    }


} 