<?php

namespace geotime;

require_once("vendor/autoload.php");
Database::connect();

header('Content-Type: application/json');

$object = new \stdClass();

if (isset($_POST['getCoverage'])) {
    $object = Geotime::getCoverageInfo();
}
elseif (isset($_POST['getMaps'])) {
    $object = Geotime::getMapsAndLocalizedTerritoriesCount(true);
}
elseif (isset($_POST['getTerritories'])) {
    $object = Geotime::getTerritories(isset($_POST['startingWith']) ? $_POST['startingWith'] : null);
}
elseif (isset($_POST['getSvg'])) {
    $year = $_POST['year'];
    $ignored = empty($_POST['ignored']) ? array() : explode(',', $_POST['ignored']);
    $object = Geotime::getIncompleteMapInfo($year, $ignored);
}
elseif (isset($_GET['addTerritory'])) {
    $mapId = $_GET['mapId'];
    $territoryName = $_GET['territoryName'];
    $territoryXpath = $_GET['territoryXpath'];
}

echo json_encode($object);

?>