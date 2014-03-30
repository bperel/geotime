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
        return date('Y', $this->getStart()->sec).'-'.date('Y', $this->getEnd()->sec);
    }

    /**
     * @return string
     */
    public function __toString() {
        return 'Period '.date('Y', $this->getStart()->sec).' to '.date('Y', $this->getEnd()->sec);
    }

    public static function generate($startDateStr, $endDateStr) {
        $period = new Period();
        $period->setStart(new \MongoDate(strtotime($startDateStr)));
        $period->setEnd(new \MongoDate(strtotime($endDateStr)));

        return $period;
    }


}

Period::$log = Logger::getLogger("main");