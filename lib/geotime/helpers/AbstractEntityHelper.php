<?php

namespace geotime\helpers;

class AbstractEntityHelper {
    private static $flushMode = true;

    static function getTableName() {
        return null;
    }

    /**
     * @param $mode boolean
     */
    static function setFlushMode($mode) {
        self::$flushMode =$mode;
    }

    static function persist($entity) {
        ModelHelper::getEm()->persist($entity);
    }

    static function flush($force = false) {
        if (self::$flushMode || $force) {
            ModelHelper::getEm()->flush();
        }
    }
}