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
    $calibrationPoints = $_POST['calibrationPoints'];
    Geotime::updateMap($mapId, $mapProjection, $mapRotation, $mapCenter, $mapScale, $calibrationPoints);
}
elseif (isset($_POST['addTerritories'])) {
    $mapId = $_POST['mapId'];
    foreach($_POST['territories'] as $territory) {
        $referencedTerritoryId = $territory['referencedTerritory']['id'];
        $xpath = $territory['xpath'];
        $territoryPeriodStart = $territory['period']['start'];
        $territoryPeriodEnd = $territory['period']['end'];
        Geotime::saveLocatedTerritory($mapId, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd);
    }
}

echo json_encode($object);

?>