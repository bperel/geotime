<?php

namespace geotime\models;

use Purekid\Mongodm\Model;


class SparqlEndpoint extends Model {
    static $collection = 'sparqlEndpoints';

    protected static $attrs = array(
        'name' => array('type' => 'string'),
        'rootUrl' => array('type' => 'string'),
        'endPoint' => array('type' => 'string'),
        'method' => array('type' => 'string'),
        'parameters' => array('type' => 'array')
    );

    /**
     * @return string
     */
    public function getName() {
        return $this->__getter('name');
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->__setter('name', $name);
    }

    /**
     * @return string
     */
    public function getRootUrl() {
        return $this->__getter('rootUrl');
    }

    /**
     * @param string $rootUrl
     */
    public function setRootUrl($rootUrl) {
        $this->__setter('rootUrl', $rootUrl);
    }

    /**
     * @return string
     */
    public function getEndpoint() {
        return $this->__getter('endPoint');
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint) {
        $this->__setter('endPoint', $endpoint);
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->__getter('method');
    }

    /**
     * @param string $method
     */
    public function setMethod($method) {
        $this->__setter('method', $method);
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->__getter('parameters');
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters) {
        $this->__setter('parameters', $parameters);
    }
} 