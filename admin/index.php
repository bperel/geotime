<?php
namespace geotime\admin;

use geotime\Database;
use geotime\Geotime;

chdir("..");
require_once("vendor/autoload.php");
Database::connect();
?>

<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Admin area</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width">

    <link rel="stylesheet" href="../css/normalize.min.css">
    <link rel="stylesheet" href="../css/main.css">

    <script src="../js/vendor/modernizr-2.6.2.min.js"></script>
</head>
<body>

<h1>Geotime admin area</h1>

<?php
Geotime::showStatus();
?>

<p style="margin: 10px">
    <a href="initCriteriaGroups.php">Initialize database</a> (i.e. insert criteria groups)
    <br />
    <a href="import.php">Import</a> (download SVGs and other information)
</p>


<script>window.jQuery || document.write('<script src="../js/vendor/jquery-1.10.1.min.js"><\/script>')</script>

<script src="../js/plugins.js"></script>
</body>
</html>
