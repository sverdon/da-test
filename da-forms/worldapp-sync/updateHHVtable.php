<?php
    //require '../dbconn.php';
    require_once 'util.php';


    $dbhost = "35.208.174.209";
    $dbuser = "u4nb3xb15qjtk";
    $dbpass = 'wgb)@ir$cC23';
    //$db = "dbthezxpnokgxv";
    $db = "dbs1qpdncyglvh";

    $conn_da = mysqli_connect($dbhost, $dbuser, $dbpass, $db);

    // Check connection
    if (!$conn_da) {
        die("Connection failed: " . mysqli_connect_error());
    }
    else{
        echo "Successful Connection!" . PHP_EOL;
    }

    // Grab all csv files from the desired folder
    //chdir("C:/Users/JingKappes/github/delagua-1/da-forms/worldapp-sync");
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
    echo "FormID is $formID" .PHP_EOL;

    //Check it's the right form
    if($formID != '41628408' && $formID != '41638258' && $formID != '41621788'){
        exit("FormID is $formID, not an HHV form");
    }

    define("RegularForm", "1");
    define("QRForm","0");
    define("signedDDN", "0");

    $dateValueIndex = -1; //variable used to keep track of which index a field with a date value (m/d/y) is in
    $QRcodeIndex = -1;
    $sysIDindex = -1;
    $sysID = 0;
    $recordID = 0;
    $numRecords = 0; //count number of rows in csv file
    $failed = 0; //flag to mark if insert/update failed on csv file
    $newTable = -1; //flag to mark if values should be inserted into different table (applies to HHV tables) 
    $tableName = '';

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
        $valuesToInsert += ['FormID' => $formID]; 
        
        //echo "\nNext row # $numRecords\n" .PHP_EOL;

        for($i =0; $i<$count; $i++)
        {
            $columns = "";
            $values = "";   

            if($row[$i])
            {
                $row[$i] = mysqli_real_escape_string($conn_da, $row[$i]);
                if(array_key_exists($i, $assocArray)) //field is one of the fields that needs to get added to table
                {
                    $date = '';
                    //echo "TableName is: $tableName, 'InsertIntoTBL is: " .$assocArray[$i]['InsertIntoTBL']. PHP_EOL;
                   
                    //check if table name is still the same - ignore very first column in CSV
                    //if table name has changed, insert values into last table first.
                    if( ($assocArray[$i]['InsertIntoTBL'] != $tableName) && ($i != 0)){ 
                        $newTable = $assocArray[$i]['InsertIntoTBL'];
                        //echo "Old table is: $tableName, New table is: $newTable\n". PHP_EOL;

                        if(!empty($valuesToInsert))
                        {      
                            if(RegularForm == 1 ){ //&& ($tableName = 'fd_HHV_Baseline')

                                //print_r($valuesToInsert);

                                $success = check_sysID($sysID, $tableName, $conn_da);
                                if($success != 0) //SysID is already present in table - need to update on SysID
                                {
                                    //echo "Updating on SystemID".PHP_EOL;
                                    foreach($valuesToInsert as $key => $value)
                                    {  
                                        if($key != 'SystemID' && $key != 'FormID'){
                                            $sql = "UPDATE $tableName SET $key = '$value' WHERE SystemID = $sysID" .PHP_EOL;
                                            //echo $sql;
                                            $result = mysqli_query($conn_da, $sql);
                                            if(!$result){
                                                echo "Failed to update where systemID = $sysID" .PHP_EOL;
                                                echo $sql;
                                                echo mysqli_error($conn_da);
                                            }
                                        }
                                    }  
                                }
                                else{
                                    get_columns_values($valuesToInsert, $columns, $values);
                                    // echo "Columns/Values to insert are: \n".PHP_EOL;
                                    // echo $columns .PHP_EOL;
                                    // echo $values . PHP_EOL;

                                    if($columns != '' && $values != ''){
                                        //4) Insert data into table
                                        //echo "INSERT INTO ".$tableName. "(".$columns.") VALUES(".$values.");" . PHP_EOL;
                                        //echo "\n";
                                        $sql = "INSERT INTO ".$tableName. "(".$columns.") VALUES(".$values.");";
                                        $result = mysqli_query($conn_da, $sql);
                                        if(!$result) {
                                            $failed++;
                                            echo "Failed to insert values for regular form $formID into table" . PHP_EOL;
                                            //echo "MYSQL Statement is: " .PHP_EOL;
                                            echo $sql. PHP_EOL;
                                        
                                            echo mysqli_error($conn_da);
                                        }  
                                    }
                                }

                                unset($valuesToInsert); //clear array for values that will go into DIFFERENT table
                                $valuesToInsert = array(); //make sure array exists to hold rest of values in row
                                //echo "Cleared array and recreated it\n".PHP_EOL;
                                $valuesToInsert += ['FormID' => $formID]; 
                                $valuesToInsert += ['SystemID' => $sysID];
                                //print_r($valuesToInsert);
                                //echo"\n".PHP_EOL;   
                            }
                        }
                        $tableName = $newTable; //store new table name
                        //echo "tableName = newTable: $tableName" . PHP_EOL;
                    }
                    else{
                        //echo "i is: $i, tablename was $tableName ";
                        //echo "i is: $i, table name is:" . $assocArray[$i]['InsertIntoTBL'] .PHP_EOL;
                        $tableName = $assocArray[$i]['InsertIntoTBL'];
                        //echo "and now tablename is $tableName\n".PHP_EOL;
                        //echo "Variable TableName is: $tableName" .PHP_EOL;
                    }
                    
                    
                    if($i == $sysIDindex)
                    {
                        //$sysID = $field;
                        $sysID = $row[$i];

                        //if no system ID - continue to next row b/c row is empty?
                        if(!$sysID)
                        {
                            echo "System ID $sysID is null, continuing to next row" .PHP_EOL;
                            continue 2;
                        }
                        //add value along with table column header as 'key'
                        $valuesToInsert += ['SystemID' => $sysID];
                    }

                    //echo "after checking SysID, tableName is: $tableName" . PHP_EOL;

                    //if($tableName == 'fd_HHV_Baseline' || $tableName == 'fd_HHV_Stuff')
                    //{
                        $fieldName_DBtbl = $assocArray[$i]['FieldName_DBtbl'];
                        $fieldType = $assocArray[$i]['FieldType'];
                        $timesFound = $assocArray[$i]['TimesFound'];

                        if($fieldType == 'DATE')//check if field is a date value (m/d/y...etc)
                        {
                            $date = $row[$i];
                            $dateValueIndex = $i; //record index number of date field

                            //check if it's the Submit Date or Activity Date - use different date validate functions
                            //bc Submit Date format is mm/dd/yy and Activity Date format is dd/mm/yy
                            if($fieldName_DBtbl == 'SubmitDate'){
                                $formattedDate = Date_validate($date);
                            }
                            else{
                                $formattedDate = Date_validate2($date);
                            }
                        /*
                            if($numRecords < 5){
                                echo "Date is $row[$i]" .PHP_EOL;
                                echo "Formatted date is $formattedDate" . PHP_EOL;
                            }
                        */
                        
                            if(!$formattedDate)
                            {
                                //insert record into tbl_SurveyCleaning?
                                echo "$date failed to get formatted correctly" .PHP_EOL;
                            }
                            else{
                                $valuesToInsert += [$fieldName_DBtbl => $formattedDate];
                            }
                            //echo "After checking date, tablename is $tableName" .PHP_EOL;
                        }

                        if($fieldType == 'INT'){
                            $row[$i] = check_number($row[$i]);
                           // echo "After Checking INT, tablename is: $tableName" . PHP_EOL;
                        }

                        if($i != $sysIDindex && $i != $dateValueIndex && $timesFound <= 1 && $i != $QRcodeIndex)
                        {
                            //echo "i is not sysID/Date/QR, tablename is $tableName" . PHP_EOL;

                            if($tableName != 'tble_LogTransOutNin') //specific to QR forms
                            {
                                $valuesToInsert += [$fieldName_DBtbl => $row[$i]];

                                // if($tableName == "fd_HHV_Baseline"){
                                //      echo $fieldName_DBtbl . ": " .$row[$i] .", ";
                                // }
                               
                            }
                            //echo "added value to array, tablename is $tableName" .PHP_EOL;
                        }

                        // echo "added values to array, table is $tableName. Array is: ".PHP_EOL;
                        // print_r($valuesToInsert);                        
                    //}
                } //end of if i in assocArray    
            } //end of if row[i] exists
            $dateValueIndex = -1; //reset 
        } //end of i loop
    
        //echo "Next row\n" .PHP_EOL;
        if(RegularForm == 1 && (!empty($valuesToInsert)) ){
            //echo "Last insert/update for row" .PHP_EOL;
            //print_r($valuesToInsert);

            $success = check_sysID($sysID, $tableName, $conn_da);
            if($success != 0) //SysID is already present in table - need to update on SysID
            {
                foreach($valuesToInsert as $key => $value)
                {  
                    if($key != 'SystemID' && $key != 'FormID'){
                        $sql = "UPDATE $tableName SET $key = '$value' WHERE SystemID = $sysID" .PHP_EOL;
                        //echo $sql . PHP_EOL;
                        $result = mysqli_query($conn_da, $sql);
                        if(!$result){
                            echo "Failed to update where systemID = $sysID" .PHP_EOL;
                            echo $sql;
                            echo mysqli_error($conn_da);
                        }
                    }
                }  
            }
            else{
                //4) Insert data into table
                //echo "INSERT INTO ".$tableName. "(".$columns.") VALUES(".$values.");" . PHP_EOL;
                //echo "\n";
                $sql = "INSERT INTO ".$tableName. "(".$columns.") VALUES(".$values.");";
                $result = mysqli_query($conn_da, $sql);
                if(!$result) {
                    $failed++;
                    echo "Failed to insert values for regular form $formID into table" . PHP_EOL;
                    //echo "MYSQL Statement is: " .PHP_EOL;
                    echo $sql. PHP_EOL;
                    echo mysqli_error($conn_da);
                }   
            }
        }

    /*
        if(RegularForm == 1 && !empty($valuesToInsert))
        {
            
            $sql = "UPDATE $tableName SET FormID = $formID WHERE SystemID = $sysID" .PHP_EOL;
                $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Failed to update formID where systemID = $sysID" .PHP_EOL;
                echo $sql;
                echo mysqli_error($conn_da);
            }
        }
    */

        unset($valuesToInsert); //clear array for next row
    }//end of while loop

    // $file = fopen('parsedForms.txt', 'a');
    // fwrite($file, $oldestFile."\n");
    // fclose($file);

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

    //echo "closing mysql connection" .PHP_EOL;
    mysqli_close($conn_da);

/*
    //if there were problems inserting csv data into tables
    if($failed > 0)
    {
        if(copy(realpath($oldestFile), "failedCSVs/$oldestFile"))
        {
            echo "File $oldestFile failed on some updates/inserts" .PHP_EOL;
            echo "Filed Copied!" .PHP_EOL;
        }
        else{
            echo "File $oldestFile failed on some updates/inserts. And failed to copy file to folder" .PHP_EOL;
        }
    }
*/
    //Delete file
    //unlink($oldestFile);
