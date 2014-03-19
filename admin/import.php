<?php

namespace geotime\admin;

use geotime\Import;
use geotime\models\CriteriaGroup;

chdir("..");

require_once("vendor/autoload.php");

$import = new Import();

CriteriaGroup::drop();
CriteriaGroup::importFromJson("data/criteriaGroups.json");

$import->execute(false);