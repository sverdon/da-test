<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$uploadedFile = $_FILES['numhhs']['tmp_name'];
$realFilename = $_FILES['numhhs']['name'];

// copy file to uploads folder
$target = $_SERVER['DOCUMENT_ROOT'] . '/da-forms/uploads/' . $realFilename;
copy($file, $target);

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($uploadedFile);
$sheet = $spreadsheet->getSheetByName('Schedule');

// Iterate through each row
$highestRow = $sheet->getHighestDataRow();
$rows = $sheet->rangeToArray("A2:V$highestRow");

foreach($rows as $row){
    $changed = $row['21'];

    // If row has been changed
    if($changed == 1){
        $id = $row['0'];
        $numhhs = $row['1'];

        $sql = "UPDATE g_Locations SET NumHHs = $numhhs WHERE GID = $id";
        $result = mysqli_query($conn_da, $sql);
    }
}

if($result){
    echo 'Records successfully updated!';
} else{
    echo 'ERROR updating records. There was either no updated information or an error while updating the database.' . PHP_EOL;
    echo mysqli_error($conn_da);
}