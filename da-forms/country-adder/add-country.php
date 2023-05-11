<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$country = $_POST['country'];
$workregion = $_POST['workregion'];
$distregion = $_POST['distregion'];
$boundaries = $_POST['boundary'];

// Create boundary SQL
array_shift($boundaries);
$boundarySQL = implode("','", $boundaries);

// Create level SQL
for($i=0;$i<count($boundaries);$i++) {
    $j = $i + 1;
    $levels .= "Level$j,";
}
$levels = rtrim($levels, ','); // Remove trailing comma

// g_Locations
$sql = "INSERT INTO g_Locations (RegionName, RegionType, ParentID, WorkRegion_CHW, DistrRegion) VALUES ('$country', 'Country', 0, '$workregion', '$distregion')";

$result = mysqli_query($conn_da, $sql);
if(!$result) {
    die(mysqli_error($conn_da));
}

// g_Boundaries
$sql = "INSERT INTO g_Boundaries (CountryID, $levels) VALUES (LAST_INSERT_ID(), '$boundarySQL')";

$result = mysqli_query($conn_da, $sql);
if(!$result) {
    die(mysqli_error($conn_da));
}

echo 'Country successfully added to the database.';