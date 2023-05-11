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

// check if selected region has a sub-region
$hasSubRegion = 0;

foreach($regions as $region){
    $sql = "SELECT GID
            FROM g_Locations
            WHERE ParentID = " . $region['id'];

    $result = mysqli_query($conn_da, $sql);

    if(mysqli_num_rows($result) > 0){
        $hasSubRegion++;
    } else {
        continue;
    }
}

if($hasSubRegion > 0){
    echo json_encode($regions);
}