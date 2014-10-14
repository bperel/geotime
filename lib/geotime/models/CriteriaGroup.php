<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class CriteriaGroup extends Model {
    static $collection = 'criteriaGroups';

    protected static $attrs = array(
        'criteria' => array('model' => 'geotime\models\Criteria', 'type' => 'embeds'),
        'optional' => array('model' => 'geotime\models\Criteria', 'type' => 'embeds'),
        'sort' => array('type' => 'array'),
        'name' => array('type' => 'string')
    );

    /**
     * @return Criteria[]
     */
    public function getCriteria() {
        return $this->__getter('criteria');
    }

    /**
     * @param Criteria[] $criteria
     */
    public function setCriteria(array $criteria) {
        $this->__setter('criteria', $criteria);
    }

    /**
     * @return Criteria[]
     */
    public function getOptional() {
        return $this->__getter('optional');
    }

    /**
     * @param Criteria[] $optional
     */
    public function setOptional(array $optional) {
        $this->__setter('optional', $optional);
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

} 