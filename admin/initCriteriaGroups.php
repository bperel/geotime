<?php

namespace geotime\admin;

use geotime\Database;
use geotime\models\CriteriaGroup;
use Logger;

chdir("..");
require_once("vendor/autoload.php");

Logger::configure(stream_resolve_include_path("lib/geotime/logger.xml"));
$log = Logger::getLogger("main");

Database::connect();

CriteriaGroup::drop();
$nbImportedObjects = CriteriaGroup::importFromJson("data/criteriaGroups.json");

if (is_int($nbImportedObjects)) {
    $log->info("Successfully imported $nbImportedObjects criteria group(s)");
}
else {
    $log->info('An error occured while importing criteria groups');
}

?><br /><a href="index.php">Back to admin home</a>


