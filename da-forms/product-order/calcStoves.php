<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$bcfid = $_POST['bcfid'];
$stoves = $_POST['stoves'];

$sql = "SELECT Prefix FROM Inv_BCFormat WHERE BCFID = $bcfid";
$result = mysqli_query($conn_da, $sql);
while($row = mysqli_fetch_assoc($result)){
    $prefix = $row['Prefix'];
}

$sql = "SELECT Max(Barcode) AS Max FROM tbl_ProductBarcodes WHERE Barcode LIKE '$prefix%'";
$result = mysqli_query($conn_da, $sql);
while($row = mysqli_fetch_assoc($result)){
    $max = $row['Max'];
}

$start = intval(str_replace($prefix, '', $max)) + 1;
$end = ($start + $stoves) - 1;

echo json_encode(array('start' => $start, 'end' => $end));