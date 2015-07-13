<?php

namespace geotime\admin;

use DoctrineBootstrap;
use geotime\Geotime;
use geotime\helpers\ModelHelper;
use geotime\Import;
use geotime\NaturalEarthImporter;

require_once("../vendor/autoload.php");

ModelHelper::setEm(DoctrineBootstrap::getEntityManager());

$clean = isset($_GET['clean']);

if ($clean) {
    Geotime::clean();
}

$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json');

Import::initCriteriaGroups();
Import::importMaps(!$clean);

?><br /><a href="index.html">Back to admin home</a>