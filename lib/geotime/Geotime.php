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
     * @return void
     */
    static function showStatus() {
        $periodsAndTerritoriesCount = self::getMapsAndLocalizedTerritoriesCount();

        self::$log->info(count($periodsAndTerritoriesCount).' periods found');
        foreach($periodsAndTerritoriesCount as $periodStr=>$territoryCount) {
            self::$log->info($periodStr.' : '.$territoryCount.' territories located');
        }
    }

    /**
     * @return array
     */
    static function getMapsAndLocalizedTerritoriesCount() {

        $mapsAndTerritoriesCount = array();

        /** @var Territory[] $mapsWithLocalizedTerritories */
        $mapsWithLocalizedTerritories = Map::aggregate(
            array(
                array(
                    '$unwind' => '$territories'
                ),
                array(
                    '$group'  => array(
                        '_id' => '$fileName',
                        'territoriesCount' => array(
                            '$sum' => 1
                        )
                    )
                )
            )
        );

        foreach($mapsWithLocalizedTerritories['result'] as $map) {
            $mapsAndTerritoriesCount[$map['_id']] = $map['territoriesCount'];
        }

        return $mapsAndTerritoriesCount;
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
            $periodArray = $periodAndCoverage['_id'];

            if (is_null($periodArray)) { // No period specified <=> Natural earth data
                $period = new Period();
                $period->setStart(new \MongoDate(strtotime(NaturalEarthImporter::$dataDate)));
                $period->setEnd(new \MongoDate(strtotime(NaturalEarthImporter::$dataDate)));
            }
            else {
                $period = new Period($periodArray);
            }
            $coverage = new \stdClass();
            $coverage->period = $period->__toStringShort();
            $coverage->coverage = $periodAndCoverage['areaSum'];

            $formattedPeriodsAndCoverage[] = $coverage;
        }

        return array('periodsAndCoverage' => $formattedPeriodsAndCoverage, 'optimalCoverage' => self::$optimalCoverage);

    }

    public static function getIncompleteMapInfo($year)
    {
        $year=new \MongoDate(strtotime($year.'-01-01'));
        /** @var Territory $matchingTerritories */
        $matchingTerritory = Territory::one(array(
            'polygon' => array('$exists' => false),
            'period.start' => array('$lte' => $year),
            'period.end' => array('$gte' => $year)));

        if (!is_null($matchingTerritory)) {
            return Map::one(array('territories.$id' => new \MongoId($matchingTerritory->getId())));
        }

        return null;
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