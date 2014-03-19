<?php

namespace geotime\models;

use Purekid\Mongodm\Model;
use geotime\Database;


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
     * @param $fileName : Fichier JSON à importer
     * @return string Code de retour de la commande
     * @throws \InvalidArgumentException
     */
    public static function importFromJson($fileName) {
        if (!preg_match('#^[./a-zA-Z0-9]+$#', $fileName)) {
            throw new \InvalidArgumentException('Invalid file name for JSON import : '.$fileName."\n");
        }
        else {
            $command = 'mongoimport --jsonArray -u '.Database::$username.' -p '.Database::$password.' -d '.Database::$db.' -c '.self::$collection.' '.(getcwd())."/".$fileName. ' 2>&1';
            return shell_exec($command)."\n";
        }
    }
} 