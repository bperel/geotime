<?php
namespace geotime\helpers;

use geotime\models\mariadb\CriteriaGroup;
use geotime\new_models\AbstractEntityHelper;

class CriteriaGroupHelper implements AbstractEntityHelper
{
    static $cachePath = 'data/criteriaGroups.json';

    public static function importFromJson($fileName=null) {
        if (is_null($fileName)) {
            $fileName = self::$cachePath;
        }

        $tableName = self::getTableName();

        ModelHelper::importFromJson($fileName, function($object) use ($tableName) {
            $criteriaGroup = new CriteriaGroup();
            $criteriaGroup->setName($object->name);
            $criteriaGroup->setSort($object->sort);
            $criteriaGroup->setType($object->type);
            $criteria = new \stdClass();
            foreach($object->criteria as $criterium) {
                $criteria->{$criterium->key}=$criterium->value;
            }
            $criteriaGroup->setCriteria($criteria);
            $optional = new \stdClass();
            foreach($object->criteria as $criterium) {
                $optional->{$criterium->key}=$criterium->value;
            }
            $criteriaGroup->setOptional($optional);

            ModelHelper::getEm()->persist($criteriaGroup);
        });

        ModelHelper::getEm()->flush();
    }

    /**
     * @return int
     */
    public static function count() {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(criteriaGroup.id)');
        $qb->from(CriteriaGroup::CLASSNAME,'criteriaGroup');

        return $qb->getQuery()->getSingleScalarResult();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(CriteriaGroup::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}