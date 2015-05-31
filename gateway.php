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
elseif (isset($_POST['getImportedTerritories'])) {
    $object->count = Geotime::getImportedTerritoriesCount();
}
elseif (isset($_POST['getTerritories'])) {
    $object = Geotime::getReferencedTerritories(isset($_POST['startingWith']) ? $_POST['startingWith'] : null);
}
elseif (isset($_POST['getSvg'])) {
    $object = Geotime::getIncompleteMapInfo();
}
elseif (isset($_POST['locateMap'])) {
    $mapId = $_POST['mapId'];
    $mapProjection = $_POST['mapProjection'];
    $mapRotation = $_POST['mapRotation'];
    $mapCenter = $_POST['mapCenter'];
    $mapScale = $_POST['mapScale'];
    Geotime::updateMap($mapId, $mapProjection, $mapRotation, $mapCenter, $mapScale);
}
elseif (isset($_POST['addTerritory'])) {
    $referencedTerritoryId = $_POST['territoryId'];
    $territoryPeriodStart = $_POST['territoryPeriodStart'];
    $territoryPeriodEnd = $_POST['territoryPeriodEnd'];
    $xpath = $_POST['xpath'];
    $coordinates = $_POST['coordinates'];
    $object->coord = Geotime::saveLocatedTerritory($referencedTerritoryId, $coordinates, $xpath, $territoryPeriodStart, $territoryPeriodEnd);
}

echo json_encode($object);

?>