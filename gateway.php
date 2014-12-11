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
elseif (isset($_POST['addTerritory'])) {
    $mapId = $_POST['mapId'];
    $mapProjection = $_POST['mapProjection'];
    $mapPosition = $_POST['mapPosition'];
    Geotime::updateMap($mapId, $mapProjection, $mapPosition);

    $territoryName = $_POST['territoryName'];
    $xpath = $_POST['xpath'];
    $coordinates = $_POST['coordinates'];
    $object->coord = Geotime::addLocatedTerritory($territoryName, $coordinates, $xpath);
}

echo json_encode($object);

?>