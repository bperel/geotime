<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class TerritoryWithPeriod extends Model {
    static $collection = "territoriesWithPeriods";

    protected static $attrs = array(
        'territory' => array('model'=>'geotime\models\Territory','type'=>'reference'),
        'period' => array('model'=>'geotime\models\Period','type'=>'reference')
    );

    /**
     * @return Territory
     */
    public function getTerritory()
    {
        return $this->__getter('territory');
    }

    /**
     * @param Territory $territory
     */
    public function setTerritory($territory)
    {
        $this->__setter('territory', $territory);
    }

    /**
     * @return Period
     */
    public function getPeriod()
    {
        return $this->__getter('period');
    }

    /**
     * @param Period $period
     */
    public function setPeriod($period)
    {
        $this->__setter('period', $period);
    }

    /**
     * @param Period $period
     * @param bool $locatedTerritoriesOnly
     * @return int
     */
    public static function countTerritories($period, $locatedTerritoriesOnly=false) {
        $parameters = array('period.$id'=>new \MongoId($period->getId()));
        if ($locatedTerritoriesOnly ) {
            $parameters['territory'] = array('$exists'=>true);
        }

        return TerritoryWithPeriod::count($parameters);
    }


} 