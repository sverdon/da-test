<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$activity = $_POST['activity'];
$regions = $_POST['region'];
$country = $regions[1];
$location = end($regions);
$dateStart = $_POST['date-start'];
$dateEnd = $_POST['date-end'];
$teamID = $_POST['teamid'];

$where = array();

// Country
if(!empty($country)) {
    $countrySQL = "SELECT RegionName FROM g_Locations WHERE GID = $country";
    $result = mysqli_query($conn_da, $countrySQL);
    while($row = mysqli_fetch_assoc($result)) {
        $countryName = $row['RegionName'];
    }
}

// Location
if(!empty($location)) {
    $locationSQL = '(g_Locations.GID = ' . $location . ')';
    $where[] = $locationSQL;
}

// TeamID
if(!empty($teamID)) {
    $teamIDSQL = '(TeamID = ' . $teamID . ')';
    $where[] = $teamIDSQL;
}

// Date Range
if(!empty($dateStart) || !empty($dateEnd)) {
    // If only one date is set, use that date for both
    if(empty($dateStart)) {
        $dateStart = $dateEnd;
    }
    if(empty($dateEnd)) {
        $dateEnd = $dateStart;
    }
    $dateSQL = '(DistrDate BETWEEN "' . $dateStart . '" AND "' . $dateEnd . '")';
    $where[] = $dateSQL;
}

// Create WHERE clause
if(count($where) > 0) {
    $whereSQL = ' WHERE ' . implode(' AND ', $where);
}

// ORDER BY SQL
// NOTE: Because we are using UNION, we need to specify order by the column number
$orderSQL = ' ORDER BY 5 DESC';

// Activity
$distrSQL = "SELECT DistrID as 'Activity ID', 'Distr' AS Activity, TeamID, DistrDate AS 'Activity Date', Q_Plan_Distr AS '# HHs'
            FROM sch_Distrs
            LEFT JOIN g_Locations ON sch_Distrs.GID = g_Locations.GID";

$hhvSQL = "SELECT HHVID as 'Activity ID', 'HHV' AS Activity, TeamID, HHVDate AS 'Activity Date', Q_Plan_HHVs AS '# HHs'
            FROM sch_HHVs
            LEFT JOIN g_Locations ON sch_HHVs.GID = g_Locations.GID";

if(empty($activity)){
    $sql = $distrSQL . $whereSQL . ' UNION ' . $hhvSQL . str_replace('DistrDate', 'HHVDate', $whereSQL) . $orderSQL;
} else{
    if($activity == 'Distr') {
        $selectSQL = $distrSQL;
    }
    if($activity == 'HHV') {
        $selectSQL = $hhvSQL;
        $whereSQL = str_replace('DistrDate', 'HHVDate', $whereSQL);
    }
    $sql = $selectSQL . $whereSQL . $orderSQL;
}

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $activityID = $row['Activity ID'];
    $activity = $row['Activity'];
    $teamID = $row['TeamID'];
    $activityDate = $row['Activity Date'];
    $numHHs = $row['# HHs'];

    $activities[] = ["Activity ID" => $activityID, "Team ID" => $teamID, "Activity" => $activity, "Activity Date" => $activityDate, "# HHs" => $numHHs];
}

// Get additional location data
$sql = "SELECT T2.RegionType,T2.RegionName
        FROM (
            SELECT
                @r AS _id,
                (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                @l := @l + 1 AS lvl
            FROM
                (SELECT @r := $location, @l := 0) vars,
                g_Locations m
            WHERE @r <> 0) T1
        JOIN g_Locations T2
        ON T1._id = T2.GID
        ORDER BY T1.lvl DESC";
$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $regionType = $row['RegionType'];
    $regionName = $row['RegionName'];

    $locations[$regionType] = $regionName;
}

foreach($activities as &$activity){
    $activity = array_merge($activity, $locations);
}

echo json_encode($activities);