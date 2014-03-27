<?php

namespace geotime\admin;

use geotime\Database;
use geotime\Geotime;
use geotime\Import;
use geotime\NaturalEarthImporter;

chdir("..");
require_once("vendor/autoload.php");
Database::connect();

if (isset($_GET['clean'])) {
    Geotime::clean();
}

$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json');

$import = new Import();
$import->execute();

?><br /><a href="index.php">Back to admin home</a>