<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class CalibrationPoint extends Model {

    protected static $attrs = array(
        'bgPoint' => array('model' => 'geotime\models\CoordinateLatLng', 'type' => 'embed'),
        'fgPoint' => array('model' => 'geotime\models\CoordinateXY', 'type' => 'embed'),
    );

    // @codeCoverageIgnoreStart
    /**
     * @return CoordinateLatLng
     */
    public function getBgPoint() {
        return $this->__getter('bgPoint');
    }

    /**
     * @param CoordinateLatLng $bgPoint
     */
    public function setBgPoint($bgPoint) {
        $this->__setter('bgPoint', $bgPoint);
    }

    /**
     * @return CoordinateXY
     */
    public function getFgPoint() {
        return $this->__getter('fgPoint');
    }

    /**
     * @param CoordinateXY $fgPoint
     */
    public function setFgPoint($fgPoint) {
        $this->__setter('fgPoint', $fgPoint);
    }
    // @codeCoverageIgnoreEnd

    /**
     * \stdClass @param $calibrationPoint
     * @return CalibrationPoint
     */
    public static function generateFromStrings($calibrationPoint) {
        $oCalibrationPoint = new CalibrationPoint();

        $bgPointCoordinates = new CoordinateLatLng();
        $bgPointCoordinates->setLng($calibrationPoint->bgMap->lng);
        $bgPointCoordinates->setLat($calibrationPoint->bgMap->lat);

        $fgPointCoordinates = new CoordinateXY();
        $fgPointCoordinates->setX($calibrationPoint->fgMap->x);
        $fgPointCoordinates->setY($calibrationPoint->fgMap->y);

        $oCalibrationPoint->setBgPoint($bgPointCoordinates);
        $oCalibrationPoint->setFgPoint($fgPointCoordinates);

        return $oCalibrationPoint;
    }
} 