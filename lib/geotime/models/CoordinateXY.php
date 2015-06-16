<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class CoordinateXY extends Model {

    protected static $attrs = array(
        'x' => array('type' => 'float'),
        'y' => array('type' => 'float')
    );

    // @codeCoverageIgnoreStart
    /**
     * @return double
     */
    public function getX()
    {
        return $this->__getter('x');
    }

    /**
     * @param double $x
     */
    public function setX($x)
    {
        $this->__setter('x', $x);
    }

    /**
     * @return double
     */
    public function getY()
    {
        return $this->__getter('y');
    }

    /**
     * @param double $y
     */
    public function setY($y)
    {
        $this->__setter('y', $y);
    }
    // @codeCoverageIgnoreEnd

}