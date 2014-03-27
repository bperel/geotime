<?php

namespace geotime\models;

use geotime\Database;
use Purekid\Mongodm\Model;


class CriteriaGroup extends Model {
    static $collection = 'criteriaGroups';

    protected static $attrs = array(
        'criteriaList' => array('model' => 'geotime\models\Criteria', 'type' => 'embeds'),
        'sort' => array('type' => 'array'),
        'name' => array('type' => 'string')
    );

    /**
     * @return Criteria[]
     */
    public function getCriteriaList() {
        return $this->__getter('criteriaList');
    }

    /**
     * @param array $criteriaList
     */
    public function setCriteriaList(array $criteriaList) {
        $this->__setter('criteriaList', $criteriaList);
    }

    /**
     * @return array
     */
    public function getSort() {
        return $this->__getter('sort');
    }

    /**
     * @param array $sort
     */
    public function setSort($sort) {
        $this->__setter('sort', $sort);
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->__getter('name');
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->__setter('name', $name);
    }

    /**
     * @param $fileName : JSON file to import
     * @return integer|null Number of imported object, or NULL on error
     * @throws \InvalidArgumentException
     */
    public static function importFromJson($fileName) {
        if (!preg_match('#^[./_a-zA-Z0-9]+$#', $fileName)) {
            throw new \InvalidArgumentException('Invalid file name for JSON import : '.$fileName."\n");
        }
        else {
            $command = 'mongoimport --jsonArray -u '.Database::$username.' -p '.Database::$password.' -d '.Database::$db.' -c '.self::$collection.' '.(getcwd())."/".$fileName. ' 2>&1';
            $status = shell_exec($command);
            preg_match('#imported ([\d]+) objects$#', $status, $match);
            return intval($match[1]);
        }
    }
} 