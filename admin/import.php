<?php

namespace geotime\admin;

use DoctrineBootstrap;
use geotime\Geotime;
use geotime\helpers\ModelHelper;
use geotime\Import;
use geotime\NaturalEarthImporter;

require_once("../vendor/autoload.php");
require_once("../lib/doctrine/bootstrap-doctrine.php");

chdir("..");

$entityManager = DoctrineBootstrap::getEntityManager();
ModelHelper::setEm($entityManager);

$clean = isset($_GET['clean']);

if ($clean) {
    Geotime::clean();
}

$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json');

?><br /><a href="index.html">Back to admin home</a>