<?php

namespace geotime\models;

use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");

class ReferencedTerritory extends Model
{
    static $collection = "referencedTerritories";

    /** @var \Logger */
    static $log;

    protected static $attrs = array(
        'name' => array('type' => 'string'),
        'previous' => array('type' => 'references', 'model' => 'geotime\models\ReferencedTerritory'),
        'next' => array('type' => 'references', 'model' => 'geotime\models\ReferencedTerritory')
    );

    /**
     * @param $object \stdClass
     * @return ReferencedTerritory
     */
    public static function buildAndSaveFromObject($object) {
        $fields = array(
            'name' => 'name',
            'previous' => 'previous',
            'next' => 'next'
        );
        $fieldValues = array();
        foreach($fields as $mappedField => $optionalField) {
            if (isset($object->$optionalField)) {
                $fieldValues[$mappedField] = $object->$optionalField->value;
            }
            else {
                $fieldValues[$mappedField] = '';
            }
        }

        return ReferencedTerritory::buildAndCreate($fieldValues['name'], $fieldValues['previous'], $fieldValues['next']);
    }

    /**
     * @param string $name
     * @param string $previous
     * @param string $next
     * @return \geotime\models\ReferencedTerritory
     */
    public static function buildAndCreate($name, $previous = null, $next = null) {
        $referencedTerritory = new ReferencedTerritory();
        $referencedTerritory->setName($name);
        if (!empty($previous)) {
            $referencedTerritory->setPrevious(self::referencedTerritoriesStringToTerritoryArray($previous));
        }
        if (!empty($next)) {
            $referencedTerritory->setNext(self::referencedTerritoriesStringToTerritoryArray($next));
        }
        $referencedTerritory->save();
        return $referencedTerritory;
    }

    /**
     * @param $territoriesString string
     * @return ReferencedTerritory[]
     */
    public static function referencedTerritoriesStringToTerritoryArray($territoriesString) {
        return array_map(
            function($referencedTerritoryName) {
                $referencedTerritory = ReferencedTerritory::one(array('name' => $referencedTerritoryName));
                if (is_null($referencedTerritory) && !empty($referencedTerritoryName)) {
                    $referencedTerritory = ReferencedTerritory::buildAndCreate($referencedTerritoryName);
                }
                return $referencedTerritory;
            },
            explode('|', $territoriesString)
        );
    }

    // @codeCoverageIgnoreStart

    /**
     * @return string
     */
    public function getName()
    {
        return $this->__getter('name');
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->__setter('name', $name);
    }

    /**
     * @return ReferencedTerritory[]
     */
    public function getPrevious()
    {
        return $this->__getter('previous');
    }

    /**
     * @param ReferencedTerritory[] $previous
     */
    public function setPrevious($previous)
    {
        $this->__setter('previous', $previous);
    }

    /**
     * @return ReferencedTerritory[]
     */
    public function getNext()
    {
        return $this->__getter('next');
    }

    /**
     * @param ReferencedTerritory[] $next
     */
    public function setNext($next)
    {
        $this->__setter('next', $next);
    }

    // @codeCoverageIgnoreEnd
}

ReferencedTerritory::$log = Logger::getLogger("main");