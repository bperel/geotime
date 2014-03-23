<?php

namespace geotime\admin;

use geotime\Import;
use geotime\Database;
use geotime\NaturalEarthImporter;
use Logger;

chdir("..");
require_once("vendor/autoload.php");
Database::connect();

Logger::configure(stream_resolve_include_path("lib/geotime/logger.xml"));
$log = Logger::getLogger("main");

$naturalEarthImporter = new NaturalEarthImporter();
$naturalEarthImporter->clean();
$nbImportedCountries = $naturalEarthImporter->import('data/external/ne_110m_admin_0_countries.json');

if (is_int($nbImportedCountries)) {
    $log->info($nbImportedCountries.' country positions have been imported from Natural Earth data');
}

$import = new Import();
$import->execute();

?><br /><a href="index.html">Back to admin home</a>