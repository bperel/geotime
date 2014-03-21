<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class Period extends Model {
    static $collection = "periods";

    protected static $attrs = array(
        'start' => array('type' => 'timestamp'),
        'end' => array('type' => 'timestamp')
    );

    /**
     * @return \MongoTimestamp
     */
    public function getStart()
    {
        return $this->__getter('start');
    }

    /**
     * @param \MongoTimestamp $start
     */
    public function setStart($start)
    {
        $this->__setter('start', $start);
    }

    /**
     * @return \MongoTimestamp
     */
    public function getEnd()
    {
        return $this->__getter('end');
    }

    /**
     * @param \MongoTimestamp $end
     */
    public function setEnd($end)
    {
        $this->__setter('end', $end);
    }


} 