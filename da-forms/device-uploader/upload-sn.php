<?php

    require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

    $file = $_FILES['file']['tmp_name'];
    $realFilename = $_FILES['file']['name'];
    $filename = 'sn.csv'; // name of .csv file

    if(!file_exists($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        die('No file selected. Select a file and try again.');
    }

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($file);
    $sheet = $spreadsheet->getSheetByName('sn');
    $highestRow = $sheet->getHighestDataRow();
    $rows = $sheet->rangeToArray("A2:G$highestRow", NULL, TRUE, TRUE, TRUE);

    // retrieve SNs and Logins from database to check for duplicates
    $sql = "SELECT SerialNumber, RIGHT(SerialNumber, 6) as Login FROM adm_DeviceSNs WHERE SerialNumber IS NOT NULL";
    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $sns[$row['SerialNumber']] = $row['SerialNumber'];
        $logins[$row['Login']] = $row['Login'];
    }

    // INSERT EXCEL DATA INTO DATABASE
    foreach($rows as $row){
        $upload = $row['M'];

        if($upload == 'OK'){
            $serialNumber = mysqli_real_escape_string($conn_da, $row['B']);
            $imei = mysqli_real_escape_string($conn_da, $row['C']);
            $countryID = mysqli_real_escape_string($conn_da, $row['D']);
            $login = mysqli_real_escape_string($conn_da, $row['E']);
            $model = mysqli_real_escape_string($conn_da, $row['F']);

            // skip if SN already exists in database
            if(isset($sns[$serialNumber])) {
                return;
            }

            // append 'A' to Login if already exists in database
            while(isset($logins[$login])) {
               $login = $login . 'A'; 
            }

            $sql = "INSERT INTO adm_DeviceSNs (SerialNumber, IMEI, CountryID, Login, DeviceModel) VALUES ('$serialNumber', '$imei', '$countryID', '$login', '$model')";
            $result = mysqli_query($conn_da, $sql);
        }
    }


    // ADD DATA TO .CSV FILE
    $sql = "SELECT adm_DeviceSNs.SerialNumber, adm_DeviceSNs.Login, 0 AS Status, adm_DeviceSNs.CountryID, adm_DeviceSNs.DeviceID, RegionName AS Country, adm_DeviceSNs.DeviceModel
            FROM adm_DeviceSNs 
            LEFT JOIN g_Locations ON adm_DeviceSNs.CountryID = g_Locations.GID 
            WHERE isExported = 0";

    $result = mysqli_query($conn_da, $sql);
    if(!$result){
        die('An error occured selecting unexported rows from database.');
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    // Append rows to .csv
    $writing = fopen($filename, 'a');

    foreach ($rows as $row){
        if(!fputcsv($writing, $row)){
            die('An error occured while appending unexported rows to '.$filename);
        }
    }

    echo 'Rows successfully appended to '.$filename . PHP_EOL;
    fclose($writing);

    // UPDATE DATABASE
    $sql = "UPDATE adm_DeviceSNs 
            SET isExported = -1
            WHERE isExported = 0";

    $result = mysqli_query($conn_da, $sql);