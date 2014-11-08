<?php

namespace geotime\admin;

use geotime\Database;
use geotime\Geotime;
use geotime\Import;
use geotime\NaturalEarthImporter;

chdir("..");
require_once("vendor/autoload.php");
Database::connect();

$clean = isset($_GET['clean']);

if ($clean) {
    Geotime::clean();
}

$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json');

Import::initCriteriaGroups();
Import::importMaps(!$clean);

?><br /><a href="index.html">Back to admin home</a>