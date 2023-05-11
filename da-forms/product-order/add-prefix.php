<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$prefix = $_POST['prefix'];
$digits = $_POST['digits'];
$product = $_POST['product'];
$investor = $_POST['investor'];

$sql = "INSERT INTO Inv_BCFormat (Prefix, NumDigits, ProductID, InvestorID) VALUES ('$prefix', '$digits', '$product', '$investor')";
$result = mysqli_query($conn_da, $sql);

if($result){
    echo 'Data submitted successfully!';
} else {
    echo "Error: " . mysqli_error($conn_da);
}