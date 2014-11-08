<?php

namespace geotime\admin;

use geotime\Database;
use geotime\Import;
use geotime\models\CriteriaGroup;
use geotime\models\SparqlEndpoint;

chdir("..");
require_once("vendor/autoload.php");

Database::connect();

CriteriaGroup::drop();
CriteriaGroup::importFromJson();

SparqlEndpoint::drop();
SparqlEndpoint::importFromJson();

Import::importReferencedTerritories(false);

?><br /><a href="index.html">Back to admin home</a>


