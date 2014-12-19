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
    $object = Geotime::getIncompleteMapInfo();
}
elseif (isset($_POST['addTerritory'])) {
    $mapId = $_POST['mapId'];
    $mapProjection = $_POST['mapProjection'];
    $mapPosition = $_POST['mapPosition'];
    Geotime::updateMap($mapId, $mapProjection, $mapPosition);

    $territoryName = $_POST['territoryName'];
    $territoryPeriodStart = $_POST['territoryPeriodStart'];
    $territoryPeriodEnd = $_POST['territoryPeriodEnd'];
    $xpath = $_POST['xpath'];
    $coordinates = $_POST['coordinates'];
    $object->coord = Geotime::addLocatedTerritory($territoryName, $coordinates, $xpath, $territoryPeriodStart, $territoryPeriodEnd);
}

echo json_encode($object);

?>