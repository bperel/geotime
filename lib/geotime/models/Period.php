<?php

namespace geotime\models;

use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");

class Period extends Model {
    static $collection = "periods";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'start' => array('type' => 'date'),
        'end' => array('type' => 'date')
    );

    /**
     * @return \MongoDate
     */
    public function getStart()
    {
        return $this->__getter('start');
    }

    public function getStartYear() {
        return date('Y', $this->getStart()->sec);
    }

    /**
     * @param \MongoDate $start
     */
    public function setStart($start)
    {
        $this->__setter('start', $start);
    }

    /**
     * @return \MongoDate
     */
    public function getEnd()
    {
        return $this->__getter('end');
    }

    public function getEndYear() {
        return date('Y', $this->getEnd()->sec);
    }

    /**
     * @param \MongoDate $end
     */
    public function setEnd($end)
    {
        $this->__setter('end', $end);
    }

    /**
     * @return string
     */
    public function __toStringShort() {
        return $this->getStartYear().'-'.$this->getEndYear();
    }

    /**
     * @return string
     */
    public function __toString() {
        return 'Period '.$this->getStartYear().' to '.$this->getEndYear();
    }

    public static function generate($startDateStr, $endDateStr) {
        $period = new Period();
        $period->setStart(new \MongoDate(strtotime($startDateStr)));
        $period->setEnd(new \MongoDate(strtotime($endDateStr)));

        return $period;
    }


}

Period::$log = Logger::getLogger("main");