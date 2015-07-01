<?php
require_once "../../vendor/autoload.php";
require_once "bootstrap-doctrine.php";

$entityManager = DoctrineBootstrap::getEntityManager();
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
