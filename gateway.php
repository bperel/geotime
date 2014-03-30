<?php

namespace geotime;

require_once("vendor/autoload.php");
Database::connect();

header('Content-Type: application/json');

if (isset($_GET['getCoverage'])) {
    $object = Geotime::getCoverageInfo();
}
elseif (isset($_GET['getSvg'])) {
    $year = $_GET['year'];
    $object = Geotime::getIncompleteMapInfo($year);
}
else {
    $object = new \stdClass();
}

echo json_encode($object);

?>