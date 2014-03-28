<?php

namespace geotime;

require_once("vendor/autoload.php");
Database::connect();

header('Content-Type: application/json');
if (isset($_GET['getCoverage'])) {
    echo json_encode(Geotime::getCoverageInfo());
}

?>