<?php

namespace geotime;

use geotime\models\Map;
use geotime\models\Period;
use geotime\models\Territory;
use Logger;

Logger::configure("lib/geotime/logger.xml");

include_once('Util.php');

class Geotime {

    /** @var \Logger */
    static $log;

    /**
     * @var int Natural Earth data coverage
     */
    static $optimalCoverage = 145389748;

    /**
     * @return array
     */
    static function getPeriodsAndTerritoriesCount() {

        $periodsAndTerritoriesCount = array();

        /** @var Period[] $periods */
        $periods = Period::find(array(), array('start'=>1, 'end'=>1));

        foreach($periods as $period) {
            $territoriesCount = Territory::countForPeriod($period);
            $locatedTerritoriesCount = Territory::countForPeriod($period, true);
            $periodsAndTerritoriesCount[$period->__toString()] = array('total'=>$territoriesCount, 'located'=>$locatedTerritoriesCount);
        }

        return $periodsAndTerritoriesCount;
    }

    /**
     * Get the land coverage stored for each period
     *
     * @return array An associative (Period string) => (coverage integer) array
     */
    static function getCoverageInfo() {

        $periodsAndCoverage = Territory::aggregate(
            array(
                array(
                    '$group' => array(
                        '_id' => '$period',
                        'areaSum' => array(
                            '$sum' => '$area'
                        )
                    )
                )
            )
        );

        $formattedPeriodsAndCoverage = array();
        foreach($periodsAndCoverage['result'] as $periodAndCoverage) {
            /** @var Period $period */
            $period = Period::one(array('_id'=>new \MongoId($periodAndCoverage['_id']['$id'])));

            $coverage = new \stdClass();
            $coverage->period = $period->__toStringShort();
            $coverage->coverage = $periodAndCoverage['areaSum'];

            $formattedPeriodsAndCoverage[] = $coverage;

        }

        return array('periodsAndCoverage' => $formattedPeriodsAndCoverage, 'optimalCoverage' => self::$optimalCoverage);

    }

    /**
     * @return void
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
        Territory::drop();
        Period::drop();
    }
}

Geotime::$log = Logger::getLogger("main");

?>