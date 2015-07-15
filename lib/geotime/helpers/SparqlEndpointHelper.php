<?php
namespace geotime\helpers;

use geotime\models\mariadb\SparqlEndpoint;
use geotime\Util;

class SparqlEndpointHelper implements AbstractEntityHelper
{
    static $cachePath = 'data/sparqlEndpoints.json';

    public static function importFromJson($fileName=null) {
        if (is_null($fileName)) {
            $fileName = self::$cachePath;
        }

        $tableName = self::getTableName();

        Util::importFromJson($fileName, function ($object) use ($tableName) {
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

    /**
     * @param $id
     * @return SparqlEndpoint|object
     */
    public static function findOne($id) {
        return ModelHelper::getEm()->getRepository(SparqlEndpoint::CLASSNAME)
            ->find($id);
    }

    /**
     * @param $name
     * @return SparqlEndpoint|object
     */
    public static function findOneByName($name) {
        return ModelHelper::getEm()->getRepository(SparqlEndpoint::CLASSNAME)
            ->findOneBy(array('name' => $name));
    }

    public static function deleteAll() {
        $qb = ModelHelper::getEm()->createQueryBuilder();
        $qb->delete(SparqlEndpoint::CLASSNAME);

        return $qb->getQuery()->execute();
    }

    // @codeCoverageIgnoreStart
    static final function getTableName()
    {
        return ModelHelper::getEm()->getClassMetadata(SparqlEndpoint::CLASSNAME)->getTableName();
    }
    // @codeCoverageIgnoreEnd
}