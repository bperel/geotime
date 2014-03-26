<?php

namespace geotime;

use geotime\models\Period;
use geotime\models\Territory;
use geotime\models\TerritoryWithPeriod;
use geotime\models\Map;
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
        $periods = Period::find(array(), array('start'=>1, 'end'=>1));

        foreach($periods as $period) {
            $territoriesCount = TerritoryWithPeriod::countTerritories($period);
            $locatedTerritoriesCount = TerritoryWithPeriod::countTerritories($period, true);
            $periodsAndTerritoriesCount[$period->__toString()] = array('total'=>$territoriesCount, 'located'=>$locatedTerritoriesCount);
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
            self::$log->info($periodStr.' : '.$territoryCount['total'].' territories referenced, '.$territoryCount['located'].' of them located');
        }
    }

    static function clean($keepMaps=false) {
        if (!$keepMaps) {
            Map::drop();
        }
        TerritoryWithPeriod::drop();
        Territory::drop();
        Period::drop();
    }
}

Geotime::$log = Logger::getLogger("main");

?>