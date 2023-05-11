<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$project = $_POST['project'];
$investor = $_POST['investor'];
$country = $_POST['country'];
$iname = $_POST['instance-name'];
$inotes = $_POST['instance-notes'];

// c_ProjInstance
$sql = "INSERT INTO c_ProjInstance (ProjectID, InvestorID, CountryID, InstanceName, Descrip) VALUES ('$project', '$investor', '$country', '$iname', '$inotes')";

$result = mysqli_query($conn_da, $sql);
if(!$result) {
    die(mysqli_error($conn_da));
}

echo 'Carbon instance successfully added to database.';