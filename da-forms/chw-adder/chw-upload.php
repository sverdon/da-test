<?php
    $file = $_FILES['file']['tmp_name'];
    $realFilename = $_FILES['file']['name'];

    // check if file was uploaded
    if(!file_exists($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        die('No file selected. Select a file and try again.');
    }

    // copy file to uploads folder
    $target = $_SERVER['DOCUMENT_ROOT'] . '/da-forms/uploads/' . $realFilename;
    copy($file, $target);

    // load phpspreadsheet
    require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($file);
    $sheet = $spreadsheet->getSheetByName('template');

    // potential for serious time/memory saving here by using the native rowIterator instead of loading into array first
    $highestRow = $sheet->getHighestDataRow();
    $rows = $sheet->rangeToArray("B2:Y$highestRow", NULL, TRUE, TRUE, TRUE);

    // moved database connection beneath rangeToArray
    // rangeToArray was taking a long time and it timed out the database connection
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

    // Get current version from database
    $sql = "SELECT VersionNumber FROM ProgramTemplates WHERE TemplateID = 4";
    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $currentVersion = intval($row['VersionNumber']);
    }

    // Get version from spreadsheet
    $version = $spreadsheet->getActiveSheet()->getCell('AD1')->getCalculatedValue();
    if($version != $currentVersion){
        die('Uploaded file is not the correct version. Please download the most recent version from the Templates page and try again.');
    }

    $okToUpload = $spreadsheet->getActiveSheet()->getCell('Y1')->getCalculatedValue();
    if($okToUpload != 'OK TO UPLOAD'){
        die('Data entry errors. Please fix errors before uploading.');
    }

    // Iterate through data
    foreach($rows as $row){
        $hasData = $row['Y'];
        if($hasData == 'OK'){
            // mysqli escaped variables
            $givenname = mysqli_real_escape_string($conn_da, $row['B']);
            $surname = mysqli_real_escape_string($conn_da, $row['C']);
            $gender = mysqli_real_escape_string($conn_da, $row['D']);
            $nickname = mysqli_real_escape_string($conn_da, $row['E']);
            $healthCenter = mysqli_real_escape_string($conn_da, $row['F']);
            $nationalID = mysqli_real_escape_string($conn_da, $row['G']);
            $emailPersonal = mysqli_real_escape_string($conn_da, $row['H']);
            $emailWork = mysqli_real_escape_string($conn_da, $row['I']);
            $phone = mysqli_real_escape_string($conn_da, $row['J']);
            $countryID = mysqli_real_escape_string($conn_da, $row['K']);
            $regionID = mysqli_real_escape_string($conn_da, $row['L']);
            $role = mysqli_real_escape_string($conn_da, $row['M']);

            // Insert non-empty rows into database
            $sql = "INSERT INTO adm_TMs (GivenName, Surname, Gender, Nickname, HealthCenter, NationalID, Email_Personal, Email_Work, Phone, CountryID, RegionID, Role) VALUES ('$givenname', '$surname', '$gender', '$nickname', '$healthCenter', '$nationalID', '$emailPersonal', '$emailWork', '$phone', '$countryID', '$regionID', '$role')";
            $result = mysqli_query($conn_da, $sql);
        }
    }

    $filename = 'teamList_CHW_syncTo.csv';
    $tempFilename = 'CHW_temp.csv';

    // Remove already uploaded records from .csv
    $reading = fopen($filename, 'r');
    $temp = fopen($tempFilename, 'w');

    // compile DAIDs into array
    $sql = "SELECT DAID FROM adm_TMs";
    $result = mysqli_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result)) {
        $daids[$row['DAID']] = $row['DAID'];
    }

    // remove rows from .csv if they've already been imported
    while(($row = fgetcsv($reading)) != FALSE){
        $id = $row[0];
    
        if(isset($daids[$id])) {
            continue;
        }
    
        fputcsv($temp, $row);
    }

    fclose($reading);
    fclose($temp);
    rename($tempFilename, $filename);

    // Select unexported rows from adm_TMs
    $sql = "SELECT DAID, CONCAT(GivenName, ' ', Surname) AS FullName, Role, Status, CountryID, RegionID, HealthCenter, g.RegionName AS WorkRegion
            FROM adm_TMs
            LEFT JOIN g_Locations g ON adm_TMs.RegionID = g.GID
            WHERE isExported = 0";

    $result = mysqli_query($conn_da, $sql);
    if(!$result){
        die('An error occured selecting unexported rows from database.');
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // BEGIN - Append rows to .csv
    $writing = fopen($filename, 'a');

    foreach ($rows as $row){

        // get province, district for each RegionID returned above
        $regionID = $row['RegionID'];

        // skip row upload if RegionID is NULL
        if(empty($regionID)){
            continue;
        }

        $sql = "SELECT T2.RegionName, T2.GID
                FROM (
                    SELECT
                        @r AS _id,
                        (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                        @l := @l + 1 AS lvl
                    FROM
                        (SELECT @r := $regionID, @l := 0) vars,
                        g_Locations m
                    WHERE @r <> 0) T1
                JOIN g_Locations T2
                ON T1._id = T2.GID
                WHERE RegionType = 'District' OR RegionType = 'Province'
                ORDER BY T1.lvl DESC";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            die('An error occured getting region names from database for HHID: ' . $row['HHID']);
        }
        $regions = $result->fetch_all(MYSQLI_ASSOC);

        // build new array and splice into current row
        $rArray = ["District" => $regions[1]['RegionName'], "Province" => $regions[0]['RegionName']];
        array_splice($row, 8, 0, $rArray);

        if(!fputcsv($writing, $row)){
            die('An error occured while appending unexported rows to '.$filename);
        }
    }

    echo 'Rows successfully appended to '.$filename . PHP_EOL;
    fclose($writing);

    // Update all rows in table with isExported = 0 to -1
    $sql = "UPDATE adm_TMs 
            SET isExported = -1
            WHERE isExported = 0";

    $result = mysqli_query($conn_da, $sql);