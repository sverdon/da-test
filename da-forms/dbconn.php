<?php

$dbhost = "test";
$dbuser = "dssddhe1";
$dbpass = "hashHASH%%";
$db = "formsddsswd";

if( isset($_GET['testing']) || isset($_POST['testing']) ){
    $db = "formsddsswd";
}

$conn_da = mysqli_connect($dbhost, $dbuser, $dbpass, $db);

// Check connection
if (!$conn_da) {
    die("Connection failed: " . mysqli_connect_error());
}