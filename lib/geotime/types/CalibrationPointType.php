<?php
namespace geotime\types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use geotime\models\CalibrationPoint;

class CalibrationPointType extends Type
{
    static $CLASSNAME = __CLASS__;

    const MYTYPE = 'calibrationPoint';

    const SQL_DECLARATION = 'CALIBRATION_POINT';
    /**
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDoctrineTypeMapping('text');
    }

    /**
     * @param $value string
     * @param $platform AbstractPlatform
     *
     * @return CalibrationPoint
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return null;
        }

        preg_match('#^CALIBRATION_POINT\(([^ ]+) ([^\)]+)\)$#', $value, $match);
        $bgPoint = $match[1];
        $fgPoint = $match[2];

        return new CalibrationPoint(CoordinateLatLngType::toPhp($bgPoint), CoordinateXYType::toPhp($fgPoint));
    }

    /**
     * @param $value CalibrationPoint
     * @param $platform AbstractPlatform
     * @return string|void
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return is_null($value) || $value instanceof CalibrationPoint
            ? null
            : self::SQL_DECLARATION.'('.(implode(' ', array($value->getBgPoint(), $value->getFgPoint()))).')';
    }

    public function getName()
    {
        return self::MYTYPE; // modify to match your constant name
    }

    public function canRequireSQLConversion()
    {
        return true;
    }
}
