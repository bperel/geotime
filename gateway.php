<?php

namespace geotime;

require_once("vendor/autoload.php");
Database::connect();

header('Content-Type: application/json');

$object = new \stdClass();

if (isset($_GET['getCoverage'])) {
    $object = Geotime::getCoverageInfo();
}
elseif (isset($_GET['getMaps'])) {
    $object = Geotime::getMapsAndLocalizedTerritoriesCount();
}
elseif (isset($_GET['getSvg'])) {
    $year = $_GET['year'];
    $ignored = empty($_GET['ignored']) ? array() : explode(',', $_GET['ignored']);
    $object = Geotime::getIncompleteMapInfo($year, $ignored);
}

echo json_encode($object);

?>