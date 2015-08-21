<?php
namespace geotime\helpers;

use geotime\models\mariadb\SparqlEndpoint;
use geotime\Util;

class SparqlEndpointHelper extends AbstractEntityHelper
{
    static $cachePath = 'data/sparqlEndpoints.json';

    public static function importFromJson($fileName=null) {
        if (is_null($fileName)) {
            $fileName = self::$cachePath;
        }

        Util::importFromJson($fileName, function ($object) {
            $sparqlEndPoint = new SparqlEndpoint();
            $sparqlEndPoint->setName($object->name);
            $sparqlEndPoint->setRootUrl($object->rootUrl);
            $sparqlEndPoint->setEndPoint($object->endPoint);
            $sparqlEndPoint->setMethod($object->method);
            $sparqlEndPoint->setParameters($object->parameters);

            self::persist($sparqlEndPoint);
        });

        self::flush();
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
