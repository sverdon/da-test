<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$country = $_POST['country'];

$sql = "SELECT * FROM g_Boundaries WHERE CountryID = $country";
$result = mysqli_query($conn_da, $sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);

foreach($rows[0] as $key => $value){
    if((strpos($key, 'Level') !== false) && !empty($value)){
        $subregions[] = array('level' => $key, 'name' => $value);
    }
}

array_pop($subregions); // remove final value from subregions
echo json_encode($subregions);