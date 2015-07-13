<?php
date_default_timezone_set('Europe/London');
include_once(__DIR__.'/../../lib/doctrine/bootstrap-doctrine.php');
$entityManagerForTest = DoctrineBootstrap::getTestEntityManager();