<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class Criteria extends Model {
    static $collection = "criteria";

    protected static $attrs = array(
        'key' => array('type' => 'string'),
        'value' => array('type' => 'string')
    );

    // @codeCoverageIgnoreStart
    /**
     * @return string
     */
    public function getKey() {
        return $this->__getter('key');
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->__setter('key', $key);
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->__getter('value');
    }

    /**
     * @param string $value
     */
    public function setValue($value) {
        $this->__setter('value', $value);
    }
    // @codeCoverageIgnoreEnd
} 