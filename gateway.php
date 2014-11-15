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
    $object = Geotime::getMapsAndLocalizedTerritoriesCount(true);
}
elseif (isset($_GET['getTerritories'])) {
    $object = Geotime::getTerritories(isset($_GET['startingWith']) ? $_GET['startingWith'] : null);
}
elseif (isset($_GET['getSvg'])) {
    $year = $_GET['year'];
    $ignored = empty($_GET['ignored']) ? array() : explode(',', $_GET['ignored']);
    $object = Geotime::getIncompleteMapInfo($year, $ignored);
}
elseif (isset($_GET['addTerritory'])) {
    $mapId = $_GET['mapId'];
    $territoryName = $_GET['territoryName'];
    $territoryXpath = $_GET['territoryXpath'];
}

echo json_encode($object);

?>