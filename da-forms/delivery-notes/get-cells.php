<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$sectorID = $_POST['sectorID'];

$sql = "SELECT CellID, CellName 
        FROM tbl_LocCell
        WHERE SectorID = $sectorID";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['CellID'];
    $name = $row['CellName'];

    $cells[] = ["id" => $id, "name" => $name];
}

echo json_encode($cells);