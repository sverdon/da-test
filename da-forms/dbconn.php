<?php

$dbhost = "da.chmgprnkcpni.eu-west-2.rds.amazonaws.com";
$dbuser = "dr9YfQp3";
$dbpass = "mPQkGL0vdU0IW7HjQog6";
$db = "dbthezxpnokgxv";

if( isset($_GET['testing']) || isset($_POST['testing']) ){
    $db = "dbthezxpnokgxv";
}

$conn_da = mysqli_connect($dbhost, $dbuser, $dbpass, $db);

// Check connection
if (!$conn_da) {
    die("Connection failed: " . mysqli_connect_error());
}