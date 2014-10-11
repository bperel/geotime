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
    $ignored = empty($_GET['ignored']) ? array() : explode(',', $_GET['ignored']);
    $object = Geotime::getIncompleteMapInfo($year, $ignored);
}

if (!isset($object)) {
    $object = new \stdClass();
}

echo json_encode($object);

?>