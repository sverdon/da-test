<?php

include  $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$central = $_POST['central'];
$country = $_POST['country'];
$gid = $_POST['gid'];

if ($central == 'Yes') {

    // Get all GIDs in the country
    $sql = "SELECT GID
            FROM (SELECT * FROM g_Locations
            ORDER BY ParentID, GID) locations,
            (SELECT @id := $country) parentid
            WHERE find_in_set(ParentID, @id)
            AND LENGTH(@id := concat(@id, ',', GID))";

    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $gids[$row['GID']] = $row['GID'];
    }

    $sql = "SELECT GID, WHID, WarehouseName FROM tbl_LogWarehouses WHERE tbl_LogWarehouses.isCentralWH = -1";
    $result = mysqli_query($conn_da, $sql);
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // filter results by GIDs above
    foreach ($rows as $row) {
        if (array_key_exists($row['GID'], $gids)) {
            $array[] = array("WHID" => $row['WHID'], "WarehouseName" => $row['WarehouseName']);
        }
    }


} else {
    $sql = "SELECT WHID, WarehouseName FROM tbl_LogWarehouses WHERE tbl_LogWarehouses.GID = $gid";
    
    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $array[] = array("WHID" => $row['WHID'], "WarehouseName" => $row['WarehouseName']);
    }
}

echo json_encode($array);