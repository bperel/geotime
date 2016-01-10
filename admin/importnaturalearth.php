<?php

namespace geotime\admin;

use DoctrineBootstrap;
use geotime\helpers\ModelHelper;
use geotime\NaturalEarthImporter;

require_once("../vendor/autoload.php");
require_once("../lib/doctrine/bootstrap-doctrine.php");

chdir("..");

$entityManager = DoctrineBootstrap::getEntityManager();
ModelHelper::setEm($entityManager);


$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json', true);

?><br /><a href="index.html">Back to admin home</a>
