<?php

namespace geotime\models;

use Purekid\Mongodm\Model;
use geotime\Database;


class CriteriaGroup extends Model {
    static $collection = 'criteriaGroups';

    protected static $attrs = array(
        'criteria' => array('model' => 'geotime\models\Criteria', 'type' => 'references'),
        'sort' => array('type' => 'string'),
        'name' => array('type' => 'string')
    );

    /**
     * @param array $criteria
     */
    public function setCriteria(array $criteria) {
        $this->__setter('criteria', $criteria);
    }

    /**
     * @param string $sort
     */
    public function setSort($sort) {
        $this->__setter('sort', $sort);
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->__setter('name', $name);
    }

    public static function importFromJson($fileName) {
        if (!preg_match('#^[./a-zA-Z0-9]+$#', $fileName)) {
            throw new \Exception('Invalid file name for JSON import : '.$fileName."\n");
        }
        else {
            $command = 'mongoimport --jsonArray -u '.Database::$username.' -p '.Database::$password.' -d '.Database::$db.' -c '.self::$collection.' '.(getcwd())."/".$fileName. ' 2>&1';
            return shell_exec($command)."\n";
        }
    }
} 