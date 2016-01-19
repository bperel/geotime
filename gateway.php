<?php

namespace geotime;

require_once("vendor/autoload.php");
require_once("lib/doctrine/bootstrap-doctrine.php");

use DoctrineBootstrap;
use geotime\helpers\ModelHelper;

$entityManager = DoctrineBootstrap::getEntityManager();
ModelHelper::setEm($entityManager);

header('Content-Type: application/json');
$object = new \stdClass();

if (isset($_POST['getCoverage'])) {
    $object = Geotime::getCoverageInfo();
}
elseif (isset($_POST['getMapsStats'])) {
    $object = Geotime::getMapsAndLocalizedTerritoriesCount(true);
}
elseif (isset($_POST['getMaps'])) {
    $object = Geotime::getMaps();
}
elseif (isset($_POST['getImportedTerritories'])) {
    $object->count = Geotime::getImportedTerritoriesCount();
}
elseif (isset($_POST['getTerritories'])) {
    $object = Geotime::getReferencedTerritories(isset($_POST['like']) ? $_POST['like'] : null);
}
elseif (isset($_POST['getSvg'])) {
    $object = Geotime::getIncompleteMapInfo($_POST['fileName']);
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
        $xpath = $territory['xpath'];
        if (!empty($xpath)) {
            $referencedTerritoryId = $territory['referencedTerritory']['id'];
            $territoryPeriodStart = $territory['startDate'];
            $territoryPeriodEnd = $territory['endDate'];
            $territoryId = array_key_exists('id', $territory) ? $territory['id'] : null;
            $success = Geotime::saveLocatedTerritory($mapId, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd, $territoryId);
            if (!$success) {
                $object = new \stdClass();
                $object->error = 'Error while saving territory '.$territory['xpath'];
                break;
            }
        }
    }
}
elseif (isset($_POST['getTerritoriesForYear'])) {
    $object = Geotime::getTerritoriesForPeriod($_POST['year']);
}

echo json_encode($object);

?>
