<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$whid = $_POST['outbound-wh'];
$date = $_POST['date'];
$numtrucks = $_POST['numtrucks'];

// Repeat below insert statement for each truck
for($i=0; $i<$numtrucks; $i++) {
    $values .= "('$whid', '$date'),";
}

$values = rtrim($values, ','); // trim trailing comma

$sql = "INSERT INTO tbl_LogTransOut (WHID_Source, Trans_Date) VALUES $values";
$result = mysqli_query($conn_da, $sql);

if(!$result) {
    die(mysqli_error($conn_da));
}

echo 'Information successfully added to database.';