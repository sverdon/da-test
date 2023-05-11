<?php

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$tid = $_POST['tid'];
$tdid = $_POST['tdid'];
$stoves = $_POST['stoves'];
$posters = $_POST['posters'];
$investorid = $_POST['bcformat'];
$regions = $_POST['region'];
$dest = end($regions);
$storage = prev($regions);

if(!empty($tdid)){
    $sql = "UPDATE tbl_LogTransOutNin 
            SET  CellID_Dest = $storage, CellID_Distr = $dest, QSent_Tots_Dura = $stoves, QSent_Posters = $posters, InvestorID = $investorid
            WHERE TDID = $tdid";
} else{
    $sql = "INSERT INTO tbl_LogTransOutNin (CellID_Dest, CellID_Distr, QSent_Tots_Dura, QSent_Posters, TID, InvestorID) 
            VALUES ('$storage', '$dest', '$stoves', '$posters', '$tid', '$investorid')";
}

$result = mysqli_query($conn_da, $sql);

if($result){
    echo 'Data submitted successfully!';
} else {
    echo "Error: " . mysqli_error($conn_da);
}