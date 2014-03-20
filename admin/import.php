<?php

namespace geotime\admin;

use geotime\Import;
use geotime\Database;
use geotime\models\CriteriaGroup;

chdir("..");
require_once("vendor/autoload.php");
Database::connect();

$import = new Import();

CriteriaGroup::drop();
CriteriaGroup::importFromJson("data/criteriaGroups.json");

$import->execute();