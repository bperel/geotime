<?php
namespace geotime\models\mariadb;

/**
 * @Entity @Table(name="referencedTerritories")
 **/
class ReferencedTerritory
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /** @Column(type="string") **/
    var $name;

    /**
     * @ManyToMany(targetEntity="ReferencedTerritory")
     * @JoinTable(name="previous_referenced_territories")
     */
    var $previous;

    /**
     * @ManyToMany(targetEntity="ReferencedTerritory")
     * @JoinTable(name="next_referenced_territories")
     */
    var $next;

    /**
     * @Column(type="calibrationPoint", nullable= true)
     * @var CalibrationPoint
     */
    private $calibrationPoint;

    /**
     * ReferencedTerritory constructor.
     * @param $name
     * @param $previous
     * @param $next
     */
    public function __construct($name, $previous = array(), $next = array())
    {
        $this->name = $name;
        $this->previous = $previous;
        $this->next = $next;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return ReferencedTerritory[]
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * @param ReferencedTerritory[] $previous
     */
    public function setPrevious($previous)
    {
        $this->previous = $previous;
    }

    /**
     * @return ReferencedTerritory[]
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param ReferencedTerritory[] $next
     */
    public function setNext($next)
    {
        $this->next = $next;
    }

    /**
     * @return CalibrationPoint
     */
    public function getCalibrationPoint()
    {
        return $this->calibrationPoint;
    }

    /**
     * @param CalibrationPoint $calibrationPoint
     */
    public function setCalibrationPoint($calibrationPoint)
    {
        $this->calibrationPoint = $calibrationPoint;
    }
    // @codeCoverageIgnoreEnd
}