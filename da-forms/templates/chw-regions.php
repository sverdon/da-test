<?php 

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$country = $_POST['country'];

// get CHW Work Region
$sql = "SELECT WorkRegion_CHW
        FROM g_Locations
        WHERE GID = $country";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $workRegion = $row['WorkRegion_CHW'];
}

// Find Parent of Work Region
$sql = "SELECT p.RegionType AS 'PRegion'
        FROM g_Locations g
        LEFT JOIN 
        (SELECT GID, RegionType FROM g_Locations) p ON p.GID = g.ParentID
        WHERE g.RegionType = '$workRegion'
        LIMIT 1";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $pRegion = $row['PRegion'];
}

echo json_encode($pRegion);