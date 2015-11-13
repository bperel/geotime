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
        if (!array_key_exists('id', $territory)) {
            $referencedTerritoryId = $territory['referencedTerritory']['id'];
            $xpath = $territory['xpath'];
            $territoryPeriodStart = $territory['startDate'].'-01-01';
            $territoryPeriodEnd = $territory['endDate'].'-01-01';
            $success = Geotime::saveLocatedTerritory($mapId, $referencedTerritoryId, $xpath, $territoryPeriodStart, $territoryPeriodEnd);
            if (!$success) {
                $object = new \stdClass();
                $object->error = 'Error while saving territory '.$territory['xpath'];
                break;
            }
        }
    }
}

echo json_encode($object);

?>
