<?php

    require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet

    $file = $_FILES['file']['tmp_name'];
    $realFilename = $_FILES['file']['name'];

    if(!file_exists($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        die('No file selected. Select a file and try again.');
    }

    // copy file to uploads folder
    $target = $_SERVER['DOCUMENT_ROOT'] . '/da-forms/uploads/' . $realFilename;
    copy($file, $target);

    $username = $_POST['username'];
    $error = 0;

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($file);
    $sheet = $spreadsheet->getSheetByName('BennieList');
    $highestRow = $sheet->getHighestDataRow();

    // potential for serious time/memory saving here by using the native rowIterator instead of loading into array first
    $rows = $sheet->rangeToArray("B2:T$highestRow", NULL, TRUE, TRUE, TRUE);

    // moved database connection beneath rangeToArray
    // rangeToArray was taking a long time and it timed out the database connection
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

    // Get current version from database
    $sql = "SELECT VersionNumber FROM ProgramTemplates WHERE TemplateID = 1";
    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $currentVersion = intval($row['VersionNumber']);
    }

    // Get version from spreadsheet
    $version = $spreadsheet->getActiveSheet()->getCell('W1')->getCalculatedValue();
    
    if($version != $currentVersion){
        die('Uploaded file is not the correct version. Please download the most recent version from the Templates page and try again.');
    }

    // Insert data into bl_Uploads
    $sql = "INSERT INTO bl_Uploads (FileName, Name, ULDate) VALUES ('$realFilename', '$username', curdate())";
    if($result = mysqli_query($conn_da, $sql)){
        $bluid = mysqli_insert_id($conn_da);
    }

    // Iterate through data
    foreach($rows as $row){
        $upload = $row['T'];
        if($upload == 1){
            // mysqli escaped variables
            $hoh_surname = mysqli_real_escape_string($conn_da, $row['B']);
            $hoh_givenname = mysqli_real_escape_string($conn_da, $row['C']);
            $nationalID = mysqli_real_escape_string($conn_da, $row['D']);
            $idType = mysqli_real_escape_string($conn_da, $row['E']);
            $gender = mysqli_real_escape_string($conn_da, $row['F']);
            $phone = mysqli_real_escape_string($conn_da, $row['G']);
            $category = mysqli_real_escape_string($conn_da, $row['H']);
            $source = mysqli_real_escape_string($conn_da, $row['I']);
            $gid = mysqli_real_escape_string($conn_da, $row['J']);
            $trainingName = mysqli_real_escape_string($conn_da, $row['K']);

            $hhid = NULL; // reset HHID to NULL each time to prevent errors from inserting previous IDs
            $startingError = $error;

            // Insert non-empty rows into database
            $sql = "INSERT INTO tbl_BenList (HOH_Surname, HOH_GivenName, Category, NationalID, Gender, Source, VillageID, TrainingName, BLUID, IDType, Phone) VALUES ('$hoh_surname', '$hoh_givenname', '$category', '$nationalID', '$gender', '$source', '$gid', '$trainingName', '$bluid', '$idType', '$phone')";
            $result = mysqli_query($conn_da, $sql) or $error++;
            $lastID = mysqli_insert_id($conn_da);

            // Create HHID
            $hhid = $lastID . 6;

            // Update HHID
            $sql = "UPDATE tbl_BenList SET HHID = $hhid WHERE id = LAST_INSERT_ID()";
            $result = mysqli_query($conn_da, $sql) or $error++;

            if($error > $startingError){
                $nameErrors[] = array('villageID' => $gid, 'name' => $hoh_surname);
            }
        }
    }

    if($error == 0){
        echo "File successfully uploaded! Thank you." . PHP_EOL . 'If you have questions, reference Upload ID: ' . $bluid . PHP_EOL;
    } else {
        echo "There was an error uploading $error name(s) to the database." . PHP_EOL;
        print_r($nameErrors);
    }

    // NEW TEMPLATE
        $filename = 'template-ba-new.csv';
        $tempFilename = 'bennie_temp_new.csv';

    // START - Remove already uploaded records from .csv
        $reading = fopen($filename, 'r');
        $temp = fopen($tempFilename, 'w');

        // compile completed BLUIDS into array
        $sql = "SELECT BLUID FROM bl_Uploads WHERE ULStatus = 'Complete'";
        $result = mysqli_query($conn_da, $sql);

        while($row = mysqli_fetch_assoc($result)) {
            $bluids[$row['BLUID']] = $row['BLUID'];
        }

        // remove rows from .csv if their ULStatus is 'Complete'
        while(($row = fgetcsv($reading)) != FALSE){
            $id = $row[8];
        
            if(isset($bluids[$id])) {
                continue;
            }
        
            fputcsv($temp, $row);
        }

        fclose($reading);
        fclose($temp);
        rename($tempFilename, $filename);
    // END - Remove already uploaded records from .csv

    // START - Add rows to .csv
        $sql = "SELECT tbl_BenList.HHID, tbl_BenList.VillageID, 0, CONCAT(tbl_BenList.HOH_GivenName, ' ', tbl_BenList.HOH_Surname) AS Name, g_Locations.ParentID, tbl_BenList.TrainingName, tbl_BenList.NationalID, tbl_BenList.BLUID, g_Locations.GID
                FROM tbl_BenList 
                LEFT JOIN g_Locations ON tbl_BenList.VillageID = g_Locations.GID
                WHERE isExported = 0";

        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            die('An error occured selecting unexported rows from database.');
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        // Append rows to new .csv
        $writing = fopen($filename, 'a');

        foreach ($rows as $row){

            $gid = $row['GID'];

            if(empty($gid)){
                continue;
            }

            // New Code
            $sql = "SELECT T2.GID, T2.RegionType, T2.RegionName, T2.WorkRegion_CHW
                    FROM (
                        SELECT
                            @r AS _id,
                            (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                            @l := @l + 1 AS lvl
                        FROM
                            (SELECT @r := $gid, @l := 0) vars,
                            g_Locations m
                        WHERE @r <> 0) T1
                    JOIN g_Locations T2
                    ON T1._id = T2.GID
                    ORDER BY T1.lvl DESC";
            $result = mysqli_query($conn_da, $sql);
            $levels = $result->fetch_all(MYSQLI_ASSOC);
            $workRegion = $levels[0]['WorkRegion_CHW']; // Get WorkRegion from Country level
            $wrIndex = array_search($workRegion, array_column($levels, 'RegionType')); // Get index of WorkRegion

            $wrArray = array('PWR1' => '', 'PWR2' => '','PWR3' => '','PWR4' => '','WorkRegion' => '', 'SWR1' => '', 'SWR2' => '','SWR3' => '','SWR4' => '', 'RegionName' => '');
            $p = 1;
            $s = 1;
            foreach($levels as $key => $level){
                $regionType = $level['RegionType'];
                $regionName = $level['RegionName'];
                $gid = $level['GID'];

                if($regionType != 'Country' && $regionName != $workRegion && $key < $wrIndex){
                    $wrArray["PWR$p"] = $regionName;
                    $p++;
                }
                if($regionType != 'Country' && $regionName != $workRegion && $key > $wrIndex){
                    $wrArray["SWR$s"] = $regionName;
                    $s++;
                }
                if($regionType == $workRegion){
                    $wrArray['WorkRegion'] = $regionName;
                    array_splice($row, 2, 0, $gid);
                }
                if($key == (count($levels)-1)){
                    $wrArray['RegionName'] = $regionName;
                }
            }

            foreach($wrArray as &$value){
                if(empty($value)){
                    $value = 'blank';
                }
            }

            unset($row['GID']); // Remove GID from row
            $row = array_merge($row, $wrArray); // Merge arrays

            if(!fputcsv($writing, $row)){
                die('An error occured while appending unexported rows to '.$filename);
            }
        }

        echo 'Rows successfully appended to ' . $filename . PHP_EOL;
        fclose($writing);
    // END - Add rows to .csv

    // Update all rows in table with isExported = 0 to -1
    $sql = "UPDATE tbl_BenList 
            SET isExported = -1
            WHERE isExported = 0";

    $result = mysqli_query($conn_da, $sql);