<?php
namespace geotime\models\mariadb;

/**
 * @Entity @Table(name="sparqlEndpoints")
 **/
class SparqlEndpoint
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue *
     * @Column(type="integer")
     */
    var $id;

    /** @Column(type="string") **/
    var $name;


    /** @Column(type="string") **/
    var $rootUrl;


    /** @Column(type="string") **/
    var $endPoint;


    /** @Column(type="string") **/
    var $method;


    /** @Column(type="simple_array") **/
    var $parameters;

    function __construct($name, $rootUrl, $endPoint, $method, $parameters)
    {
        $this->name = $name;
        $this->rootUrl = $rootUrl;
        $this->endPoint = $endPoint;
        $this->method = $method;
        $this->parameters = $parameters;
    }



    // @codeCoverageIgnoreStart
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    /**
     * @param string $rootUrl
     */
    public function setRootUrl($rootUrl)
    {
        $this->rootUrl = $rootUrl;
    }

    /**
     * @return string
     */
    public function getEndPoint()
    {
        return $this->endPoint;
    }

    /**
     * @param string $endPoint
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string[] $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    // @codeCoverageIgnoreEnd

}