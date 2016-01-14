<?php
namespace geotime\types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use geotime\models\CoordinateXY;

class CoordinateXYType extends Type
{
    static $CLASSNAME = __CLASS__;

    const MYTYPE = 'coordinate_xy';
    const SQL_DECLARATION = 'COORDINATE_XY';

    /**
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return self::SQL_DECLARATION;
    }

    /**
     * @param $value string
     * @param $platform AbstractPlatform
     *
     * @return CoordinateXY
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return self::toPHP($value);
    }

    public static function toPHP($value) {
        if (is_null($value)) {
            return null;
        }
        preg_match('#^COORDINATE_XY\(([-0-9]+),([-0-9]+)\)$#', $value, $match);
        $x = $match[1];
        $y = $match[2];

        return new CoordinateXY(floatval($x), floatval($y));
    }

    /**
     * @param $value CoordinateXY
     * @param $platform AbstractPlatform
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return is_null($value)
            ? null
            : (self::SQL_DECLARATION.'('.implode(',',array($value->getX(), $value->getY())).')');
    }

    public function getName()
    {
        return self::MYTYPE; // modify to match your constant name
    }
}
