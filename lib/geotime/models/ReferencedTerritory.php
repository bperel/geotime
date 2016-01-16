<?php
namespace geotime\models;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToOne;

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
     * @ManyToOne(targetEntity="ReferencedTerritory", fetch="EAGER")
     * @JoinColumn(name="dependency_of", referencedColumnName="id")
     **/
    var $dependencyOf;


    /**
     * ReferencedTerritory constructor.
     * @param $name
     * @param array $previous
     * @param array $next
     * @param ReferencedTerritory $dependencyOf
     */
    public function __construct($name, $previous = array(), $next = array(), $dependencyOf = null)
    {
        $this->name = $name;
        $this->previous = $previous;
        $this->next = $next;
        $this->dependencyOf = $dependencyOf;
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
     * @return ReferencedTerritory
     */
    public function getDependencyOf()
    {
        return $this->dependencyOf;
    }

    /**
     * @param ReferencedTerritory $dependencyOf
     */
    public function setDependencyOf($dependencyOf)
    {
        $this->dependencyOf = $dependencyOf;
    }
    // @codeCoverageIgnoreEnd
}
