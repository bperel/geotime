<?php

namespace geotime;

use geotime\models\Period;
use geotime\models\TerritoryWithPeriod;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Geotime {

    /** @var \Logger */
    static $log;

    /**
     * @return array
     */
    static function getPeriodsAndTerritoriesCount() {

        $periodsAndTerritoriesCount = array();

        /** @var Period[] $periods */
        $periods = Period::find();

        foreach($periods as $period) {
            $periodsAndTerritoriesCount[$period->__toString()] = TerritoryWithPeriod::count(array('period.$id'=>new \MongoId($period->getId())));
        }

        return $periodsAndTerritoriesCount;
    }

    /**
     * @ return void
     */
    static function showStatus() {
        $periodsAndTerritoriesCount = self::getPeriodsAndTerritoriesCount();

        self::$log->info(count($periodsAndTerritoriesCount).' periods found');
        foreach($periodsAndTerritoriesCount as $periodStr=>$territoryCount) {
            self::$log->info($periodStr.' : '.$territoryCount.' territories located');
        }
    }
}

Geotime::$log = Logger::getLogger("main");

?>