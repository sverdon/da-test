<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$uploadedFile = $_FILES['barcodes']['tmp_name'];
$realFilename = $_FILES['barcodes']['name'];
$filename = 'Barcode_ToWorldApp_Template.csv';

// copy file to uploads folder
$target = $_SERVER['DOCUMENT_ROOT'] . '/da-forms/uploads/' . $realFilename;
copy($file, $target);

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($uploadedFile);
$sheet = $spreadsheet->getSheet(0);

// Check if trip ID has already been inserted into table
$tripID = $sheet->getCell("M2")->getCalculatedValue();
$sql = "SELECT ID FROM tbl_LogTransBCs WHERE TID = $tripID";
$result = mysqli_query($conn_da, $sql);

if (mysqli_num_rows($result) > 0){
    die('ERROR: File was already uploaded for this Truck Trip ID');
}

// Check if there are any errors in the spreadsheet
$errorCheck = $sheet->getCell("M3")->getCalculatedValue();

if($errorCheck != 1){
    die('ERROR: Data entry errors exist. Please correct them before uploading');
}

// If checks are successful then insert barcodes into database and insert into .csv template
$highestRow = $sheet->getHighestDataRow();
$barcodes = $sheet->rangeToArray("A2:A$highestRow");

// Get sector count and sector ID
$sql = "SELECT COUNT(DISTINCT g_Locations.ParentID) AS sectorCount, g_Locations.ParentID AS SectorID
        FROM tbl_LogTransOutNin 
        LEFT JOIN g_Locations ON g_Locations.GID = tbl_LogTransOutNin.CellID_Dest
        WHERE tbl_LogTransOutNin.TID = $tripID";

$result = mysqli_query($conn_da, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $sectorCount = $row['sectorCount'];
    $sectorID = $row['SectorID'];
}

// Temporarily removing process for removing already uploaded barcodes
// Will need to revisit this when we add it back to make it more efficient
// see /beneficiary-adder/bennie-adder.php for example

// Remove already uploaded barcodes from .csv
// $reading = fopen($filename, 'r');
// $temp = fopen('barcode_temp.csv', 'w');

// while(($row = fgetcsv($reading)) != FALSE){
//     $tbcid = $row[3];

//     $sql = "SELECT Barcode FROM frm_DMBarcodes WHERE TBCID = $tbcid";
//     $result = mysqli_query($conn_da, $sql);

//     if(mysqli_num_rows($result) > 0){
//         continue;
//     }

//     fputcsv($temp, $row);
// }

// fclose($reading);
// fclose($temp);
// rename('barcode_temp.csv', $filename);

// Open .csv for appending
$writing = fopen($filename, 'a');

foreach($barcodes as $cell){
    foreach($cell as $barcode){
        if(!empty($barcode) && $barcode != 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'){
            $sql = "INSERT INTO tbl_LogTransBCs (TID, Barcode) VALUES ('$tripID', '$barcode')";
            $result = mysqli_query($conn_da, $sql);
            $lastId = mysqli_insert_id($conn_da);

            $sql = "SELECT ID FROM tbl_ProductBarcodes WHERE Barcode = '$barcode'";
            $result = mysqli_query($conn_da, $sql);
            while($row = mysqli_fetch_assoc($result)) {
                $bid = $row['ID'];
            }

            if($sectorCount == 1){
                fputcsv($writing, array($barcode, 0, $sectorID, $lastId, $bid));
            } else if ($sectorCount > 1){
                fputcsv($writing, array($barcode, 1, 0, $lastId, $bid));
            }
        }
    }
}

fclose($writing);
echo 'Barcodes successfully uploaded!';

// UNLOCK SPREADSHEET - Working with example sheet but not Kyle's sheets
// require_once('xlsxDecrypt/PHPDecryptXLSXWithPassword.php'); // load xlsxdecrpyt

// move_uploaded_file($uploadedFile, "locked/$uploadedFileName");

// $newFile = "locked/$uploadedFileName";

// unlock spreadsheet
// $password = 'S(*&WE#WE465';
// $decryptedFile = 'unlocked/file' . time() . '.xlsx';

// decrypt($newFile, $password, $decryptedFile);

// unlink($newFile);