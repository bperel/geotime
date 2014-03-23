<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class Period extends Model {
    static $collection = "periods";

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
    public function __toString() {
        return 'Period '.date('Y', $this->getStart()->sec).' to '.date('Y', $this->getEnd()->sec);
    }


} 