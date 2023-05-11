<?php
    //require '../dbconn.php';
    require_once 'util.php';

    $dbhost = "35.208.174.209";
    $dbuser = "u4nb3xb15qjtk";
    $dbpass = 'wgb)@ir$cC23';
    $db = "dbthezxpnokgxv";
    //$db = "dbs1qpdncyglvh";

    $conn_da = mysqli_connect($dbhost, $dbuser, $dbpass, $db);

    // Check connection
    if (!$conn_da) {
        die("Connection failed: " . mysqli_connect_error());
    }
    else{
        echo "Successful Connection!";
    }
   
    // Grab all csv files from the desired folder
    //chdir("C:/Users/JingKappes/github/DelAgua-Test-PHP");
    //chdir("C:/Users/JingKappes/github/DelAgua-Test-PHP/failedCSVs");
    $files = glob('*.csv' );
    //$files = glob('failedCSVs/*.csv' );

    //Check if there are any files, if no files exit
    if(empty($files)){
        exit();
    }

    // Sort files by modified time, latest to earliest
    // Use SORT_ASC in place of SORT_DESC for earliest to latest
    array_multisort(
    array_map( 'filemtime', $files ),
    SORT_NUMERIC,
    //SORT_ASC,
    SORT_DESC,
    $files
    );

   $oldestFile = $files[0]; // the latest modified file should be the first. 

    echo "File chosen is " . basename($oldestFile) . PHP_EOL;

    //opening file 
    $file = fopen($oldestFile, 'r');

    //get form ID, Ex. if csv is called "Export41613938--2022-06-08.csv", want to get 41613938 as the formID
    $formID = substr(basename($oldestFile), 6, 8);
    //$formID = 41629569;

    echo "FormID is $formID" .PHP_EOL;

    if($formID != '41622901')
    {
        echo "FormID is not 41622901, exiting";
        exit;
    }
/*  
    //these formID need to be ignored
    if( $formID == '41613938' || $formID == '41613922')
    {
        unlink($oldestFile);
        //echo "Form $formID no longer applicable, deleting File!" .PHP_EOL;
        exit;
    }

    if($formID == '41627897') //datacalc form, want to have other script process it
    {
        if(copy(realpath($oldestFile), "DataCalc/$oldestFile"))
        {
            echo "File $oldestFile is Data Calc form, moved to DataCalc folder" .PHP_EOL;
        }
        exit;
    } 
*/

    //if($formID == '41613987') //ID of previous QR form
    if($formID == '41629588' || $formID == '41613987') //formID of forms that have QR codes
    {
        define("QRForm","1");
        define("RegularForm", "0");
        define("signedDDN", "0");
    }
    else if($formID == '41629569')
    {
        define("signedDDN", "2");
        define("RegularForm", "0");
        define("QRForm","0");
    }
    else{
        define("RegularForm", "1");
        define("QRForm","0");
        define("signedDDN", "0");
    }

    $dateValueIndex = -1; //variable used to keep track of which index a field with a date value (m/d/y) is in
    $QRcodeIndex = -1;
    $sysIDindex = -1;
    $sysID = 0;
    $recordID = 0;
    $numRecords = 0; //count number of rows in csv file
    $failed = 0; //flag to mark if insert/update failed on csv file

     //read CSV
    if($file)
    {
        $header = fgetcsv($file); //get headers 
        foreach($header as &$value)
        {
            //found on https://stackoverflow.com/questions/1176904/how-to-remove-all-non-printable-characters-in-a-string
            $value =preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value); //strip hidden chars from field
            $value = mysqli_real_escape_string($conn_da, $value);
            //echo $value .PHP_EOL;
        }
    }
    else{
        echo "Unable to open file" .PHP_EOL;
    }

    $tableInfo = get_tableinfo($conn_da, $formID);

    //print_r($tableInfo);

    if(QRForm == 1)
    {
        $assocArray = create_assocArray2($header, $tableInfo, $sysIDindex);
    }
    else{
        $assocArray = create_assocArray($header, $tableInfo, $sysIDindex);
    }
    
    //print_r($assocArray);
    //echo "SysID is in index $sysIDindex" .PHP_EOL;
 
    while( ($row = fgetcsv($file)) != FALSE )
    {
        $numRecords++;
        $count = count($row);
        //echo "Number of columns in this row is $count" .PHP_EOL;
        $flag = 0; //used to indicate if row got split
        $valuesToInsert = array(); //array to hold fields from a single row that need to be inserted into table
        for($i =0; $i <$count; $i++)
        {
            if($row[$i])
            {
                $row[$i] = mysqli_real_escape_string($conn_da, $row[$i]);
                if(array_key_exists($i, $assocArray)) //field is one of the fields that needs to get added to table
                {
                    $date = '';
                    $tableName = $assocArray[$i]['InsertIntoTBL'];
                    $fieldName_DBtbl = $assocArray[$i]['FieldName_DBtbl'];
                    $fieldType = $assocArray[$i]['FieldType'];
                    $timesFound = $assocArray[$i]['TimesFound'];
                    
                    if($i == $sysIDindex)
                    {
                        //$sysID = $field;
                        $sysID = $row[$i];

                        if(!$sysID)
                        {
                            echo "System ID $sysID is null, continuing to next row" .PHP_EOL;
                            continue 2;
                        }
                    }

                    if($fieldName_DBtbl == 'ActivityDate')
                    {
                        $date = $row[$i];
                        $dateValueIndex = $i; //record index number of date field
                        $formattedDate = Date_validate2($date);

                        if(!$formattedDate)
                        {
                            //insert record into tbl_SurveyCleaning?
                             echo "$date failed to get formatted correctly" .PHP_EOL;
                        }
                        else{
                            $sql = "UPDATE fd_Distr SET ActivityDate = '$formattedDate' WHERE SystemID = $sysID;";
                            if($numRecords < 5)
                            {
                                echo $sql;
                                echo "\n";
                            }
                            execute_query($conn_da, $sql);
                        }
                    }

                }
            }
            //continue 2;
        }
    }
/*
    //record time of insert for formID
    date_default_timezone_set('America/Los_Angeles'); //set timezone to PST
    $date = date('Y-m-d H:i:s');
    $sql = "UPDATE frm_FormVersion SET LastUpdate = '$date', NumInWA = '$numRecords' WHERE FormID = $formID;"; 
    $result = mysqli_query($conn_da, $sql);
    if(!$result)
    {
    
        echo "Failed to insert the current date that form got updated into table" . PHP_EOL;
        echo "MYSQL Statement is: $sql" .PHP_EOL;
        echo $sql .PHP_EOL;
    
        echo mysqli_error($conn_da);
    }
*/