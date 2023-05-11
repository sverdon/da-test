<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$investorID = $_POST['investors'];
$productID = $_POST['products'];
$bcfid = $_POST['format'];
$stoves = $_POST['numStoves'];
$start = $_POST['start'];
$end = $_POST['end'];
$date = $_POST['date'];

$sql = "INSERT INTO Inv_Orders (BCFID, InvestorID, Quantity, Order_Date)
        VALUES ('$bcfid', '$investorID', '$stoves', '$date')";
$result = mysqli_query($conn_da, $sql);
$orderID = mysqli_insert_id($conn_da);

$sql = "SELECT Prefix FROM Inv_BCFormat WHERE BCFID = $bcfid";
$result = mysqli_query($conn_da, $sql);
while($row = mysqli_fetch_assoc($result)){
    $prefix = $row['Prefix'];
}

// build VALUES() for single SQL statement
for($i=$start;$i<=$end;$i++){
    $barcode = $prefix . $i;
    $values .= "('$barcode', '$productID', '$orderID', '$investorID', '$bcfid'),";
}

$values = rtrim($values, ','); // trim trailing comma

$sql = "INSERT INTO tbl_ProductBarcodes (Barcode, ProductID, OrderID, InvestorID, BCFID) 
        VALUES $values";
$result = mysqli_query($conn_da, $sql);

if($result){
    echo 'Data submitted successfully!';
} else {
    echo "Error: " . mysqli_error($conn_da);
}