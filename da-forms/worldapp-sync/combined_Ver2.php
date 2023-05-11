<?php
    require '../dbconn.php';
    require_once 'util.php';
    //require_once 'FormFunctions.php';

    /* $dbhost = "35.208.174.209";
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
 */
    // Grab all csv files from the desired folder
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

    //echo "File chosen is " . basename($oldestFile) . PHP_EOL;

    //opening file 
    $file = fopen($oldestFile, 'r');

    //get form ID, Ex. if csv is called "Export41613938--2022-06-08.csv", want to get 41613938 as the formID
    $formID = substr(basename($oldestFile), 6, 8);
    //echo "FormID is $formID" .PHP_EOL;
    //$formID = 41629569;

    //temporarily only do non HHV forms
    if($formID == '41628408' || $formID == '41638258') // || $formID == '41621788'
    {
        exit("FormID is $formID, skipping HHV forms that are split for now");
    }

    //these formID need to be ignored
    if( $formID == '41613938' || $formID == '41613922')
    {
        unlink($oldestFile);
        //echo "Form $formID no longer applicable, deleting File!" .PHP_EOL;
        exit;
    }

    if($formID == '41627897' || $formID == '41636330') //datacalc form, want to have other script process it
    {
        if(copy(realpath($oldestFile), "DataCalc/$oldestFile"))
        //if(move_uploaded_file(realpath($oldestFile), "DataCalc/$oldestFile"))
        {
            echo "File $oldestFile is Data Calc form, copied to DataCalc folder" .PHP_EOL;
            unlink($oldestFile);
        }
        else{
            echo "Failed to copy data clac form" .PHP_EOL;
        }

        exit;
    }
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
    $newTable = -1; //flag to mark if values should be inserted into different table (applies to HHV tables) 

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
        for($i =0; $i <$count; $i++)
        {
            $columns = "";
            $values = "";   

            if($row[$i])
            {
                $row[$i] = mysqli_real_escape_string($conn_da, $row[$i]);
                if(array_key_exists($i, $assocArray)) //field is one of the fields that needs to get added to table
                {
                    $date = '';

                    //check if table name is still the same - ignore very first column in CSV
                    //if table name has changed, insert values into last table first.
                    if( ($assocArray[$i]['InsertIntoTBL'] != $tableName) && ($i != 0)){ 
                        $newTable = $assocArray[$i]['InsertIntoTBL'];
                        //echo "Old table is: $tableName, New table is: $newTable\n". PHP_EOL;

                        if(!empty($valuesToInsert))
                        {      
                            if(RegularForm == 1){

                                $success = check_sysID($sysID, $tableName, $conn_da);
                                if($success != 0) //SysID is already present in table - need to update on SysID
                                {
                                    foreach($valuesToInsert as $key => $value)
                                    {  
                                        if($key != 'SystemID' && $key != 'FormID'){
                                            $sql = "UPDATE $tableName SET $key = '$value' WHERE SystemID = $sysID" .PHP_EOL;
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
                    }
                    else{
                        //echo "i is: $i, tablename was $tableName ";
                        $tableName = $assocArray[$i]['InsertIntoTBL'];
                        //echo "and now tablename is $tableName\n".PHP_EOL;
                    }
                    
                    $fieldName_DBtbl = $assocArray[$i]['FieldName_DBtbl'];
                    $fieldType = $assocArray[$i]['FieldType'];
                    $timesFound = $assocArray[$i]['TimesFound'];
                    
                   
                    //1) check if system ID is already in the table
                    if($i == $sysIDindex)
                    {
                        //$sysID = $field;
                        $sysID = $row[$i];
                    
                        $success = check_sysID($sysID, $tableName, $conn_da);
                        if($success != 0) //SysID is already present in table
                        {
                            //echo "SystemID $sysID is already in table, continue to next row" .PHP_EOL;
                            continue 2; //want to continue to next Row, NOT next field
                        }
                    
                        //if no system ID - continue to next row b/c row is empty?
                        if(!$sysID)
                        {
                            //echo "System ID $sysID is null, continuing to next row" .PHP_EOL;
                            continue 2;
                        }
                        //add value along with table column header as 'key'
                        $valuesToInsert += [$fieldName_DBtbl => $sysID];
                    }
                    

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
                        
                    }

                    if($fieldType == 'INT'){
                        $row[$i] = check_number($row[$i]);
                    }

                    if(QRForm == 1)
                    {
                        if($fieldName_DBtbl == 'QR Code' && $timesFound <= 1)
                        {
                            $QRcodeIndex = $i;
                        
                            $table = 0;
                            $recordID = '';
                            $numRows = check_QRcode($row[$i], $table, $recordID, $conn_da);

                            //QR code in QR_OutBound column does not start with 02 - row should be ignored
                            if($table != 1 &&  $assocArray[$i]['FieldName_SourceFile'] == 'QR_Outbound'){
                                //echo "Row $numRecords" .PHP_EOL;
                                //echo "QR code for QR_Outbound in form $formID, on systemID $sysID is 02 not 01 - $row[$i]" .PHP_EOL;
                                unset($valuesToInsert); 
                                continue 2; //skip to next row
                            }

                            if($numRows >= 1) //QR code matches what's in log_QRCodes
                            {
                                //echo "Match for QR code found in log_QRcodes" .PHP_EOL;
                                if($tableName == 'tbl_LogTransOut' && $table == 1)
                                {
                                    $valuesToInsert += ['TID' => $recordID];
                                }
                            }
                            else{ //QR codes isn't found, don't want to continue to insert data
                                if($table == 1)
                                {
                                    unset($valuesToInsert); //clear array 
                                    $i = 17; //set i to Dest_Scan 1 field
                                    //get new values for updated $i
                                    $tableName = $assocArray[$i]['InsertIntoTBL'];
                                    $timesFound = $assocArray[$i]['TimesFound'];
                                    //echo "i got reset, times found is $timesFound" . PHP_EOL;
                                }
                            
                            }
                        } //end of if QR code
                    } //end of QRForm

                    if(signedDDN == 2)
                    {
                        if($fieldName_DBtbl == 'QR Code' && $row[$i] != -1) // && $timesFound <= 1)
                        {
                            //check QRcode against log_QRCode table
                            $numRows = check_QRcode($row[$i], $table, $recordID, $conn_da);
                            //echo "numrows is $numRows" .PHP_EOL;
                            if($table != '2')
                            {
                                //echo "Table is $table, insert into error table" .PHP_EOL;
                                //TODO insert into error table
                                //$sql = "INSERT INTO table (columns) VALUES($formID, $sysID, $row[$i], 'QR code does not begin with 02";
                                //echo "continuing to next row" .PHP_EOL;
                                continue 2;
                            }

                            if($numRows == 0) //QR code doesn't match
                            { 
                                continue 2;
                            }
                        }
                    }

                    if($i != $sysIDindex && $i != $dateValueIndex && $timesFound <= 1 && $i != $QRcodeIndex)
                    {
                        if($tableName != 'tble_LogTransOutNin') //specific to QR forms
                        {
                            $valuesToInsert += [$fieldName_DBtbl => $row[$i]];

                            // if( (strpos($fieldName_DBtbl,'OCL_Other') !== FALSE) && ($tableName == 'fd_HHVs') )
                            // {
                            //     //echo "value: " .$row[$i] ."Column: $fieldName_DBtbl, i: $i" . PHP_EOL;
                            //     $valuesToInsert[$fieldName_DBtbl] = $row[$i];
                            // }
                        }
                    }

                    //3)Check if row gets split
                    if($timesFound > 1) 
                    {   
                        if(RegularForm == 1){
                            $toFind = $assocArray[$i]['FieldName_SourceFile'];
                            $fieldType = $assocArray[$i]['FieldType'];
                        }

                        // echo "Header to find is $toFind" . PHP_EOL;
                        $h = 0;
                        for($k=$i; $k < $count; ++$k)
                        {
                            if($row[$k])
                            {
                                $row[$k] = mysqli_real_escape_string($conn_da, $row[$k]);

                                if($fieldType == 'INT'){
                                    $row[$k] = check_number($row[$k]);
                                }
                                if(RegularForm == 1)
                                {
                                    $match = $header[$k]; //get header for this cell
                                    //echo "In for loop Cell header is $match" .PHP_EOL;
                                    //if(str_contains($match, $toFind))
                                    if(strpos($match, $toFind) !== FALSE)
                                    {
                                        //echo "$toFind is in $match" . PHP_EOL;   
                                        $hashtable[$h][$fieldName_DBtbl] = $row[$k]; 
                                        $flag = 1;
                                        $h++;
                                        $i = $k;
                                    }
                                    else{
                                        break;
                                    }  
                                }

                                if(QRForm == 1)
                                {
                                    //echo "after mysqli escape string, field is  $row[$k]" .PHP_EOL;
                                    if(array_key_exists($k, $assocArray))
                                    {
                                        $fieldName_DBtbl = $assocArray[$k]['FieldName_DBtbl'];
                                        $toFind = $assocArray[$k]['FieldName_SourceFile'];
                                        $match = $header[$k]; //get header for this cell
                                        $fieldType = $assocArray[$k]['FieldType'];
                                        //echo "In for loop Cell header is $match" .PHP_EOL;
                                        //if(str_contains($match, $toFind))
                                        if(strpos($match, $toFind) !== FALSE)
                                        {  
                                            //echo "field is  $row[$k]" .PHP_EOL;
                                            if($fieldType == 'INT'){
                                                $row[$k] = check_number($row[$k]);
                                            }
                                            if($fieldName_DBtbl == 'QR Code')
                                            {
                                                if($row[$k] != 0 && $row[$k] != 1) //actual QR code
                                                {
                                                    //echo "QR code is $row[$k], k is $k, column is $fieldName_DBtbl" .PHP_EOL;
                                                    $table = 0;
                                                    $recordID2 = '';
                                                    $numRows = check_QRcode($row[$k], $table, $recordID2, $conn_da);
                                                    //echo "numrows is $numRows" .PHP_EOL;
                                                    if($numRows >= 1) //QR code matches what's in log_QRCodes
                                                    { 
                                                        //echo "k is $k" .PHP_EOL;
                                                        //echo "Match for QR code found in log_QRcodes" .PHP_EOL;
                                                        $flag = 1; //track if row got split
                                                        $hashtable[$h]['TDID'] = $recordID2;
                                                    }
                                                    else{ //QR codes doesn't match what's in log_QRCodes, 
                                                        $k += 19; //increment k so $row[$k] is now the next dest_scan_#
                                                    }
                                                }
                                                else if($row[$k] == 1) //no QR code, but other fields in group
                                                {
                                                    $flag = 1; //track if row got split
                                                    $hashtable[$h]['TDID'] = '';
                                                }
                                                else if ($row[$k] == 0) //no fields in grouping
                                                {
                                                    $k += 19; //increment k so $row[$k] is now the next dest_scan_#
                                                }
                                            } 
                                            else{
                                                $hashtable[$h][$fieldName_DBtbl] = $row[$k]; 
                                            }
    
                                            if($fieldName_DBtbl == 'LINK_DNDest')
                                            {
                                                //echo "incrementing h\n";
                                                $h++;
                                            }
                                        }  //end of if matching header
                                    } //end of if k exists in assocArray
                                    $i = $k;
                                } //end of if QRForm
                            } //end of if row[k]
                        }  //end of k loop
                    } //end if timesfound >1
                } //end of if i in assocArray    
            } //end of if row[i] exists
            $dateValueIndex = -1; //reset 
        } //end of i loop

        $columns = "";
        $values = "";
        //print_r($valuesToInsert); 
        
        if(!empty($valuesToInsert))
        {      
            if(RegularForm == 1){ //} || signedDDN == 2){
            
            /* //code used to update already inserted values
                foreach($valuesToInsert as $key => $value)
                {  
                    if($key != 'SystemID' && ($key == 'SubmitDate' || $key == 'CHW_DAID') ){
                    
                        $sql = "UPDATE $tableName SET $key = '$value' WHERE SystemID = $sysID" .PHP_EOL;
                        $result = mysqli_query($conn_da, $sql);
                        if($key == 'SubmitDate' && $numRecords < 5){
                            echo $sql .PHP_EOL;
                        }
                        if(!$result){
                            echo "Failed to update where systemID = $sysID" .PHP_EOL;
                            echo $sql;
                            echo mysqli_error($conn_da);
                        }
                    }
                    
                }  
                $sql = "UPDATE $tableName SET formID = $formID WHERE SystemID = $sysID" .PHP_EOL;
                $result = mysqli_query($conn_da, $sql);
                if(!$result){
                    echo "Failed to update formID where systemID = $sysID" .PHP_EOL;
                    echo $sql;
                    echo mysqli_error($conn_da);
                }
            */
                get_columns_values($valuesToInsert, $columns, $values);
            }
                
            if(QRForm == 1)
            {
                $ID = 0; //reset ID
                $ID = $valuesToInsert['TID'];
                //echo $ID .PHP_EOL;
                //4) Insert data into table
                if($ID != 0)
                {
                    foreach($valuesToInsert as $key=>$value)
                    {
                        if($key != 'TID'){
                            //echo "UPDATE tbl_LogTransOut SET " .$key. " = " .$value. " WHERE TID = " .$ID. ";" . PHP_EOL;
                            $sql = "UPDATE tbl_LogTransOut SET " .$key. " = '" .$value. "' WHERE TID = " .$ID. ";" . PHP_EOL;
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result) {
                                $failed++;
                                echo "Failed to update LogTransOut table for $formID on TID = $ID" . PHP_EOL;
                                //echo "MYSQL Statement is: " .PHP_EOL;
                                echo $sql. PHP_EOL;
                            
                                echo mysqli_error($conn_da);
                            }
                        }
                    }
                }
                //echo "\n";
            }

            if(signedDDN == 2)
            {
                if($valuesToInsert['hasQRCode'] == 'Yes' && $valuesToInsert['QR Code'] != -1) //be careful about case sensitive
                {
                    foreach($valuesToInsert as $key=>$value)
                    {
                        //if($key != 'QR Code' && $key != 'hasQRCode' && $key != 'isSummaryDNOK.A1'){
                        if($key == 'SystemID' || $key == 'Location_Dest' || $key == 'LINK_DNDest' || $key == 'TID' || $key == 'CellID_Dest'){
                           $sql = "UPDATE $tableName SET " .$key. " = '" .$value. "' WHERE TDID = " .$recordID. ";" . PHP_EOL;
                        
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result) {
                                $failed++;
                                echo "Failed to update LogTransOutNin table for $formID on TDID = $recordID" . PHP_EOL;
                                //echo "MYSQL Statement is: " .PHP_EOL;
                                echo $sql. PHP_EOL;
                            
                                echo mysqli_error($conn_da);
                            }
                        
                        }
                    }
                }
                else if( $valuesToInsert['hasQRCode'] == 'No' && $valuesToInsert['QR Code'] == -1) {
                    foreach($valuesToInsert as $key=>$value)
                    {
                        if($key == 'SystemID' || $key == 'TID' || $key == 'Location_Dest' || $key == 'CellID_Dest' || $key == 'LINK_DNDest'){
                            $sql = "INSERT INTO $tableName ( $key ) VALUES ( '$value');" .PHP_EOL;
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result) {
                                $failed++;
                                echo "Failed toinsert into LogTransOutNin table for $formID" . PHP_EOL;
                                //echo "MYSQL Statement is: " .PHP_EOL;
                                echo $sql. PHP_EOL;
                            
                                echo mysqli_error($conn_da);
                            }
                        }
                    }
                } //end of if 'hasQRCode'

                if($valuesToInsert['isSummaryDNOK.A1'] == 'Yes' && $valuesToInsert['hasQRCode'] == 'Yes')
                {
                    $sql = "UPDATE tbl_LogTransOutNin SET QRec_Tots_Dura = QSent_Tots_Dura, QRec_Dam_Dura = QSent_Dam_Dura, QRec_Posters = QSent_Posters "
                          ."Where TDID = $recordID;";
                          $result = mysqli_query($conn_da, $sql);
                    if(!$result)
                    {
                        echo "failed to update # stoves/posters in LogTransOutNin" .PHP_EOL;
                        echo $sql .PHP_EOL;
                        echo mysqli_error($conn_da);
                    }
        
                /*
                    $sql = "SELECT QSent_Tots_Dura, QSent_Dam_Dura, QSent_Posters FROM tbl_LogTransOutNin WHERE TDID = $recordID;";
                    $result = mysqli_query($conn_da, $sql);
                    
                    if($result)
                    {
                        $StovesAndPosters = array();
                        while ($row = mysqli_fetch_assoc($result)) {
                            $StovesAndPosters[] = $row;
                        }

                        if(!empty($StovesAndPosters))
                        {
                            $count = count($StovesAndPosters);
                            for($m=0; $m < $count; $m++){
                                foreach($StovesAndPosters[$m] as $key => $value)
                                {
                                    if($value){
                                        $sql = "UPDATE tbl_LogTransOutNin SET $key = $value WHERE TDID = $recordID;" .PHP_EOL;
                                    
                                        $result = mysqli_query($conn_da, $sql);
                                        if(!$result)
                                        {
                                            echo "failed to update # stoves/posters in LogTransOutNin" .PHP_EOL;
                                            echo $sql .PHP_EOL;
                                            echo mysqli_error($conn_da);
                                        }
                                    }
                                
                                }
                            }
                        }
                        unset($StovesAndPosters);
                    
                    }
                    else{
                        echo "Failed to get results for the stoves/posters" .PHP_EOL;
                        echo mysqli_error($conn_da);
                    }
                */
        
                }
                else if( $valuesToInsert['isSummaryDNOK.A1'] == 'No'){
                    foreach($valuesToInsert as $key => $value)
                    {
                        if( $value != -1 && ( $key == 'QRec_Tots_Dura' || $key == 'QRec_Posters' || $key == 'QRec_Dam_Dura') ){
                            $sql = "UPDATE tbl_LogTransOutNin SET $key = $value WHERE TDID = $recordID;" .PHP_EOL;
                        
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result)
                            {
                                echo "failed to update # stoves/posters in LogTransOutNin" .PHP_EOL;
                                echo $sql .PHP_EOL;
                                echo mysqli_error($conn_da);
                            }
                        
                        }
                    }
                    
                } //end of if 'isSummaryDNOK'            
            } //end of if signedDDN
        
        } 
        if($flag == 1) //means row got split
        {
            //get values in repeated columns and insert into table
            $count = count($hashtable);
            $repeatedValues = "";
            $columns2 = "";
            for($n=0; $n<$count; ++$n)
            {
                if(RegularForm == 1){
                    get_columns_values($hashtable[$n], $columns2,$repeatedValues);

                    //4) Insert data into table
                    $sql = "INSERT INTO ".$tableName. "(".$columns. ',' .$columns2 .") VALUES(".$values. ',' .$repeatedValues. ");";
                    $result = mysqli_query($conn_da,$sql);
                    if(!$result){
                        $failed++;
                        echo "Failed to insert into table - for split form $formID" . PHP_EOL;
                        //echo "MYSQL Statement is: " .PHP_EOL;
                        echo $sql. PHP_EOL;
                    
                        echo mysqli_error($conn_da);
                    }
                
                    $repeatedValues = NULL;
                    $columns2 = NULL;
                }
                if(QRForm == 1)
                {
                    if(!$hashtable[$n]['TDID']) //no TID
                    {
                        $items = count($hashtable[$n]);
                        if($items > 1)
                        {
                            get_columns_values($hashtable[$n], $columns2, $repeatedValues);
                            //4) Insert data into table
                            $sql = "INSERT INTO tbl_LogTransOutNin ( `TID`, ".$columns2 .") VALUES('" .$recordID. "', " .$repeatedValues. ");"; 
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result){
                                $failed++;
                                echo "Failed to insert into tbl_LogTransOutNin table form $formID, TID = $recordID" . PHP_EOL;
                                //echo "MYSQL Statement is: " .PHP_EOL;
                                echo $sql .PHP_EOL;
                            
                                echo mysqli_error($conn_da);
                            }

                            $repeatedValues = NULL;
                            $columns2 = NULL;
                        }
                    }
                    else{
                        $ID = $hashtable[$n]['TDID'];
                        foreach($hashtable[$n] as $key =>$value)
                        {  
                            if($key != 'TDID') //don't want to update TID value itself
                            {
                                //echo "UPDATE tbl_LogTransOutNin SET " .$key. " = " .$value. " WHERE TDID = " .$ID.";" .PHP_EOL;
                                $sql = "UPDATE tbl_LogTransOutNin SET " .$key. " = '" .$value. "' WHERE TDID = " .$ID.";";
                                $result = mysqli_query($conn_da, $sql);
                                if(!$result){
                                    $failed++;
                                    echo "Failed to update tbl_LogTransOutNin table for form $formID where TDID = $ID" . PHP_EOL;
                                    //echo "MYSQL Statement is: " .PHP_EOL;
                                    echo $sql .PHP_EOL;
                                
                                    echo mysqli_error($conn_da);
                                }
                            }     
                        }
                        $sql = "UPDATE tbl_LogTransOutNin SET TID = " .$recordID. " WHERE TDID = " .$ID.";";
                        $result = mysqli_query($conn_da, $sql);
                        if(!$result){
                            $failed++;
                            echo "Failed to update TID for tbl_LogTransOutNin table where TDID = $ID" . PHP_EOL;
                            //echo "MYSQL Statement is: " .PHP_EOL;
                            echo $sql .PHP_EOL;
                        
                            echo mysqli_error($conn_da);
                        }
                        //echo "\n";
                    }
                } //end of QRForm
            } //end of loop
            unset($hashtable);
        }
    
        else{
            if(RegularForm == 1 && (!empty($valuesToInsert)) ){ //($columns != '' && $values != '') 
                $success = check_sysID($sysID, $tableName, $conn_da);
                if($success != 0) //SysID is already present in table - need to update on SysID
                {
                    foreach($valuesToInsert as $key => $value)
                    {  
                        if($key != 'SystemID' && $key != 'FormID'){
                            $sql = "UPDATE $tableName SET $key = '$value' WHERE SystemID = $sysID" .PHP_EOL;
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

        }

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
        unset($valuesToInsert); //clear array for next row
    
    } //end of while loop

    fclose($file);

    $file = fopen('parsedForms.txt', 'a');
    fwrite($file, $oldestFile."\n");
    fclose($file);

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

    //Delete file
    unlink($oldestFile);
