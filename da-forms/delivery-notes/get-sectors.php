<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$districtID = $_POST['districtID'];

$sql = "SELECT SectorID, SectorName 
        FROM tbl_LocSector
        WHERE DistrictID = $districtID";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['SectorID'];
    $name = $row['SectorName'];

    $sectors[] = ["id" => $id, "name" => $name];
}

echo json_encode($sectors);