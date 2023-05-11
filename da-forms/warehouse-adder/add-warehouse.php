<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$regions = $_POST['region'];
foreach (array_reverse($regions) as $region) {
    if(!empty($region)){
        $location = $region;
        break 1; // stop the loop
    }
}

$name = $_POST['wh-name'];
$type = $_POST['wh-type'];
$describe = $_POST['describe'];
$central = $_POST['central'];

// g_Locations
$sql = "INSERT INTO tbl_LogWarehouses (GID, WarehouseType, WarehouseName, OtherDesc, isCentralWH) VALUES ($location, '$type', '$name', '$describe', '$central')";

$result = mysqli_query($conn_da, $sql);
if(!$result) {
    die(mysqli_error($conn_da));
}

echo 'Warehouse successfully added!';