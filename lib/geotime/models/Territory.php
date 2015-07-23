<?php

namespace geotime\models\mariadb;

include_once('ReferencedTerritory.php');

/**
 * @Entity @Table(name="territories")
 **/
class Territory {
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /**
     * @ManyToOne(targetEntity="ReferencedTerritory")
     * @JoinColumn(name="referenced_territory", referencedColumnName="id")
     **/
    var $referencedTerritory;

    /** @Column(type="object", nullable=true) **/
    var $polygon;

    /** @Column(type="integer", nullable=true) **/
    var $area;

    /** @Column(type="boolean") **/
    var $userMade;

    /** @Column(type="string", nullable=true) **/
    var $xpath;

    /** @Column(type="datetime", nullable=true) **/
    var $startDate;

    /** @Column(type="datetime", nullable=true) **/
    var $endDate;

    /**
     * @ManyToOne(targetEntity="Map", cascade={"persist","remove"})
     * @JoinColumn(name="map", referencedColumnName="id")
     **/
    var $map;

    function __construct($referencedTerritory, $userMade, $polygon = null, $area = 0, $xpath = null, $startDate = null, $endDate = null)
    {
        $this->referencedTerritory = $referencedTerritory;
        $this->polygon = $polygon;
        $this->area = $area;
        $this->xpath = $xpath;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userMade = $userMade;
    }

    // @codeCoverageIgnoreStart
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
     * @return bool|string
     */
    public function getStartYear() {
        return date('Y', $this->getStartDate());
    }

    /**
     * @return bool|string
     */
    public function getEndYear() {
        return date('Y', $this->getEndDate());
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

    /**
     * @return Map
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param Map $map
     */
    public function setMap($map)
    {
        $this->map = $map;
    }
    // @codeCoverageIgnoreEnd
}