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
     * @param $tableName string
     * @param $fileName string
     *
     * @return void
     */
    public static function importFromJson($tableName, $fileName)
    {
        if (file_exists($fileName)) {
            $data = json_decode(file_get_contents($fileName));
            if (is_array($data)) {
                $connection = ModelHelper::getEm()->getConnection();
                foreach($data as $tuple) {
                    if (is_object($tuple)) {
                        $connection->insert($tableName, (array)$tuple);
                    }
                }
            }
        }
    }
}

/** @var EntityManager $entityManager */
ModelHelper::setEm($entityManager);