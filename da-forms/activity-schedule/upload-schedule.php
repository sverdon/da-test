<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$uploadedFile = $_FILES['activity-schedule']['tmp_name'];
$realFilename = $_FILES['activity-schedule']['name'];
$username = $_POST['username'];

// copy file to uploads folder
$target = $_SERVER['DOCUMENT_ROOT'] . '/da-forms/uploads/' . $realFilename;
copy($file, $target);

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($uploadedFile);
$sheet = $spreadsheet->getSheetByName('Schedule');

// Iterate through each row
$highestRow = $sheet->getHighestDataRow();
$rows = $sheet->rangeToArray("A2:AA$highestRow");

foreach($rows as $row){
    $changed = $row['AA'];

    // If row has been changed
    if($changed == 'true'){
        $activity = $row['C'];
        $id = $row['A'];
        $oldTeamID = $row['W'];
        $oldDate = $row['X'];
        $oldHHNum = $row['Y'];
        $newTeamID = $row['B'];
        $newDate = $row['D'];
        $newHHNum = $row['E'];

        // Conditional UPDATE statement
        if($activity == 'Distr'){
            $sql = "UPDATE sch_Distrs SET TeamID = $newTeamID, DistrDate = $newDate, Q_Plan_Distr = $newHHNum WHERE DistrID = $id";
        } else {
            $sql = "UPDATE sch_HHVs SET TeamID = $newTeamID, HHVDate = $newDate, Q_Plan_HHVs = $newHHNum WHERE HHVID = $id";
        }
        $result = mysqli_query($conn_da, $sql);

        // Insert change into sch_ChangeLog
        $sql = "INSERT INTO sch_ChangeLog (Activity, schID, oldTeamID, oldDate, oldHHNum, newTeamID, newDate, newHHNum, Who)
                VALUES ('$activity', '$id', '$oldTeamID', '$oldDate', '$oldHHNum', '$newTeamID', '$newDate', '$newHHNum', '$username')";

        $result = mysqli_query($conn_da, $sql);
    }
}

echo 'Activity Schedule updated!';