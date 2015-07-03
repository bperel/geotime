<?php

namespace geotime\models\mariadb;


class CoordinateXY {

    const CLASSNAME = __CLASS__;

    /** @var $x float */
    var $x;

    /** @var $y float */
    var $y;

    /**
     * @param float $x
     * @param float $y
     */
    function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param float $x
     */
    public function setX($x)
    {
        $this->x = $x;
    }

    /**
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param float $y
     */
    public function setY($y)
    {
        $this->y = $y;
    }
    // @codeCoverageIgnoreEnd
}