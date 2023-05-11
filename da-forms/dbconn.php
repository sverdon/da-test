<?php

$dbhost = "35.208.174.209";
$dbuser = "uenze6vve1gmk";
$dbpass = "schlafen123";
$db = "dbthezxpnokgxv";

if( isset($_GET['testing']) || isset($_POST['testing']) ){
    $db = "dbs1qpdncyglvh";
}

$conn_da = mysqli_connect($dbhost, $dbuser, $dbpass, $db);

// Check connection
if (!$conn_da) {
    die("Connection failed: " . mysqli_connect_error());
}