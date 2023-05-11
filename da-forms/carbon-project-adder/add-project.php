<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$projectname = $_POST['project-name'];
$registry = $_POST['registry'];
$registryid = $_POST['registryid'];
$country = $_POST['country'];
$investor = $_POST['investor'];
$groupproject = $_POST['group-project'];
$projectnotes = $_POST['project-notes'];
$iname = $_POST['instance-name'];
$icountry = $_POST['instance-country'];
$iinvestor = $_POST['instance-investor'];
$inotes = $_POST['instance-notes'];

// c_Proj
$sql = "INSERT INTO c_Proj (ProjectName, Registry, RegistryID, InvestorID, CountryID, isGrouped) VALUES ('$projectname', '$registry', '$registryid', '$investor', '$country', '$groupproject')";

$result = mysqli_query($conn_da, $sql);
if(!$result) {
    die(mysqli_error($conn_da));
}

if($groupproject == 'Yes'){
    // c_ProjInstance
    $sql = "INSERT INTO c_ProjInstance (ProjectID, InstanceName, InvestorID, CountryID, Descrip) VALUES (LAST_INSERT_ID(), '$iname', '$iinvestor', '$icountry', '$inotes')";

    $result = mysqli_query($conn_da, $sql);
    if(!$result) {
        die(mysqli_error($conn_da));
    }
}

echo 'Carbon project successfully added to database.';