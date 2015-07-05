<?php
namespace geotime\helpers;

use geotime\models\mariadb\SparqlEndpoint;
use geotime\new_models\AbstractEntityHelper;

class SparqlEndpointHelper implements AbstractEntityHelper
{
    static $cachePath = 'data/sparqlEndpoints.json';

    public static function importFromJson($fileName=null) {
        if (is_null($fileName)) {
            $fileName = self::$cachePath;
        }

        $tableName = self::getTableName();

        ModelHelper::importFromJson($fileName, function($object) use ($tableName) {
            $sparqlEndPoint = new SparqlEndpoint();
            $sparqlEndPoint->setName($object->name);
            $sparqlEndPoint->setRootUrl($object->rootUrl);
            $sparqlEndPoint->setEndPoint($object->endPoint);
            $sparqlEndPoint->setMethod($object->method);
            $sparqlEndPoint->setParameters($object->parameters);

            ModelHelper::getEm()->persist($sparqlEndPoint);
        });

        ModelHelper::getEm()->flush();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(SparqlEndpoint::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}