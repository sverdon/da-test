<?php 

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$country = $_POST['country'];

$sql = "SELECT TeamID FROM adm_TeamIDs WHERE CountryID = $country";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $teamid = $row['TeamID'];

    $teamids[] = $teamid;
}

echo json_encode($teamids);