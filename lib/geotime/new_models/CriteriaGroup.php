<?php
namespace geotime\models\mariadb;

/**
 * @Entity @Table(name="criteriaGroups")
 **/
class CriteriaGroup
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /** @Column(type="string") **/
    var $type;


    /** @Column(type="object") **/
    var $criteria;


    /** @Column(type="object") **/
    var $optional;


    /** @Column(type="simple_array") **/
    var $sort;


    /** @Column(type="object") **/
    var $name;

    /**
     * CriteriaGroup constructor.
     */
    public function __construct()
    {
        $this->criteria = new \stdClass();
        $this->optional = new \stdClass();
    }

    // @codeCoverageIgnoreStart
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return \stdClass
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param \stdClass $criteria
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @return \stdClass
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * @param \stdClass $optional
     */
    public function setOptional($optional)
    {
        $this->optional = $optional;
    }

    /**
     * @return array
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param array $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    // @codeCoverageIgnoreEnd

}