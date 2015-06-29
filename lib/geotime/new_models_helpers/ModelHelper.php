<?php
namespace geotime\helpers;
use Doctrine\ORM\EntityManager;

class ModelHelper {
    /** @var EntityManager $incompleteMap */
    public static $em;

    /**
     * @return EntityManager
     */
    public static function getEm()
    {
        return self::$em;
    }
}

/** @var EntityManager $entityManager */
ModelHelper::$em = $entityManager;