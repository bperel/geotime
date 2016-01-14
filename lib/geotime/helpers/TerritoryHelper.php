<?php
namespace geotime\helpers;
use geotime\models\Map;
use geotime\models\Territory;
use geotime\models\ReferencedTerritory;
use geotime\Util;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class TerritoryHelper extends AbstractEntityHelper
{
    /** @var \integer */
    static $equatorialRadius = 6378137;

    /** @var \Logger */
    static $log;

    /**
     * @param ReferencedTerritory $referencedTerritory
     * @param $object \stdClass
     * @return Territory
     */
    public static function buildFromObjectAndReferencedTerritory($referencedTerritory, $object) {
        $fields = array(
            'startDate' => 'date1',
            'endDate' => 'date2'
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

        return self::buildWithReferencedTerritory($referencedTerritory, $fieldValues['startDate'], $fieldValues['endDate']
        );
    }

    /**
     * @param $referencedTerritory ReferencedTerritory
     * @param $coordinates \stdClass
     * @return Territory
     */
    public static function buildAndCreateFromNEData($referencedTerritory, $coordinates) {
        $territory = new Territory($referencedTerritory, false, $coordinates);
        return self::build($territory);
    }

    /**
     * @param ReferencedTerritory $referencedTerritory
     * @param string $startDate
     * @param string $endDate
     * @param string $xpath
     * @return Territory
     */
    public static function buildWithReferencedTerritory($referencedTerritory, $startDate = '', $endDate = '', $xpath = '') {
        $territory = new Territory($referencedTerritory, true);
        return self::build($territory, $startDate, $endDate, $xpath);
    }

    /**
     * @param $territory Territory
     * @param $startDate string
     * @param $endDate string
     * @param $xpath string
     * @return Territory
     */
    public static function build($territory, $startDate = '', $endDate = '', $xpath = null) {
        if (!empty($startDate) && !empty($endDate)) {
            $territory->setStartDate(Util::createDateTimeFromString($startDate));
            $territory->setEndDate(Util::createDateTimeFromString($endDate));
        }
        if (!empty($xpath)) {
            $territory->setXpath($xpath);
        }
        $territory->setArea(self::calculateArea($territory));
        return $territory;
    }

    /**
     * @param $territoryId integer
     * @param $map Map
     * @param $referencedTerritoryId integer
     * @param $xpath string
     * @param $territoryPeriodStart string
     * @param $territoryPeriodEnd string
     * @return bool
     */
    public static function saveLocatedTerritory($territoryId, $map, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd)
    {
        if (is_null($territoryId)) {
            $referencedTerritory = ReferencedTerritoryHelper::find($referencedTerritoryId);
            if (is_null($referencedTerritory)) {
                return false;
            }

            $territory = self::buildWithReferencedTerritory(
                $referencedTerritory, $territoryPeriodStart, $territoryPeriodEnd, $xpath
            );
        } else {
            $oldTerritory = self::findOneById($territoryId);
            $territory = self::build(
                $oldTerritory, $territoryPeriodStart, $territoryPeriodEnd, $xpath
            );
        }

        $isCalibratedMap = MapHelper::isCalibratedMap($map);
        $geocoordinates = null;

        if ($isCalibratedMap) {
            $geocoordinates = self::calculateCoordinates($territory, $map);
            if (!is_null($geocoordinates)) {
                $territory->setPolygon(json_decode(json_encode(array(array($geocoordinates)))));
            }
        }

        if ($geocoordinates || !$isCalibratedMap) {
            $territory->setMap($map);
            TerritoryHelper::save($territory);

            $map->addOrUpdateTerritory($territory);
            ModelHelper::getEm()->persist($map);

            ModelHelper::getEm()->flush();
            return true;
        }
        return false;
    }

    /**
     * @param $territory Territory
     * @return string
     */
    private static function getElementIdFromPath($territory) {
        return preg_replace('#^\/\/path\[id="([^"]+)"\]$#', '$1', $territory->getXpath());
    }


    /**
     * @param $territory Territory
     * @param $calibratedMap Map
     * @return string|null
     */
    public static function calculateCoordinates($territory, $calibratedMap)
    {
        return Util::calculatePathCoordinates(
            $calibratedMap->getFileName(),
            $territory->getXpath(),
            $calibratedMap->getProjection(),
            $calibratedMap->getCenter(),
            $calibratedMap->getScale(),
            $calibratedMap->getRotation()
        );
    }

    /**
     * Calculate the approximate area of the polygon were it projected onto
     *     the earth.  Note that this area will be positive if ring is oriented
     *     clockwise, otherwise it will be negative.
     *
     * Reference:
     * Robert. G. Chamberlain and William H. Duquette, "Some Algorithms for
     *     Polygons on a Sphere", JPL Publication 07-03, Jet Propulsion
     *     Laboratory, Pasadena, CA, June 2007 http://trs-new.jpl.nasa.gov/dspace/handle/2014/40409
     *
     * Adapted from https://github.com/mapbox/geojson-area
     *
     * @param $territory Territory
     * @return int|null The approximate signed geodesic area of the polygon in square kilometers.
     */
    private static function calculateArea($territory) {
        $coords = $territory->getPolygon();
        if (!is_null($coords)) {
            $area = 0;
            foreach($coords as $landCoords) {
                $area += self::calculateLandArea($landCoords[0]); // The natural data export encapsulates the coords in an extra array
            }

            return intval(abs($area) / pow(10,6));
        }
        return null;
    }

    /**
     * @param $landCoords
     * @return float
     */
    public static function calculateLandArea($landCoords)
    {
        $area = 0;
        if (count($landCoords) > 2) {
            for ($i = 0; $i < count($landCoords) - 1; $i++) {
                $p1 = $landCoords[$i];
                $p2 = $landCoords[$i + 1];
                $area += self::rad($p2[0] - $p1[0]) * (2 + sin(self::rad($p1[1])) + sin(self::rad($p2[1])));
            }
            $area = $area * self::$equatorialRadius * self::$equatorialRadius / 2;
            return $area;
        }
        return $area;
    }

    private static function rad($measure) {
        return $measure * pi() / 180;
    }

    /**
     * @param $startDate \DateTime
     * @param $endDate \DateTime
     * @param $locatedTerritoriesOnly bool
     * @return int
     */
    public static function countForPeriod($startDate, $endDate, $locatedTerritoriesOnly=false) {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(territory.id)');
        $qb->from(Territory::CLASSNAME,'territory');
        $qb->where('territory.startDate <= :endDate AND territory.endDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($locatedTerritoriesOnly) {
            $qb->andWhere($qb->expr()->andx(
                $qb->expr()->isNotNull('territory.polygon')
            ));
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $noCoordinates boolean
     * @return object
     */
    public function __toSimplifiedObject($noCoordinates) {
        // TODO
        /*
        $simplifiedTerritory = parent::__toSimplifiedObject();
        $simplifiedTerritory->referencedTerritory = $this->getReferencedTerritory()->__toSimplifiedObject();
        unset($simplifiedTerritory->polygon);
        return $simplifiedTerritory;
        */
    }

    /**
     * @param $id integer
     * @return null|Territory
     */
    public static function findOneById($id) {
        return ModelHelper::getEm()->getRepository(Territory::CLASSNAME)
            ->findOneBy(array('id' => $id));
    }

    /**
     * @param $xpath string
     * @return null|Territory
     */
    public static function findOneByXpath($xpath) {
        return ModelHelper::getEm()->getRepository(Territory::CLASSNAME)
            ->findOneBy(array('xpath' => $xpath));
    }

    /**
     * @param $referencedTerritoryId integer
     * @return null|Territory
     */
    public static function findOneByReferencedTerritoryId($referencedTerritoryId) {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('territory')
            ->from(Territory::CLASSNAME,'territory')
            ->join('territory.referencedTerritory', 'referencedTerritory')
            ->where(
                $qb->expr()->eq('referencedTerritory.id', $qb->expr()->literal($referencedTerritoryId))
            );

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return Territory[]
     */
    public static function findWithPeriod() {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('territory');
        $qb->from(Territory::CLASSNAME,'territory');
        $qb->where(
            $qb->expr()->andx(
                $qb->expr()->isNotNull('territory.startDate'),
                $qb->expr()->isNotNull('territory.endDate')
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $territory Territory
     * @return Territory
     */
    public static function save($territory) {
        $territory->setArea(self::calculateArea($territory));
        self::persist($territory);
        self::flush();

        return $territory;
    }

    /**
     * @param bool $userMadeFilter
     * @return int
     */
    public static function count($userMadeFilter = null) {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->select('count(territory.id)');
        $qb->from(Territory::CLASSNAME,'territory');
        if (!is_null($userMadeFilter)) {
            $qb->where(
                $qb->expr()->eq('territory.userMade', ':userMade')
            )
            ->setParameter('userMade', $userMadeFilter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(ReferencedTerritory::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}

TerritoryHelper::$log = Logger::getLogger("main");
