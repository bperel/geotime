<?php

namespace geotime\admin;

use DoctrineBootstrap;
use geotime\helpers\CriteriaGroupHelper;
use geotime\helpers\ModelHelper;
use geotime\helpers\SparqlEndpointHelper;
use geotime\Import;

require_once("../vendor/autoload.php");

ModelHelper::setEm(DoctrineBootstrap::getEntityManager());

CriteriaGroupHelper::deleteAll();
CriteriaGroupHelper::importFromJson();

SparqlEndpointHelper::deleteAll();
SparqlEndpointHelper::importFromJson();

Import::instance()->importReferencedTerritories('formerTerritories', false);

?><br /><a href="index.html">Back to admin home</a>


