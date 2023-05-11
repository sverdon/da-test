<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$tripid = $_POST['truckID'];
$trips = array();

$sql = "SELECT 
        tbl_LogTransOutNin.TDID, 
        tbl_LogTransOutNin.CellID_Dest,
        tbl_LogTransOutNin.InvestorID,
        Investors.Investor,
        tbl_LogTransOutNin.QSent_Tots_Dura, 
        tbl_LogTransOutNin.QSent_Posters, 
        tbl_LogWarehouses.WarehouseName,
        Inv_BCFormat.Prefix
        FROM tbl_LogTransOutNin
        LEFT JOIN tbl_LogWarehouses ON tbl_LogTransOutNin.WHID_Dest = tbl_LogWarehouses.WHID
        LEFT JOIN Investors ON tbl_LogTransOutNin.InvestorID = Investors.InvestorID
        LEFT JOIN Inv_BCFormat ON tbl_LogTransOutNin.InvestorID = Inv_BCFormat.BCFID
        WHERE tbl_LogTransOutNin.TID = $tripid";

if($result = mysqli_query($conn_da, $sql)){
    while($row = mysqli_fetch_assoc($result)) {
        $tdid = $row['TDID'];
        $destid = $row['CellID_Dest'];
        $investorid = $row['InvestorID'];
        $investor = $row['Investor'];
        $prefix = $row['Prefix'];
        $posters = $row['QSent_Posters'];
        $stoves = $row['QSent_Tots_Dura'];
        $location = $row['WarehouseName'] ?: 'Cell Office';

        $trips[] = ["tdid" => $tdid, "destid" => $destid, "investor" => $investor, "investorid" => $investorid, "prefix" => $prefix, "posters" => $posters, "stoves" => $stoves, "location" => $location];
        // $trips[] = ["TDID" => $tdid, "Dest" => $destid, "Stoves" => $stoves, "Posters" => $posters, "Warehouse" => $location, "BC Prefix" => $prefix, "id-investor" => $investorid];
    }
} else {
    die(mysqli_error($conn_da));
}

foreach($trips as $key => $trip){
    // $dest = $trip['Dest'];
    $dest = $trip['destid'];

    $sql = "SELECT T2.RegionName, T2.GID, T2.RegionType
            FROM (
                SELECT
                    @r AS _id,
                    (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                    @l := @l + 1 AS lvl
                FROM
                    (SELECT @r := $dest, @l := 0) vars,
                    g_Locations m
                WHERE @r <> 0) T1
            JOIN g_Locations T2
            ON T1._id = T2.GID
            WHERE RegionType <> 'Country'
            ORDER BY T1.lvl DESC";
            
    if($result = mysqli_query($conn_da, $sql)){
        while($row = mysqli_fetch_assoc($result)) {
            $name = $row['RegionName'];
            $id = $row['GID'];
            $type = $row['RegionType'];
    
            $trips[$key][$type] = $name;
            $trips[$key][$type.'id'] = $id;
            // $regions[$key][$type] = $name;
            // $regions[$key]['id-'.$type] = $id;
        }
    } else {
        die(mysqli_error($conn_da));
    }
}

// $data = array();
// $data['trips'] = $trips;
// $data['regions'] = $regions;

// echo json_encode($data);

echo json_encode($trips);