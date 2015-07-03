<?php

namespace geotime\models\mariadb;

/**
 * @Entity @Table(name="territories")
 **/
class Territory {

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /**
     * @ManyToOne(targetEntity="ReferencedTerritory")
     * @JoinColumn(name="referenced_territory", referencedColumnName="id")
     **/
    var $referencedTerritory;

    /** @Column(type="object") **/
    var $polygon;

    /** @Column(type="integer") **/
    var $area;

    /** @Column(type="string") **/
    var $xpath;

    /** @Column(type="datetime") **/
    var $startDate;

    /** @Column(type="datetime") **/
    var $endDate;

    /** @Column(type="boolean") **/
    var $userMade;

    /**
     * @return ReferencedTerritory
     */
    public function getReferencedTerritory()
    {
        return $this->referencedTerritory;
    }

    /**
     * @param ReferencedTerritory $referencedTerritory
     */
    public function setReferencedTerritory($referencedTerritory)
    {
        $this->referencedTerritory = $referencedTerritory;
    }

    /**
     * @return \stdClass
     */
    public function getPolygon()
    {
        return $this->polygon;
    }

    /**
     * @param \stdClass $polygon
     */
    public function setPolygon($polygon)
    {
        $this->polygon = $polygon;
    }

    /**
     * @return integer
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param integer $area
     */
    public function setArea($area)
    {
        $this->area = $area;
    }

    /**
     * @return string
     */
    public function getXpath()
    {
        return $this->xpath;
    }

    /**
     * @param string $xpath
     */
    public function setXpath($xpath)
    {
        $this->xpath = $xpath;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return boolean
     */
    public function getUserMade()
    {
        return $this->userMade;
    }

    /**
     * @param boolean $userMade
     */
    public function setUserMade($userMade)
    {
        $this->userMade = $userMade;
    }
}