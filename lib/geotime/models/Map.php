<?php

namespace geotime\models\mariadb;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\PersistentCollection;

/**
 * @Entity @Table(name="maps")
 **/
class Map {
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /** @Column(type="string") **/
    var $fileName;

    /** @Column(type="datetime", nullable=true) **/
    var $uploadDate;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="Territory", mappedBy="map")
     **/
    var $territories;

    /** @Column(type="string", nullable=true) **/
    var $projection;

    /** @Column(type="simple_array", nullable=true) **/
    var $rotation;

    /** @Column(type="simple_array", nullable=true) **/
    var $center;

    /** @Column(type="integer", nullable=true) **/
    var $scale;

    /** @Column(type="json_array", nullable=true) **/
    var $calibrationPoints;

    public function __construct()
    {
        $this->territories = new ArrayCollection();
    }

    // @codeCoverageIgnoreStart


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return \DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * @param \DateTime $uploadDate
     */
    public function setUploadDate($uploadDate)
    {
        $this->uploadDate = $uploadDate;
    }

    /**
     * @return PersistentCollection|Territory[]
     */
    public function getTerritories()
    {
        return $this->territories;
    }

    /**
     * @param Territory[] $territories
     */
    public function setTerritories($territories)
    {
        $this->territories = $territories;
    }

    /**
     * @param $territory Territory
     */
    public function addOrUpdateTerritory($territory) {
        if (!is_null($territory->getId())) {
            $this->territories->remove($territory->getId());
        }
        $this->territories->add($territory);
    }

    /**
     * @return string
     */
    public function getProjection()
    {
        return $this->projection;
    }

    /**
     * @param string $projection
     */
    public function setProjection($projection)
    {
        $this->projection = $projection;
    }

    /**
     * @return array
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param array $rotation
     */
    public function setRotation($rotation)
    {
        $this->rotation = $rotation;
    }

    /**
     * @return array
     */
    public function getCenter()
    {
        return $this->center;
    }

    /**
     * @param array $center
     */
    public function setCenter($center)
    {
        $this->center = $center;
    }

    /**
     * @return integer
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @param integer $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return CalibrationPoint[]
     */
    public function getCalibrationPoints()
    {
        return $this->calibrationPoints;
    }

    /**
     * @param CalibrationPoint[] $calibrationPoints
     */
    public function setCalibrationPoints($calibrationPoints)
    {
        $this->calibrationPoints = $calibrationPoints;
    }
    // @codeCoverageIgnoreEnd
}
