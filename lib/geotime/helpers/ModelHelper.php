<?php
namespace geotime\helpers;
use Doctrine\ORM\EntityManager;

class ModelHelper {
    /** @var EntityManager $incompleteMap */
    private static $em;

    /**
     * @return EntityManager
     */
    public static function getEm()
    {
        return self::$em;
    }

    /**
     * @param $em EntityManager
     */
    public static function setEm($em)
    {
        self::$em = $em;
    }

}