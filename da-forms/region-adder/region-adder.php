<?php
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

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
    $sheet = $spreadsheet->getSheetByName('regions');

    // potential for serious time/memory saving here by using the native rowIterator instead of loading into array first
    $highestRow = $sheet->getHighestDataRow();
    $rows = $sheet->rangeToArray("B2:I$highestRow", NULL, TRUE, TRUE, TRUE);

    $fixErrors = $spreadsheet->getActiveSheet()->getCell('I1')->getCalculatedValue();
    if($fixErrors != 'OK TO UPLOAD'){
        die('Data entry errors. Please fix errors before uploading.');
    }

    // Iterate through data
    foreach($rows as $row){
        $hasData = $row['I'];
        if($hasData == 'OK'){
            $regionName = mysqli_real_escape_string($conn_da, $row['B']);
            $numhhs = mysqli_real_escape_string($conn_da, $row['C']);
            $descrip = mysqli_real_escape_string($conn_da, $row['D']);
            $parentID = mysqli_real_escape_string($conn_da, $row['F']);
            $regionType = mysqli_real_escape_string($conn_da, $row['G']);

            // Insert non-empty rows into database
            $sql = "INSERT INTO g_Locations (RegionName, NumHHs, RegionDescrip, ParentID, RegionType) VALUES ('$regionName', '$numhhs', '$descrip', '$parentID', '$regionType')";
            $result = mysqli_query($conn_da, $sql);
        }
    }

    echo "Regions successfully added to database.";