<?php

namespace geotime\models\mariadb;


class CalibrationPoint {

    /** @var $bgPoint CoordinateLatLng */
    var $bgPoint;

    /** @var $fgPoint CoordinateXY */
    var $fgPoint;

    /**
     * @param $bgPoint CoordinateLatLng
     * @param $fgPoint CoordinateXY
     */
    public function __construct($bgPoint, $fgPoint)
    {
        $this->bgPoint  = $bgPoint;
        $this->fgPoint = $fgPoint;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return CoordinateLatLng
     */
    public function getBgPoint()
    {
        return $this->bgPoint;
    }

    /**
     * @param CoordinateLatLng $bgPoint
     */
    public function setBgPoint($bgPoint)
    {
        $this->bgPoint = $bgPoint;
    }

    /**
     * @return CoordinateXY
     */
    public function getFgPoint()
    {
        return $this->fgPoint;
    }

    /**
     * @param CoordinateXY $fgPoint
     */
    public function setFgPoint($fgPoint)
    {
        $this->fgPoint = $fgPoint;
    }
    // @codeCoverageIgnoreEnd

} 