<?php 

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$parentID = $_POST['parentID'];

$sql = "SELECT GID, RegionName, RegionType
        FROM g_Locations
        WHERE ParentID = $parentID";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['GID'];
    $name = $row['RegionName'];
    $type = $row['RegionType'];

    $regions[] = ["id" => $id, "name" => $name, "type" => $type];
}

echo json_encode($regions);