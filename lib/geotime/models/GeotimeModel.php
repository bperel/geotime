<?php
namespace geotime\models;

use Logger;
use Purekid\Mongodm\Model;

Logger::configure("lib/geotime/logger.xml");


class GeotimeModel extends Model {

    public function getIdAsString() {
        $subKey='$id';
        $id = $this->getId();
        return $id->$subKey;
    }

    /**
     * @return object
     */
    public function __toSimplifiedObject() {
        $arr = $this->toArray(array('_type','_id'), true, 5);
        $arr['id'] = $this->getIdAsString();
        return json_decode(json_encode($arr));
    }
}