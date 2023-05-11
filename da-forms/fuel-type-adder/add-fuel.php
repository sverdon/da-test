<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$country = $_POST['country'];
$english = $_POST['fuel-english'];
$local = $_POST['fuel-local'];
$biomass = $_POST['biomass'];

// c_FT
$sql = "INSERT INTO c_FT (CountryID, Fuel_Eng, Fuel_Local, isBiomass) VALUES ('$country', '$english', '$local', '$biomass')";

$result = mysqli_query($conn_da, $sql);
$ftid = mysqli_insert_id($conn_da);
if(!$result) {
    die(mysqli_error($conn_da));
}

$csv = fopen('fueltype.csv', 'a');
fputcsv($csv, array($ftid, $english, $local, $country, $biomass));
fclose($csv);

echo 'New fuel type successfully added to database.';