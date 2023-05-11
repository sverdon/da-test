<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$investorID = $_POST['investorID'];
$productID = $_POST['productID'];
$array = array();

$sql = "SELECT CONCAT(Prefix, ' + ', NumDigits) AS Prefix, BCFID
        FROM Inv_BCFormat
        WHERE InvestorID = $investorID AND ProductID = $productID";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $bcfid = $row['BCFID'];
    $prefix = $row['Prefix'];

    $array[] = ["bcfid" => $bcfid, "prefix" => $prefix];
}

echo json_encode($array);