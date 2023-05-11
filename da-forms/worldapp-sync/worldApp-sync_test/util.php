<?php

    //util function to execute queries and print error
    function execute_query($conn_da, $sql){
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo $sql . PHP_EOL;
            echo mysqli_error($conn_da);
            echo "Exiting".PHP_EOL;
            return exit; //Should return -1 or exit?
        }
        return $result;
    }

    //function first runs query which returns a table of which column values from csv need to be added to tables - based on formID
    //multi-D array is then created to represent the table. 
    function get_tableinfo($conn_da, $formID){
        $sql = "SELECT frm_Forms.FormDescr, 
        frm_FormVersionFields.FieldName_SourceFile, 
        frm_FormVersionFields.FieldName_DBtbl, 
        frm_FormVersionFields.InsertIntoTBL, 
        frm_FormVersionFields.FieldType,
        frm_FormVersionFields.TimesFound,
        frm_FormVersion.DSID
        FROM (frm_Forms INNER JOIN frm_FormVersion ON frm_Forms.DID = frm_FormVersion.DID) 
        INNER JOIN frm_FormVersionFields ON frm_FormVersion.DSID = frm_FormVersionFields.DSID
        WHERE (((frm_FormVersion.FormID)= $formID));";
        $result = mysqli_query($conn_da, $sql);

        if(!$result)
        {
            //echo "Failed to get corresponding results based on form ID\n";
            echo mysqli_error($conn_da);
            exit("Failed to get corresponding results based on form ID\n");
        }
        else{ //put resulting table into 2-d array 
            $tableInfo = array();
            $i=0;
            $columnNames = array();
            $fieldInfo = mysqli_fetch_fields($result); //get column headers

            foreach ($fieldInfo as $val) 
            {
                //printf("Name: %s\n", $val -> name);
                array_push($columnNames, $val->name);
                //$i++;
            }

            //echo "printing column names" .PHP_EOL;
            //print_r($columnNames);
            while($row = mysqli_fetch_assoc($result))
            {  
                $rows = array();
                foreach($row as $cell)
                {
                    //echo $cell .PHP_EOL;
                    array_push($rows, $cell);
                }

                $tableInfo[$i] = array_combine($columnNames, $rows);
                unset($rows);
                $i++;
            }

        }

        //print_r($tableInfo);
        return $tableInfo;
    }

    //function creates a array where each header, whose column values needs to get inserted into table, 
    //is associated with that header's index number. 
    function create_assocArray($header, $tableInfo, &$sysIDindex){
        $i=0;
        $assocArray = array();
        $count = count($header);
        //echo "Header has $count columns" .PHP_EOL;
        //foreach($header as $value)
        for($i=0; $i<$count; ++$i)
        {
            //echo "Header is: $value" .PHP_EOL;
            $j = 0;
            foreach($tableInfo as $row)
            { 
                $column = $tableInfo[$j]['FieldName_SourceFile'];
                $timesFound = $tableInfo[$j]['TimesFound'];
                //echo "Column is $column, Times found is $timesFound" .PHP_EOL;
                //if($value == $column) //if header value matches
                if($header[$i] == $column)
                {
                    //echo "header value is $header[$i], column is $column" . PHP_EOL;
                    $assocArray += [$i => $row];
                    if($header[$i] == 'System ID')
                    {
                        $sysIDindex = $i;
                    } 
                }
                if($timesFound > 1)
                {
                    //if(str_contains($header[$i], $column))
                    if(strpos($header[$i], $column) !== FALSE)
                    {
                        //echo "$header[$i] contains $column" .PHP_EOL;
                        $assocArray += [$i => $row];
                        $i += ($timesFound -1);
                    // echo "i is now $i" .PHP_EOL;
                    }
                }
                $j++;
            }
        }
        return $assocArray;
    }

    function create_assocArray2($header, $tableInfo, &$sysIDindex){
        $i=0;
        $assocArray = array();
        $count = count($header);
        $k = 2; //gets incrementes, used to append number Ex. CellID_Dest_#.A11
        //echo "Header has $count columns" .PHP_EOL;
        //foreach($header as $value)
        for($i=0; $i<$count; ++$i)
        {
            //echo "Header is: $value" .PHP_EOL;
            $j = 0;
           
            foreach($tableInfo as $row)
            { 
                $column = $tableInfo[$j]['FieldName_SourceFile'];
                $timesFound = $tableInfo[$j]['TimesFound'];
             
                //echo "Column is $column, Times found is $timesFound" .PHP_EOL;
                //if($value == $column) //if header value matches
                //if(str_contains($header[$i], $column))
                if(strpos($header[$i], $column) !== FALSE)
                {
                    ///echo "header value is $header[$i], column is $column" . PHP_EOL;
                    //echo "i is $i and row is ";
                    //print_r($row);
                    $assocArray += [$i => $row];
                    if($header[$i] == 'System ID')
                    {
                        $sysIDindex = $i;
                    } 
                }
                else if( $column == 'CellID_Dest_1.A11' && preg_match('/CellID_Dest_[0-9].A11/i', $header[$i]) > 0)
                {
                    //echo "Matching CellID_Dest $header[$i]" .PHP_EOL;
                    //echo "i is $i, header is $header[$i] and row is ";
                    //print_r($row);

                    $row['FieldName_SourceFile'] = "CellID_Dest_" .$k . ".A11";
                    //echo $row['FieldName_SourceFile'] . PHP_EOL;
                    $assocArray += [$i => $row];
                    //$k++;
                }
                else if( $column == 'Comments_Dest_1.A1' && preg_match('/Comments_Dest_[0-9].A1/i', $header[$i]) > 0)
                {
                    //echo "Matching Comments_Dest $header[$i]" . PHP_EOL;
                    $row['FieldName_SourceFile'] = "Comments_Dest_" .$k . ".A1";
                    $assocArray += [$i => $row];
                    ++$k;
                }
                $j++;
            }
        }
        return $assocArray;
    }

    function check_QRCode($QRcode, &$table, &$recordID, $conn_da)
    {
        $table = substr($QRcode, 0, 2);
        $recordID = substr($QRcode, 3, 7);

        //strip leading 0
        $table = ltrim($table, "0");
        $recordID = ltrim($recordID, "0");
        //echo "Record ID is $recordID" . PHP_EOL;

        //Check QR code in log_QRcodes table
        $sql = '';
        if($table == 1)
        {
            $sql = "SELECT * FROM log_QRcodes WHERE (TID, QRCode) = ('" .$recordID. "', '" .$QRcode. "');";
        }
        else if ($table == 2){
            $sql = "SELECT * FROM log_QRcodes WHERE (TDID, QRCode) = ('" .$recordID. "', '" .$QRcode. "');";
        }

        $result = mysqli_query($conn_da, $sql);
        if(!$result)
        {
            echo "Query to find QRcode in log_QRCodes failed\n";
            return NULL;
        }
        $numRows = mysqli_num_rows($result);

        return $numRows;
    }
    
    //function checks if systemID is already in table csv data will get inserted into
    function check_sysID($sysID, $tableName, $conn_da){
        //echo "System ID is $sysID" .PHP_EOL;
        //echo "Table to insert into is $tableName" . PHP_EOL;
        
        $sql = "SELECT EXISTS (SELECT * FROM ".$tableName." WHERE SystemID = '".$sysID."');";
        $result = mysqli_query($conn_da, $sql);

        //Even if sysID is not in table, query still return 1 row, with value 0
        //$row = mysqli_num_rows($result);
        $row = mysqli_fetch_row($result);
        $success = $row[0];

        return $success;
    }

    function check_number($number)
    {
        if(strpos($number, ',') >= 0)
        {
            $number = str_replace(',', '', $number);
        }    
        return $number; 
    }
    
    //Function formats incoming date to yyyy/mm/dd format, assumes that incoming date format is varitaion of Month/Day/Year
    function Date_validate($input_date) {
        $input_date = str_replace(' ','', $input_date);
        //echo "date is now $input_date" .PHP_EOL;
        //mm/dd/yyyy 
        if(DateTime::createFromFormat('m/d/Y', $input_date) && DateTime::createFromFormat('m/d/Y', $input_date)->format('m/d/Y')== $input_date){
            return DateTime::createFromFormat('m/d/Y', $input_date)->format('Y-m-d');
        // m/d/yyyy
        }else if(DateTime::createFromFormat('n/j/Y', $input_date) && DateTime::createFromFormat('n/j/Y', $input_date)->format('n/j/Y')== $input_date){
            return DateTime::createFromFormat('n/j/Y', $input_date)->format('Y-m-d');
        //mm/dd/yyyy 1-12hr:min
        }else if(DateTime::createFromFormat('m/d/Yg:i', $input_date) && DateTime::createFromFormat('m/d/Yg:i', $input_date)->format('m/d/Yg:i')== $input_date){
            return DateTime::createFromFormat('m/d/Yg:i', $input_date)->format('Y-m-d');
        //mm/dd/yyyy 1-12hr:min Am/PM
        }else if(DateTime::createFromFormat('m/d/Yg:iA', $input_date) && DateTime::createFromFormat('m/d/Yg:iA', $input_date)->format('m/d/Yg:iA')== $input_date){
            return DateTime::createFromFormat('m/d/Yg:iA', $input_date)->format('Y-m-d');
        //mm/dd/yyyy 1-12hr:min::sec AM/PM
        }else if(DateTime::createFromFormat('m/d/Yg:i:sA', $input_date) && DateTime::createFromFormat('m/d/Yg:i:sA', $input_date)->format('m/d/Yg:i:sA')== $input_date){
            return DateTime::createFromFormat('m/d/Yg:i:sA', $input_date)->format('Y-m-d');
        //mm/dd/yyyy 0-23hr:min AM/PM
        }else if(DateTime::createFromFormat('m/d/YG:iA', $input_date) && DateTime::createFromFormat('m/d/YG:iA', $input_date)->format('m/d/YG:iA')== $input_date){
            return DateTime::createFromFormat('m/d/YG:iA', $input_date)->format('Y-m-d');
        //mm/dd/yyyy 0-23hr:min::sec AM/PM
        }else if(DateTime::createFromFormat('m/d/YG:i:sA', $input_date) && DateTime::createFromFormat('m/d/YG:i:sA', $input_date)->format('m/d/YG:i:sA')== $input_date){
            return DateTime::createFromFormat('m/d/YG:i:sA', $input_date)->format('Y-m-d');
        // m/dd/yyyy 0-23hr:min
        }else if(DateTime::createFromFormat('n/d/YG:i', $input_date) && DateTime::createFromFormat('n/d/YG:i', $input_date)->format('n/d/YG:i')== $input_date){
            return DateTime::createFromFormat('n/d/YG:i', $input_date)->format('Y-m-d');
        // m/dd/yyyy 0-23hr:min AM/PM
        }else if(DateTime::createFromFormat('n/d/YG:iA', $input_date) && DateTime::createFromFormat('n/d/YG:iA', $input_date)->format('n/d/YG:iA')== $input_date){
            return DateTime::createFromFormat('n/d/YG:iA', $input_date)->format('Y-m-d');
        // m/dd/yyyy 0-23hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('n/d/YG:i:sA', $input_date) && DateTime::createFromFormat('n/d/YG:i:sA', $input_date)->format('n/d/YG:i:sA')== $input_date){
            return DateTime::createFromFormat('n/d/YG:i:sA', $input_date)->format('Y-m-d');
        // m/d/yyyy 0-12hr:min AM/PM
        }else if(DateTime::createFromFormat('n/j/Yg:iA', $input_date) && DateTime::createFromFormat('n/j/Yg:iA', $input_date)->format('n/j/Yg:iA')== $input_date){
            return DateTime::createFromFormat('n/j/Yg:iA', $input_date)->format('Y-m-d');
        // m/d/yyyy 0-12hr:min 
        }else if(DateTime::createFromFormat('n/j/Yg:i', $input_date) && DateTime::createFromFormat('n/j/Yg:i', $input_date)->format('n/j/Yg:i')== $input_date){
            return DateTime::createFromFormat('n/j/Yg:i', $input_date)->format('Y-m-d');
        // m/d/yyyy 0-23hr:min 
        }else if(DateTime::createFromFormat('n/j/YG:i', $input_date) && DateTime::createFromFormat('n/j/YG:i', $input_date)->format('n/j/YG:i')== $input_date){
            return DateTime::createFromFormat('n/j/YG:i', $input_date)->format('Y-m-d');
        // m/d/yyyy 0-23hr:min AM/PM
        }else if(DateTime::createFromFormat('n/j/YG:iA', $input_date) && DateTime::createFromFormat('n/j/YG:iA', $input_date)->format('n/j/YG:iA')== $input_date){
            return DateTime::createFromFormat('n/j/YG:iA', $input_date)->format('Y-m-d');
        // m/d/yyyy 0-23hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('n/j/YG:i:sA', $input_date) && DateTime::createFromFormat('n/j/YG:i:sA', $input_date)->format('n/j/YG:i:sA')== $input_date){
            return DateTime::createFromFormat('n/j/YG:i:sA', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 01-12hr:min:sec
        }else if(DateTime::createFromFormat('Y-m-dh:i:s', $input_date) && DateTime::createFromFormat('Y-m-dh:i:s', $input_date)->format('Y-m-dh:i:s')== $input_date){
            return DateTime::createFromFormat('Y-m-dh:i:s', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 00-23hr:min:sec
        }else if(DateTime::createFromFormat('Y-m-dH:i:s', $input_date) && DateTime::createFromFormat('Y-m-dH:i:s', $input_date)->format('Y-m-dH:i:s')== $input_date){
            return DateTime::createFromFormat('Y-m-dH:i:s', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 0-23hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('Y/m/dG:i:sA', $input_date) && DateTime::createFromFormat('Y/m/dG:i:sA', $input_date)->format('Y/m/dG:i:sA')== $input_date){
            return DateTime::createFromFormat('Y/m/dG:i:sA', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 1-12hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('Y/m/dg:i:sA', $input_date) && DateTime::createFromFormat('Y/m/dg:i:sA', $input_date)->format('Y/m/dg:i:sA')== $input_date){
            return DateTime::createFromFormat('Y/m/dg:i:sA', $input_date)->format('Y-m-d');
        // yyyy-mm-dd
        } else if (DateTime::createFromFormat('Y-m-d', $input_date) && DateTime::createFromFormat('Y-m-d', $input_date)->format('Y-m-d') == $input_date) {
            return DateTime::createFromFormat('Y-m-d', $input_date)->format('Y-m-d');
        // yyyy/mm/dd
        } else if (DateTime::createFromFormat('Y/m/d', $input_date) && DateTime::createFromFormat('Y/m/d', $input_date)->format('Y/m/d') == $input_date) {
            return DateTime::createFromFormat('Y/m/d', $input_date)->format('Y-m-d');
        } else {
            return 'null';
        }
    }

    //Function formats incoming date to yyyy/mm/dd format, assumes that incoming date format is varitaion of Day/Month/Year
    function Date_validate2($input_date) {
        $input_date = str_replace(' ','', $input_date);
        //echo "date is now $input_date" .PHP_EOL;
        // dd-mm-YYYY
        if(DateTime::createFromFormat('d-m-Y', $input_date) && DateTime::createFromFormat('d-m-Y', $input_date)->format('d-m-Y') == $input_date) {
            return DateTime::createFromFormat('d-m-Y', $input_date)->format('Y-m-d');
        // dd/mm/yyyy
        } else if (DateTime::createFromFormat('d/m/Y', $input_date) && DateTime::createFromFormat('d/m/Y', $input_date)->format('d/m/Y') == $input_date) {
            return DateTime::createFromFormat('d/m/Y', $input_date)->format('Y-m-d');
        // d/m/yyyy
        }else if (DateTime::createFromFormat('j/n/Y', $input_date) && DateTime::createFromFormat('j/n/Y', $input_date)->format('j/n/Y') == $input_date) {
            return DateTime::createFromFormat('j/n/Y', $input_date)->format('Y-m-d');
        // dd/mm/yyyy 1-12hrs:min AM/PM
        }else if(DateTime::createFromFormat('d/m/Yg:iA', $input_date) && DateTime::createFromFormat('d/m/Yg:iA', $input_date)->format('d/m/Yg:iA')== $input_date){
            return DateTime::createFromFormat('d/m/Yg:iA', $input_date)->format('Y-m-d');
        // dd/mm/yyyy 1-12hrs:min:sec AM/PM
        }else if(DateTime::createFromFormat('d/m/Yg:i:sA', $input_date) && DateTime::createFromFormat('d/m/Yg:i:sA', $input_date)->format('d/m/Yg:i:sA')== $input_date){
            return DateTime::createFromFormat('d/m/Yg:i:sA', $input_date)->format('Y-m-d');
        // dd/mm/yyyy 1-12hrs:min AM/PM
        }else if(DateTime::createFromFormat('d/m/YG:iA', $input_date) && DateTime::createFromFormat('d/m/YG:iA', $input_date)->format('d/m/YG:iA')== $input_date){
            return DateTime::createFromFormat('d/m/YG:iA', $input_date)->format('Y-m-d');
        // d/m/YYYY 0-23hrs:min AM/PM
        }else if (DateTime::createFromFormat('j/n/YG:iA', $input_date) && DateTime::createFromFormat('j/n/YG:iA', $input_date)->format('j/n/YG:iA') == $input_date) {
            return DateTime::createFromFormat('j/n/YG:iA', $input_date)->format('Y-m-d');
        // d/m/YYYY 0-23hrs:min:sec AM/PM
        }else if (DateTime::createFromFormat('j/n/YG:i:sA', $input_date) && DateTime::createFromFormat('j/n/YG:i:sA', $input_date)->format('j/n/YG:i:sA') == $input_date) {
            return DateTime::createFromFormat('j/n/YG:i:sA', $input_date)->format('Y-m-d');
        // d/m/YYYY 1-12hrs:min AM/PM
        }else if (DateTime::createFromFormat('j/n/Yg:iA', $input_date) && DateTime::createFromFormat('j/n/Yg:iA', $input_date)->format('j/n/Yg:iA') == $input_date) {
            return DateTime::createFromFormat('j/n/Yg:iA', $input_date)->format('Y-m-d');
        // d/m/YYYY 1-12hrs:min:sec AM/PM
        }else if (DateTime::createFromFormat('j/n/Yg:i:sA', $input_date) && DateTime::createFromFormat('j/n/Yg:i:sA', $input_date)->format('j/n/Yg:i:sA') == $input_date) {
            return DateTime::createFromFormat('j/n/Yg:i:sA', $input_date)->format('Y-m-d');
         // yyyy-mm-dd 01-12hr:min:sec
        }else if(DateTime::createFromFormat('Y-m-dh:i:s', $input_date) && DateTime::createFromFormat('Y-m-dh:i:s', $input_date)->format('Y-m-dh:i:s')== $input_date){
            return DateTime::createFromFormat('Y-m-dh:i:s', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 00-23hr:min:sec
        }else if(DateTime::createFromFormat('Y-m-dH:i:s', $input_date) && DateTime::createFromFormat('Y-m-dH:i:s', $input_date)->format('Y-m-dH:i:s')== $input_date){
            return DateTime::createFromFormat('Y-m-dH:i:s', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 0-23hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('Y/m/dG:i:sA', $input_date) && DateTime::createFromFormat('Y/m/dG:i:sA', $input_date)->format('Y/m/dG:i:sA')== $input_date){
            return DateTime::createFromFormat('Y/m/dG:i:sA', $input_date)->format('Y-m-d');
        // yyyy-mm-dd 1-12hr:min:sec AM/PM
        }else if(DateTime::createFromFormat('Y/m/dg:i:sA', $input_date) && DateTime::createFromFormat('Y/m/dg:i:sA', $input_date)->format('Y/m/dg:i:sA')== $input_date){
            return DateTime::createFromFormat('Y/m/dg:i:sA', $input_date)->format('Y-m-d');
        // yyyy-mm-dd
        } else if (DateTime::createFromFormat('Y-m-d', $input_date) && DateTime::createFromFormat('Y-m-d', $input_date)->format('Y-m-d') == $input_date) {
            return DateTime::createFromFormat('Y-m-d', $input_date)->format('Y-m-d');
        // yyyy/mm/dd
        } else if (DateTime::createFromFormat('Y/m/d', $input_date) && DateTime::createFromFormat('Y/m/d', $input_date)->format('Y/m/d') == $input_date) {
            return DateTime::createFromFormat('Y/m/d', $input_date)->format('Y-m-d');
        } else {
            return 'null';
        }
    }

    //concats all the values to be inserted into the table into a string.
    //Column names are concated into a different string
    function get_columns_values($valuesToInsert, &$columns, &$values){
        //echo "Printing array of values to insert" .PHP_EOL;
        //print_r($valuesToInsert);

        //get column names into a string and values into another string
        $numItems = count($valuesToInsert);
        $i=0;
        foreach($valuesToInsert as $key => $value)
        {
            if($value && ($i+1) != $numItems) //make sure value isn't null
            {
                $columns .= '`' .$key.'`, '; //not sure if the backticks are needed around each column name
                $values .= '\''.$value.'\',';
            }
            else if($i+1 == $numItems)
            {
                $columns .= '`'.$key.'`';
                $values .= '\''.$value.'\'';
            }
            ++$i;
        }
    }

    function dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum)
    {
        $aSurveyDistr = array();

        $result = mysqli_query($conn_da, $sql);

        if(!$result)
        {
            echo "Query $queryNum failed on $table". PHP_EOL;
            echo mysqli_error($conn_da);
        }

        //save query results into array
        while ($row = mysqli_fetch_assoc($result)) {
            $aSurveyDistr[] = $row;
        }

        //insert results into SurveryCleaning table
        if (!empty($aSurveyDistr)) {
            $count = count($aSurveyDistr);
            for($i = 0; $i<$count; $i++)
            {                 
                $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`,  HHVFormID,`QNum`, `DistrFormID`, `HHID`, `Sup_DAID`) SELECT " . ($i + 1) .", " . $HHVFormID." , 1101, $DistrFormID, 'HHID', 'Sup_DAID' "
                        . " from $table where HHID =" . $aSurveyDistr[$i]['HHID'] . "and SerialNumber = '" . $aSurveyDistr[$i]['SerialNumber'] 
                        ."' and Link_PhotoID = '" . $aSurveyDistr[$i]['Link_PhotoID'] ."and Link_Poster = '" .$aSurveyDistr[$i]['Link_Poster'] 
                        ."' and Link_PosterSig = '" . $aSurveyDistr[$i]['Link_PosterSig'] ."' and IgnoreRecord <> -1;";
                //echo $sql .PHP_EOL;

                $result = mysqli_query($conn_da, $sql);
                if(!$result){
                    echo "Insert for query $queryNum failed on $table" .PHP_EOL;
                    echo $sql .PHP_EOL;
                    echo mysqli_error($conn_da);
                }          
            }
        }
        echo "QUERY $queryNum effected " . count($aSurveyDistr) . ' record(s)', PHP_EOL;
        unset($aSurveyDistr);
    }

