<?php

require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$file = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$reading = fopen($file, 'r');

if(!$file){
    exit('No file found.');
}

if($reading){
    $header = fgetcsv($reading);
    $id = get_string_between($fileName, '_', '-');

    while( ($row = fgetcsv($reading)) != FALSE ){
        // Beneficiary
        if($id == 1461150){
            $province = mysqli_real_escape_string($conn_da, $row[0]);
            $district = mysqli_real_escape_string($conn_da, $row[1]);
            $sector = mysqli_real_escape_string($conn_da, $row[2]);
            $cell = mysqli_real_escape_string($conn_da, $row[3]);
            $village = mysqli_real_escape_string($conn_da, $row[4]);
            $givenname = mysqli_real_escape_string($conn_da, $row[5]);
            $surname = mysqli_real_escape_string($conn_da, $row[6]);
            $hhid = mysqli_real_escape_string($conn_da, $row[7]);
            $villageid = mysqli_real_escape_string($conn_da, $row[13]);

            $sql = "SELECT * FROM frm_DMBenList WHERE HHID = $hhid";
            $result = mysqli_query($conn_da, $sql);

            if(mysqli_num_rows($result) > 0){
                continue; // does this jump out of the loop?
            } else {
                $sql = "INSERT INTO frm_DMBenList (prov, district, sector, cell, village, givenname, surname, HHID, VillageID) VALUES ('$province', '$district', '$sector', '$cell', '$village', '$givenname', '$surname', '$hhid', '$villageid');";
                $result = mysqli_query($conn_da, $sql);
                if(!$result){
                    echo mysqli_error($conn_da);
                }
            }
        } 
        // Barcode
        else if($id == 41495693){
            $barcode = mysqli_real_escape_string($conn_da, $row[0]);
            $tbcid = mysqli_real_escape_string($conn_da, $row[1]);

            $sql = "SELECT Barcode FROM frm_DMBarcodes WHERE TCBID = $tdbid";
            $result = mysqli_query($conn_da, $sql);

            if(mysqli_num_rows($result) > 0){
                continue; // does this jump out of the loop?
            } else {
                $sql = "INSERT INTO frm_DMBarcodes (Barcode, TBCID) VALUES ('$barcode', '$tbcid');";
                $result = mysqli_query($conn_da, $sql);
                if(!$result){
                    echo mysqli_error($conn_da);
                }
            }
        } else {
            exit('File ID not recognized');
        }
    }
    echo "File successfully uploaded!";
} else {
    exit('Error reading CSV file.');
}

function get_string_between($string, $start, $end){
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);   
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}