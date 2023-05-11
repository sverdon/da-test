<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$provID = $_POST['provinceID'];

$sql = "SELECT DistrictID, DistrictName 
        FROM tbl_LocDistrict 
        WHERE ProvID = $provID";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['DistrictID'];
    $name = $row['DistrictName'];

    $districts[] = ["id" => $id, "name" => $name];
}

echo json_encode($districts);