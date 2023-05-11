<?php
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database
    require_once('util.php');

     //opening file 
     $file = fopen("form41613987.csv", 'r'); //NEED TO CHANGE THIS 
    
     //get form ID
     //Ex. if csv is called "Export41613938--2022-06-08.csv", want to get 41613938 as the formID
     $formID = 41613987;
     $flag = 0; //used to mark if csv row got split
 
      //read CSV
     if($file)
     {
         $header = fgetcsv($file); //get headers 
 
         foreach($header as &$value)
         {
             //found on https://stackoverflow.com/questions/1176904/how-to-remove-all-non-printable-characters-in-a-string
             //$value =preg_replace('/[\x00-\x1F\x7F]/u','', $value); //strip any hidden chars
             $value =preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
             $value = mysqli_real_escape_string($conn_da, $value);
             //echo $value .PHP_EOL;
         }
         //fclose($file);
     }
     else{
         echo "Unable to open file" .PHP_EOL;
     }
 
     $tableInfo = get_tableinfo($conn_da, $formID);
 
     //print_r($tableInfo);

     //$assocArray = array();
     //$i = 0;
     $sysIDindex = -1;
     $assocArray = create_assocArray2($header, $tableInfo, $sysIDindex);
 
     //print_r($assocArray);

     //echo "SysID is in index $sysIDindex" .PHP_EOL;
     $dateValueIndex = -1; //variable used to keep track of which index a field with a date value (m/d/y) is in
     $QRcodeIndex = -1;
     $recordID = 0;
     $repeat = 0;
     //foreach($array as $row)
     while( ($row = fgetcsv($file)) != FALSE )
     {
         $count = count($row);
         //echo "Number of columns in this row is $count" .PHP_EOL;
         $flag = 0; //used to indicate if row got split
         $valuesToInsert1 = array(); //array to hold fields to be inserted into tbl_LogTransOut table
         //$valuesToInsert2 = array(); //array to hold fields to be inserted into tbl_LogTransOutNin
         for($i =0; $i <$count; $i++)
         { 
            if(array_key_exists($i, $assocArray)) //field is one of the fields that needs to get added to table
            {
                //echo "i is $i" . PHP_EOL;
                $date = '';
                $tableName = $assocArray[$i]['InsertIntoTBL'];
                $fieldName_DBtbl = $assocArray[$i]['FieldName_DBtbl'];
                $fieldType = $assocArray[$i]['FieldType'];
                $timesFound = $assocArray[$i]['TimesFound'];

                if($row[$i] && $timesFound <= 1)
                {
                    $row[$i] = mysqli_real_escape_string($conn_da, $row[$i]);
            /*
                    //1) check if system ID is already in the table
                    if($i == $sysIDindex)
                    {
                        //$sysID = $field;
                        $sysID = $row[$i];
                        $success = check_sysID($sysID, $tableName, $conn_da);
                        if($success != 0) //SysID is already present in table
                        {
                            echo "SystemID $sysID is already in table, continue to next row" .PHP_EOL;
                            continue 2; //want to continue to next Row, NOT next field
                        }
                    
                        //if no system ID - continue to next row b/c row is empty?
                        if(!$sysID)
                        {
                            echo "System ID $sysID is null, continuing to next row" .PHP_EOL;
                            continue 2;
                        }
                        //add value along with table column header as 'key'
                        $valuesToInsert += [$fieldName_DBtbl => $sysID];
                    }
            */
                    //2) If header is ActivityDate or Submit date (check the index #) then check date format 
                    if($fieldType == 'DATE')//check if field is a date value (m/d/y...etc)
                    {
                        $date = $row[$i];
                        $dateValueIndex = $i; //record index number of date field
                        $formattedDate = Date_validate($date);
                        //echo "Formatted date is $formattedDate" . PHP_EOL;
                        //$valuesToInsert += [$fieldName_DBtbl => $formattedDate];
                        if(!$formattedDate)
                        {
                            //insert record into tbl_SurveyCleaning?
                            echo "Date failed, insert into tbl_SurveyCleaning";
                        }
                        
                        if($tableName == 'tbl_LogTransOut')
                        {
                            $valuesToInsert1 += [$fieldName_DBtbl => $formattedDate];
                        }
                    
                    } //end of if date
                    
                    if($fieldName_DBtbl == 'QR Code')
                    {
                        
                        $QRcodeIndex = $i;
                      
                        $table = 0;
                        $recordID = '';
                        $numRows = check_QRcode($row[$i], $table, $recordID, $conn_da);
      
                        if($numRows >= 1) //QR code matches what's in log_QRCodes
                        {
                            if($tableName == 'tbl_LogTransOut' && $table == 1)
                            {
                                $valuesToInsert1 += ['TID' => $recordID];
                            }
                        }
                        else{ //QR codes isn't found, don't want to continue to insert data
                            if($table == 1)
                            {
                                unset($valuesToInsert1); //clear array 
                                $i = 17; //set i to Dest_Scan 1 field
                                $tableName = $assocArray[$i]['InsertIntoTBL'];
                                $timesFound = $assocArray[$i]['TimesFound'];
                            }
                        
                        }
                    } //end of if QR code

                    if($i != $sysIDindex && $i != $dateValueIndex && $timesFound <= 1 && $i != $QRcodeIndex)
                    {
                        if($tableName == 'tbl_LogTransOut')
                        {
                            $valuesToInsert1 += [$fieldName_DBtbl => $row[$i]];
                        }
                    
                    }
                } //end of if not null and timesfound <=1

                //3)Check if row gets split
                if($timesFound > 1) 
                {   
                    $h=0;
                    for($k=$i; $k < $count; ++$k)
                    {
                        $flag = 1; //track if row got split
                        if(array_key_exists($k, $assocArray))                   
                        {
                            $fieldName_DBtbl = $assocArray[$k]['FieldName_DBtbl'];
                            
                            $toFind = $assocArray[$k]['FieldName_SourceFile'];
                            $match = $header[$k]; //get header for this cell
                            //echo "In for loop Cell header is $match" .PHP_EOL;
                            if(str_contains($match, $toFind))
                            {  
                                if($fieldName_DBtbl == 'QR Code')
                                {
                                    if($row[$k])
                                    {
                                        $row[$k] = mysqli_real_escape_string($conn_da, $row[$k]);
                                        $table = 0;
                                        $recordID2 = '';
                                        $numRows = check_QRcode($row[$k], $table, $recordID2, $conn_da);
                                
                                        if($numRows >= 1) //QR code matches what's in log_QRCodes
                                        {
                                            $hashtable[$h]['TID'] = $recordID2;
                                        }
                                        else{ //QR codes doesn't match what's in log_QRCodes, 
                                            $k += 19; //increment k so $row[$k] is now the next dest_scan_#
                                        }
                                    }
                                    else{
                                        $hashtable[$h]['TID'] = '';
                                    }
                                }
                                else{
                                    if($row[$k])
                                    {
                                        $row[$i] = mysqli_real_escape_string($conn_da, $row[$k]);
                                        $hashtable[$h][$fieldName_DBtbl] = $row[$k]; 
                                    }
                                }
                                $i = $k;

                                if($fieldName_DBtbl == 'LINK_DNDest')
                                {
                                    //echo "incrementing h\n";
                                    $h++;
                                }

                            } //end of if matching header
                        } //end of k is in assocArray
                    } //end of k loop  
                } //end of times found > 1
            } //end of i in assocArray
            $dateValueIndex = -1; //reset
            
        } //end of  i loop

        //echo "Printing hashtable " .PHP_EOL;
        //print_r($hashtable);
    
        if($flag == 1) //means row got split
        {
            //get values in repeated columns and insert into table
            $count = count($hashtable);
            $repeatedValues = "";
            $columns2 = "";
            for($n=0; $n<$count; ++$n)
            {
                //$items = count($hashtable[$n]);
                if(!$hashtable[$n]['TID']) //no TID
                {
                    $items = count($hashtable[$n]);
                    if($items > 1)
                    {
                        get_columns_values($hashtable[$n], $columns2, $repeatedValues);
                        //4) Insert data into table
                        $sql = "INSERT INTO tbl.LogTransOutNin ( `TID`, ".$columns2 .") VALUES('" .$recordID. "' " .$repeatedValues. ");"; 
                        $result = mysqli_query($conn_da, $sql);
                        if(!$result)
                        {
                            echo "Failed to insert into table" . PHP_EOL;
                        }

                        $repeatedValues = NULL;
                        $columns2 = NULL;
                    }
                }
                else{
                    $ID = $hashtable[$n]['TID'];
                    foreach($hashtable[$n] as $key =>$value)
                    {  
                        if($key != 'TID') //don't want to update TID value itself
                        {
                            $sql = "UPDATE tbl_LogTransOutNin SET " .$key. " = " .$value. " WHERE TDID = " .$ID.";";
                            $result = mysqli_query($conn_da, $sql);
                            if(!$result)
                            {
                                echo "Failed to insert into table" . PHP_EOL;
                            }
                        }     
                    }
                    echo "\n";
                }
            }
            unset($hashtable);
        }

        if(!empty($valuesToInsert1))
        {
            //print_r($valuesToInsert1);
            $columns = "";
            $values = "";
            $ID = $valuesToInsert1['TID'];
            //echo $ID .PHP_EOL;
            //4) Insert data into table
            foreach($valuesToInsert1 as $key=>$value)
            {
                if($key != 'TID'){
                    //echo "UPDATE tbl_LogTransOut SET " .$key. " = " .$value. " WHERE TID = " .$ID. ";" . PHP_EOL;
                    $sql = "UPDATE tbl_LogTransOut SET " .$key. " = " .$value. " WHERE TID = " .$ID. ";" . PHP_EOL;
                    $result = mysqli_query($conn_da, $sql);
        
                    if(!$result)
                    {
                        echo "Failed to insert into table" . PHP_EOL;
                    }
                }
            }
            echo "\n\n";
        }
    
         unset($valuesToInsert1); //clear array for next row
     
     }

     fclose($file);

     //record time of insert for formID
     date_default_timezone_set('America/Los_Angeles'); //set timezone to PST
     $date = date('Y-m-d H:i:s');
     $sql = "INSERT INTO dbthezxpnokgxv.frm_FormVersion (LastUpdate) VALUES ($date) WHERE frm_FormVersion.FormID = $formID;"; 
     $result = mysqli_query($conn_da, $sql);
     if(!$result)
     {
         echo "Failed to insert into table" . PHP_EOL;
     }
 
     echo "closing mysql connection" .PHP_EOL;
     mysqli_close($conn_da);
 
     //Delete file
     // unlink($oldestFile);
