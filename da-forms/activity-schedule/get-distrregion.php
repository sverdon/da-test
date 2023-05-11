<?php 

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$country = $_POST['country'];

$sql = "SELECT DistrRegion
        FROM g_Locations
        WHERE GID = $country";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $distrregion = $row['DistrRegion'];
}

echo json_encode($distrregion);