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

    /**
     * @param $fileName string
     * @param $callback
     */
    public static function importFromJson($fileName, $callback)
    {
        if (file_exists($fileName)) {
            $data = json_decode(file_get_contents($fileName));
            if (is_array($data)) {
                array_map($callback, $data);
            }
        }
    }
}

/** @var EntityManager $entityManager */
ModelHelper::setEm($entityManager);