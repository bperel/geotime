<?php
namespace geotime\models\mariadb\types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use geotime\models\mariadb\CoordinateLatLng;

class CoordinateLatLngType extends Type
{
    const MYTYPE = 'coordinateLatLng'; // modify to match your type name

    const SQL_DECLARATION = 'COORDINATE_LATLNG';
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
     * @return CoordinateLatLng
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return self::toPHP($value);
    }

    public static function toPHP($value) {
        preg_match('#^COORDINATE_LATLNG\(([-0-9]+),([-0-9]+)\)#', $value, $match);
        $lat = $match[1];
        $lng = $match[2];

        return new CoordinateLatLng($lat, $lng);
    }

    /**
     * @param $value CoordinateLatLng
     * @param $platform AbstractPlatform
     * @return string|void
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return is_null($value)
            ? null
            : (self::SQL_DECLARATION.'('.implode(',',array($value->getLat(), $value->getLng())).')');
    }

    public function getName()
    {
        return self::MYTYPE; // modify to match your constant name
    }
}
