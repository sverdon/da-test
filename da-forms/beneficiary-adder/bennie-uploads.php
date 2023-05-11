<?php 

include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';

$bluid = $_POST['bluid'];
$status = $_POST['status'];
$filename = $_POST['filename'];

// Update status in database
$sql = "UPDATE bl_Uploads SET ULStatus = '$status' WHERE BLUID = '$bluid'";
$result = mysqli_query($conn_da, $sql);

if($result){
    echo "BLUID $bluid has been updated to $status";
} else {
    echo mysqli_error($conn_da);
    die();
}

// If status is complete, delete file from 'Uploads' folder
if($status == 'Complete'){
    if(file_exists('../uploads/' . $filename)){
        unlink('../uploads/' . $filename);
    }
}