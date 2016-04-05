<?php
namespace geotime\helpers;
use geotime\models\CalibrationPoint;
use geotime\models\CoordinateLatLng;
use geotime\models\CoordinateXY;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class CalibrationPointHelper
{
    /** @var \Logger */
    static $log;

    /**
     * @param $calibrationPoint \stdClass
     * @return CalibrationPoint
     */
    public static function generateFromStrings($calibrationPoint) {

        $bgPointCoordinates = new CoordinateLatLng($calibrationPoint->bgPoint->lat, $calibrationPoint->bgPoint->lng);
        $fgPointCoordinates = new CoordinateXY($calibrationPoint->fgPoint->x, $calibrationPoint->fgPoint->y);

        return new CalibrationPoint($bgPointCoordinates, $fgPointCoordinates);
    }

    /**
     * @param $calibrationPoint CalibrationPoint
     * @param $coordinates \stdClass
     */
    public static function addCoordinatesForCalibrationPoint(&$calibrationPoint, $type, $coordinates) {
        if ($type === 'fgPoint') {
            $calibrationPoint->setFgPoint(new CoordinateXY($coordinates->x, $coordinates->y));
        }
        else if ($type === 'bgPoint') {
            $calibrationPoint->setBgPoint(new CoordinateLatLng($coordinates->lat, $coordinates->lng));
        }
    }
}

CalibrationPointHelper::$log = Logger::getLogger("main");
