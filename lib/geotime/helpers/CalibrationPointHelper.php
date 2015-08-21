<?php
namespace geotime\helpers;
use geotime\models\mariadb\CalibrationPoint;
use geotime\models\mariadb\CoordinateLatLng;
use geotime\models\mariadb\CoordinateXY;
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

        $bgPointCoordinates = new CoordinateLatLng($calibrationPoint->bgMap->lng, $calibrationPoint->bgMap->lat);
        $fgPointCoordinates = new CoordinateXY($calibrationPoint->fgMap->x, $calibrationPoint->fgMap->y);

        return new CalibrationPoint($bgPointCoordinates, $fgPointCoordinates);
    }

    /**
     * @param $calibrationPoint CalibrationPoint
     * @param $coordinates \stdClass
     */
    public static function addCoordinatesForCalibrationPoint(&$calibrationPoint, $type, $coordinates) {
        if ($type === 'fgMap') {
            $calibrationPoint->setFgPoint(new CoordinateXY($coordinates->x, $coordinates->y));
        }
        else if ($type === 'bgMap') {
            $calibrationPoint->setBgPoint(new CoordinateLatLng($coordinates->lng, $coordinates->lat));
        }
    }
}

CalibrationPointHelper::$log = Logger::getLogger("main");
