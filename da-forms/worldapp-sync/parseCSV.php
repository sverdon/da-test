<?php
    require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database
    require_once('util.php');

    // Grab all csv files from the desired folder
    $files = glob( '*.csv' );

    //Check if there are any files, if no files exit
    if(empty($files))
    {
        exit("No files found");
    }

    // Sort files by modified time, latest to earliest
    // Use SORT_ASC in place of SORT_DESC for earliest to latest
    array_multisort(
    array_map( 'filemtime', $files ),
    SORT_NUMERIC,
    SORT_ASC,
    $files
    );

    $oldestFile = $files[0]; // the latest modified file should be the first. 

    //opening file 
    $file = fopen($oldestFile, 'r');
    
    //get form ID
    //Ex. if csv is called "Export41613938--2022-06-08.csv", want to get 41613938 as the formID
    $formID = substr($oldestFile, 6, 8);
    $flag = 0; //used to mark if csv row got split

    if($formID == '41613987') //formID of form that has QR codes
    {
        //CALL QRforms.php script
    }

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
        echo "Unable to open file" . PHP_EOL;
    }

    $tableInfo = get_tableinfo($conn_da, $formID);

    //print_r($tableInfo);

    //$assocArray = array();
    //$i = 0;
    $sysIDindex = -1;
    $assocArray = create_assocArray($header, $tableInfo, $sysIDindex);

    // print_r($assocArray);

    //echo "SysID is in index $sysIDindex" .PHP_EOL;
    $dateValueIndex = -1; //variable used to keep track of which index a field with a date value (m/d/y) is in
    //foreach($array as $row)
    while( ($row = fgetcsv($file)) != FALSE )
    {
        $count = count($row);
        //echo "Number of columns in this row is $count" .PHP_EOL;
        $flag = 0; //used to indicate if row got split
        $valuesToInsert = array(); //array to hold fields from a single row that need to be inserted into table
        for($i =0; $i <$count; $i++)
        {
            if($row[$i])
            {
                
                if(array_key_exists($i, $assocArray)) //field is one of the fields that needs to get added to table
                {
                    $date = '';
                    $tableName = $assocArray[$i]['InsertIntoTBL'];
                    $fieldName_DBtbl = $assocArray[$i]['FieldName_DBtbl'];
                    $fieldType = $assocArray[$i]['FieldType'];
                    $timesFound = $assocArray[$i]['TimesFound'];
                    
                    $row[$i] = mysqli_real_escape_string($conn_da, $row[$i]);

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

                    //2) If header is ActivityDate or Submit date (check the index #) then check date format 
                    if($fieldType == 'DATE')//check if field is a date value (m/d/y...etc)
                    {
                        $date = $row[$i];
                        $dateValueIndex = $i; //record index number of date field
                        $formattedDate = Date_validate($date);
                        //echo "Formatted date is $formattedDate" . PHP_EOL;
                        $valuesToInsert += [$fieldName_DBtbl => $formattedDate];
                        if(!$formattedDate)
                        {
                            //insert record into tbl_SurveyCleaning?
                            echo "Date failed, insert into tbl_SurveyCleaning";
                        }
                        
                        $valuesToInsert += [$fieldName_DBtbl => $formattedDate];
                    }
                    else{
                        //echo "Field " . $row[$i]." is not a date" .PHP_EOL; 
                    }

                    if($i != $sysIDindex && $i != $dateValueIndex && $timesFound <= 1)
                    {
                        $valuesToInsert += [$fieldName_DBtbl => $row[$i]];
                    }

                    //3)Check if row gets split
                    if($timesFound > 1) 
                    {   
                        //echo "i is $i" .PHP_EOL;
                        $toFind = $assocArray[$i]['FieldName_SourceFile'];

                        // echo "Header to find is $toFind" . PHP_EOL;
                        $h = 0;
                        for($k=$i; $k < $count; ++$k)
                        {
                            $flag = 1;
                            //echo "K is $k" .PHP_EOL;
                            if($row[$k])
                            {
                                $match = $header[$k]; //get header for this cell
                                //echo "In for loop Cell header is $match" .PHP_EOL;
                                if(str_contains($match, $toFind))
                                {
                                    //echo "$toFind is in $match" . PHP_EOL;   
                                    $hashtable[$h][$fieldName_DBtbl] = $row[$k]; 
                                    $h++;
                                    $i = $k;
                                }
                                else{
                                    break;
                                }  
                            }
                        }  
                    }
                }
                $dateValueIndex = -1; //reset
            }
        }

        $columns = "";
        $values = "";
        get_columns_values($valuesToInsert, $columns, $values);

        if($flag == 1) //means row got split
        {
            //get values in repeated columns and insert into table
            $count = count($hashtable);
            $repeatedValues = "";
            $columns2 = "";
            for($n=0; $n<$count; ++$n)
            {
                $items = count($hashtable[$n]);
                $m = 0;
                foreach($hashtable[$n] as $key =>$value)
                {
                    //echo $key .':'. $value .", " ;
                    if($value && ($m+1) != $items)
                    {
                        $columns2 .= '`' .$key .'`,';
                        $repeatedValues .= '\'' .$value .'\',' ;
                    }
                    else if($m+1 == $items)
                    {
                        $columns2 .= '`'.$key.'`';
                        $repeatedValues .= '\''.$value.'\'';
                    }
                    //echo $repeatedValues .PHP_EOL;
                    ++$m;
                }

                //4) Insert data into table
                //echo "INSERT INTO ".$tableName. "(".$columns. ',' .$columns2 .") VALUES(".$values. ',' .$repeatedValues. ");" . PHP_EOL;
                //echo "\n";
                $sql = "INSERT INTO ".$tableName. "(".$columns. ',' .$columns2 .") VALUES(".$values. ',' .$repeatedValues. ");";
                $result = mysqli_query($conn_da,$sql);

                if(!$result)
                {
                    echo "Failed to insert into table:" . mysqli_error($conn_da) . PHP_EOL;
                }
            
                $repeatedValues = NULL;
                $columns2 = NULL;
            }
            unset($hashtable);
        }
        else{
            
        /*
            echo $columns . PHP_EOL;
            echo "\n";
            echo $values . PHP_EOL;
        */
            //4) Insert data into table
            $sql = "INSERT INTO ".$tableName. "(".$columns.") VALUES(".$values.");";
            $result = mysqli_query($conn_da, $sql);
        
            if(!$result)
            {
                echo "Failed to insert into table" . mysqli_error($conn_da) . PHP_EOL;
            }
        
        }
        unset($valuesToInsert); //clear array for next row
    
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

