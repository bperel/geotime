<?php
namespace geotime\helpers;

use geotime\models\mariadb\SparqlEndpoint;
use geotime\new_models\AbstractEntityHelper;

class SparqlEndpointHelper implements AbstractEntityHelper
{
    static $cachePath = 'data/sparqlEndpoints.json';

    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(SparqlEndpoint::CLASSNAME)->getTableName();
    }

    public static function importFromJson($fileName=null) {
        if (is_null($fileName)) {
            $fileName = self::$cachePath;
        }
        ModelHelper::importFromJson(self::getTableName(), $fileName);
    }
}