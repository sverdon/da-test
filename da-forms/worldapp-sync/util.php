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

    //execute multi-query
    function multi_query($conn_da, $sql, $message)
    {
        if($sql != NULL){
            mysqli_multi_query($conn_da, $sql);
            do{
                if (0 !== mysqli_errno($conn_da))
                {
                    echo $message. PHP_EOL; 
                    echo $sql . "\n" .PHP_EOL;
                    echo mysqli_error($conn_da);
                    break;
                }
            }while(mysqli_next_result($conn_da));

            $numRows = mysqli_affected_rows($conn_da);
            echo "Query affected $numRows records\n" .PHP_EOL;
        }
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
            exit("Failed to get corresponding results based on form ID $formID\n");
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
                $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`,  HHVFormID,`QNum`, `DistrFormID`, `HHID`, `Sup_DAID`) SELECT " . ($i + 1) .", " . $HHVFormID." , 1101, $DistrFormID, HHID, Sup_DAID "
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


     //Functions returns the parent regions of a given GID and assigns the region name to a Level/Boundary
    function get_parentGIDs($conn_da, $gid) //&$country, &$boundary1, &$boundary2, &$boundary3, &$boundary4, &$boundary5, &$boundary6, &$boundary7) 
    {
        //assoc-array that gets returned with values filled in
        $regions = array("Country" => "", "Boundary1" => "", "Boundary2" => "", 
        "Boundary3" => "", "Boundary4" => "",  "Boundary5" => "",
        "Boundary6" => "", "Boundary7" => "",);

        $sql = "SELECT * From g_Boundaries";
        $result = execute_query($conn_da, $sql);

         //save query results into array
         while ($row = mysqli_fetch_assoc($result)) {
            $boundaries[] = $row;
        }

        //print_r($boundaries);

        //group by columns
        $Level1 = array_column($boundaries, 'Level1');
        //print_r($Level1);
        $Level2 = array_column($boundaries, 'Level2');
        //print_r($Level2);
        $Level3 = array_column($boundaries, 'Level3');
        //print_r($Level3);
        $Level4 = array_column($boundaries, 'Level4');
        //print_r($Level4);
        $Level5 = array_column($boundaries, 'Level5');
        //print_r($Level5);
        $Level6 = array_column($boundaries, 'Level6');
        //print_r($Level6);
        $Level7 = array_column($boundaries, 'Level7');
        //print_r($Level7);
    
        if($gid != NULL){
            $sql = "SELECT T2.GID, T2.RegionType,T2.RegionName
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

            $result = execute_query($conn_da, $sql);

            while($row = mysqli_fetch_assoc($result))
            {
                if($row['RegionType'] == 'Country'){
                    $regions['Country'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level1)){
                    $regions['Boundary1'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level2)){
                    $regions['Boundary2'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level3) ){
                    $regions['Boundary3'] = $row['RegionName'];
                }
                elseif(in_array($row['RegionType'], $Level4)){
                    $regions['Boundary4'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level5)){
                    $regions['Boundary5'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level6)){
                    $regions['Boundary6'] = $row['RegionName'];
                }
                elseif( in_array($row['RegionType'], $Level7)){
                    $regions['Boundary7'] = $row['RegionName'];
                }
            }

            //echo "Country: $country, Level1: $boundary1, Level2: $boundary2, Level3: $boundary3, Level4: $boundary4, Level5: $boundary5";
            
            //print_r($regions);
            return $regions;
        }
    
    }

/*  //function no longer used
    function revised_dataCleaning($conn_da, $sql, $table, $queryNum)
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

        if($queryNum < 1100){ //Not data matching queries
            //insert results into SurveryCleaning table
            if (!empty($aSurveyDistr)) {
                $count = count($aSurveyDistr);
                for($i = 0; $i<$count; $i++)
                {           
                    if($table == 'fd_Distr'){ 
                        $sql = "INSERT INTO tbl_ErrorSource(`TypeID`, `Source_Table`, `Source_TableID`)
                                VALUES ($queryNum, '$table', " .$aSurveyDistr[$i]['DistrFormID'] .")";

                    }
                    elseif($table == 'fd_HHVs'){
                        $sql = "INSERT INTO tbl_ErrorSource(`TypeID`, `Source_Table`, `Source_TableID`)
                                VALUES ($queryNum, '$table', " .$aSurveyDistr[$i]['HHVFormID']. ")";
                    }

                    $result = execute_query($conn_da, $sql);
                }
            }
            echo "QUERY $queryNum effected " . count($aSurveyDistr) . ' record(s)', PHP_EOL;
            unset($aSurveyDistr);

        }
        elseif($queryNum > 1100){ //Data Matching Queries
            if (!empty($aSurveyDistr)) {
                $count = count($aSurveyDistr);
                for($i = 0; $i<$count; $i++)
                {   
                    if($table == 'fd_Distr'){
                        $sql = "SELECT DistrFormID, HHID, SerialNumber, Link_PhotoID, Link_Poster, Link_PosterSig FROM $table 
                                WHERE  HHID =" . $aSurveyDistr[$i]['HHID'] . "and SerialNumber = '" . $aSurveyDistr[$i]['SerialNumber'] 
                        ."' and Link_PhotoID = '" . $aSurveyDistr[$i]['Link_PhotoID'] ."and Link_Poster = '" .$aSurveyDistr[$i]['Link_Poster'] 
                        ."' and Link_PosterSig = '" . $aSurveyDistr[$i]['Link_PosterSig'] ."' and IgnoreRecord <> -1;";

                        $result2 = execute_query($conn_da, $sql);
                        while($row2 = mysqli_fetch_assoc($result2)){
                            $sql = "INSERT INTO tbl_ErrorSource(`TypeID`, `Source_Table`, `Source_TableID`)
                                VALUES ($queryNum, '$table', " .$aSurveyDistr[$i]['DistrFormID'] .")";
                        }
                    }
                }
            }
        }
    }
*/

function getPrimaryKey($errorID) //Figure out what the name of the primary key column is. Ex DistrFormID or HHVFormID
{
    if(empty($errorID)){
        exit("ErroID is empty");
    }

    $pk = '';

    if(substr($errorID, -1) == 'a'){
        $pk = 'DistrFormID';
    }
    elseif(substr($errorID, -1) == 'b'){
        $pk = 'HHVFormID';
    }
    elseif(substr($errorID, -1) == 'c'){
        $pk = 'DistrID';
    }
    elseif(substr($errorID, -1) == 'd'){
        $pk = 'HHVID';
    }
    else{ //For Query 1010 - checks if SubmitDate is NULL, needs to be run on all tables
        $pk = '';
    }

    return $pk;
}

//Query to set inCleaning for records that just got flagged
//these marked records will not be considered in subsequent queries.
function mark_inCleaning($conn_da, $lvl)
{
    //array used when setting inCleaning value after queries run
    $tables = array("fd_Distr" => "DistrFormID", "fd_HHVs" => "HHVFormID");
    // "frm_FldRptPostDistr" => "DRID", "frm_FldRptULDistr" => "EUDID", "frm_FldRptULHHV" => "EUHID");

    foreach($tables as $key => $value)
    {
        //TODO: add end letters for other tables?
        if($key == "fd_Distr"){
            $end = 'a';
        }
        elseif($key == 'fd_HHVs'){
            $end = 'b';
        }

        if($lvl == 1){
            //NOTE: Queries 1010 and 1000 update inCleaning, ignoreRecord, BadSumit separately
            $types = "('1001$end', '1002$end', '1006$end', '1007$end')";
            $flag = -1;
        }
        elseif($lvl == 2){
            $types = "('1008$end', '1003$end', '1004$end', '1005$end', '1011$end', '1012$end', '1013$end')";
            $flag = -2;
        }
        elseif($lvl == 3){
            $types = "('1101$end', '1102$end', '1103$end')";
            $flag = -3;
        }

        // $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS SetCleaning AS
        // SELECT TypeID, Source_TableID, $value,
        // -- HHID, SerialNumber, ActivityDate, SubmitDate,
        // -- Link_PhotoID, Link_Poster, Link_PosterSig, 
        // IgnoreRecord, inCleaning, BadSubmit, 
        // FROM $key
        // JOIN tbl_ErrorSource es ON es.Source_TableID = $value
        // WHERE TypeID IN $types; 
        
        // UPDATE $key
        // INNER JOIN SetCleaning sc on sc.$value = $key.$value
        // SET $key.inCleaning = -2
        // WHERE TypeID IN $types;
        // DROP TABLE IF EXISTS SetCleaning;";

        //TODO: check if this alternative works
        //FIXME: Change back to inCleaning_Lvl1-2 column
        $sql = "UPDATE $key
                JOIN tbl_ErrorSource es ON es.Source_TableID = $value
                SET $key.inCleaning_Lvl3 = $flag
                WHERE TypeID IN $types;";
        
        //echo $sql . PHP_EOL;

        multi_query($conn_da, $sql, "Query to Mark inCleaning for Lvl$lvl failed on $key");
    }

}