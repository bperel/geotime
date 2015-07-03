<?php
namespace geotime\helpers;
use geotime\models\mariadb\CalibrationPoint;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class CalibrationPointHelper
{
    /** @var \Logger */
    static $log;

    /**
     * \stdClass @param $calibrationPoint
     * @return CalibrationPoint
     */
    public static function generateFromStrings($calibrationPoint) {

        $bgPointCoordinates = new CoordinateLatLng();
        $bgPointCoordinates->setLng($calibrationPoint->bgMap->lng);
        $bgPointCoordinates->setLat($calibrationPoint->bgMap->lat);

        $fgPointCoordinates = new CoordinateXY();
        $fgPointCoordinates->setX($calibrationPoint->fgMap->x);
        $fgPointCoordinates->setY($calibrationPoint->fgMap->y);

        return new CalibrationPoint($bgPointCoordinates, $fgPointCoordinates);
    }
}

CalibrationPointHelper::$log = Logger::getLogger("main");