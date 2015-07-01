<?php
date_default_timezone_set('Europe/London');
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/doctrine')));
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(),__DIR__.'/../../lib/geotime/new_models_helpers')));
include_once('bootstrap-doctrine.php');
$entityManagerForTest = DoctrineBootstrap::getTestEntityManager();