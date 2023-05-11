<?PHP 
    //require('../dbconn.php');
    require('util.php');
    //exit(); //pause data Cleaning script for now
  
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
        echo "Successful Connection!\n";
    }    


    //array used when setting inCleaning value after queries run
    $tables = array("fd_Distr" => "DistrFormID", "fd_HHVs" => "HHVFormID");
    // "frm_FldRptPostDistr" => "DRID", "frm_FldRptULDistr" => "EUDID", "frm_FldRptULHHV" => "EUHID");

    //get Error Types from tbl_ErrorType where Severity =1
    $sql = "SELECT ETID, `Table` FROM tbl_ErrorType WHERE Severity <= 1";
    $result = execute_query($conn_da, $sql);

    $errorTypes = array();

    while($row = mysqli_fetch_assoc($result))
    {
        $errorTypes[] = $row;
    }
    
    //print_r($errorTypes);


    $pk = ''; //variable to hold name of column that is primary key. Ex DistrFormID or HHVFormID
    $end = '';
    $count = count($errorTypes);
    
    for($i=0; $i<$count; $i++)
    {
        $sql = NULL; //reset veriable
        $result = NULL;

        $errorID = $errorTypes[$i]['ETID'];
        $table = $errorTypes[$i]['Table'];

        $pk = getPrimaryKey($errorID);

        if($pk == "DistrFormID"){
            $end = 'a';
        }
        elseif($pk == 'HHVFormID'){
            $end = 'b';
        }
        elseif($pk == 'DistrID'){
            $end = 'c';
        }
        elseif($pk == 'HHVID'){
            $end = 'd';
        }

        echo "Table is $table, Primary Key Column is: $pk, errorType is: $errorID, end is: $end" . PHP_EOL;

        //QUERIES THAT CHECK INDIVIDUAL RECORDS
    
        if($errorID == '1010'){ //SubmitDate is NULL or '0000-00-00'

            //NOTE: LogTransOut, LogTranOutNin, MonitoringData tables, also get data from CSVs, but don't have SubmitDate Column
            //FIXME: Fld_ tables don't have Badsubmit or IgnoreRecord columns
            $tables = array("fd_Distr" => "DistrFormID", "fd_HHVs" => "HHVFormID"); 
                            //"frm_FldRptPostDistr" => "DRID", "frm_FldRptULDistr" => "EUDID", "frm_FldRptULHHV" => "EUHID");

            foreach($tables as $key => $value){
                if($value == "DistrFormID"){
                    $end = 'a';
                }
                elseif($value == "HHVFormID"){
                    $end = 'b';
                }

                $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID$end', $value, 0 FROM $key
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $value
                    WHERE ( (SubmitDate is NULL or SubmitDate = '0000-00-00') and Source_TableID is NULL)
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID$end', `Source_TableID` = $value, `Lvl` = 0;"; // OR
                    //( (SubmitDate is NULL or SubmitDate = '0000-00-00') and Lvl = 1 AND TypeID <> '1010' and TypeID LIKE '%$end');";

                //echo $sql .PHP_EOL;
            
                $result = mysqli_query($conn_da, $sql);
                if(!$result){
                    echo "Query $errorID failed on $key" .PHP_EOL;
                    echo $sql .PHP_EOL;
                    echo mysqli_error($conn_da);
                }
                else{
                    $numRows = mysqli_affected_rows($conn_da);
                    echo "Query $errorID affected $numRows records" .PHP_EOL;
                }
            
                // $sql = "UPDATE $key SET IgnoreRecord = -1, BadSubmit = -1, inCleaning = -1 WHERE SubmitDate is NULL or SubmitDate = '0000-00-00';";
                // $result = mysqli_query($conn_da, $sql);
                // if(!$result){
                //     echo "Query $errorID failed on $$key" .PHP_EOL;
                //     echo $sql .PHP_EOL;
                //     echo mysqli_error($conn_da);
                // }
                // else{
                //     $numRows = mysqli_affected_rows($conn_da);
                //     echo "Query $errorID affected $numRows records" .PHP_EOL;
                // }
            }
            $sql = ''; //reset variable so query doesn't get executed again
        }
    

    
        if($errorID == '1000a' || $errorID == '1000b'){ //Missing HHID and SerialNumber

            //echo "Marking all records in table where HHID and SerialNumber is null" .PHP_EOL;
            //$sql = "UPDATE $table SET IgnoreRecord = -1, BadSubmit = -1, inCleaning = -1 WHERE (HHID is null OR HHID = 0 ) and SerialNumber is null;";
            //$result = execute_query($conn_da, $sql);

            //$numRows = mysqli_affected_rows($conn_da);
            //echo "Update affected $numRows records" .PHP_EOL;

            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 0 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk  
                    WHERE ( (HHID is null OR HHID = 0 ) and SerialNumber is null and Source_TableID is NULL)
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 0;"; // OR
                    //( (HHID is null OR HHID = 0 ) and SerialNumber and Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end');";
        }
        
        if($errorID == '1014a' || $errorID == '1014b'){ //SN is not found in ProductBarcodes table
            $sql = "INSERT INTO tbl_ErrorSource (`Source_TableID`, `TypeID`, `Lvl`) SELECT DISTINCT($pk), '$errorID', 0 FROM $table
                    LEFT JOIN tbl_ProductBarcodes AS pb ON SerialNumber = Barcode
                    WHERE (SerialNumber is NOT NULL and Barcode is NULL) AND Source_TableID is NULL -- OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 0;";
        }

         if($errorID == '1001a' || $errorID == '1001b'){ //Has HHID, Missing SerialNumber
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk  
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NULL) AND
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }
    
        if($errorID == '1002a' || $errorID == '1002b'){ //Missing HHID, Has SerialNumber
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk 
                    WHERE ( (HHID is NULL or HHID = 0) AND SerialNumber is Not NULL) AND 
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }

        if($errorID == '1006a' || $errorID == '1006b'){ //Activity Date is NULL or 0000-00-00
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk 
                    WHERE (ActivityDate is null or ActivityDate = '0000-00-00') AND
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }
        elseif($errorID == '1006c'){ //DistrDate in sch_Distr is NULL or 0000-00-00
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE (DistrDate is NULL or DistrDate = '0000-00-00') AND 
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }
        elseif( $errorID == '1006d'){//HHVDate in sch_HHV is NULL or 0000-00-00
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE (HHVDate is NULL OR HHVDate = '0000-00-00') AND 
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }
    
        if($errorID == '1007a' || $errorID == '1007b'){ //Activity Date is NOT null and before Jan 1st, 2020
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 1 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE (ActivityDate is not NULL AND ActivityDate < '2020-01-01') AND 
                    (Source_TableID is NULL OR (Lvl = 1 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 1;";
        }

    
        //TODO:check if this is correct
        //TODO: make severity 2?
        // if($errorID == '1009a'){ //Activity Date is NOT in sch_Distr table
        //     $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk FROM $table
        //             LEFT JOIN g_Locations g ON g.PreviousID_DA = d.VillageID 
        //             LEFT JOIN sch_Distrs plan on plan.GID = g.ParentID and plan.DistrDate = d.ActivityDate 
        //             WHERE `IgnoreRecord` <> -1 and plan.DistrID is null and ActivityDate <> '0000-00-00' and inCleaning is NULL";
        // }
    

        //Execute Query
        if($sql != NULL){
            //echo $sql .PHP_EOL;
        
            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query $errorID failed on $table" .PHP_EOL;
                echo $sql .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            else{
                $numRows = mysqli_affected_rows($conn_da);
                echo "Query $errorID affected $numRows records" .PHP_EOL;
            }
        
            $sql = NULL;
        }

    }

    //mark_inCleaning($conn_da, 1);


/*
    //get Error Types from tbl_ErrorType where Severity = 2
    //TODO: change where condition - CHECK severity
    $sql = "SELECT ETID, `Table` FROM tbl_ErrorType WHERE  ETID LIKE '1013a'"; //Severity = 2";
    $result = execute_query($conn_da, $sql);

    $errorTypes = array();

    while($row = mysqli_fetch_assoc($result)){
        $errorTypes[] = $row;
    }
    
    //print_r($errorTypes);


    $pk = ''; //variable to hold name of column that is primary key. Ex DistrFormID or HHVFormID
    $end = '';

    $count = count($errorTypes);

    for($i=0; $i<$count; $i++)
    {
        $sql = NULL; //reset veriable
        $result = NULL;

        $errorID = $errorTypes[$i]['ETID'];
        $table = $errorTypes[$i]['Table'];

        $pk = getPrimaryKey($errorID);

        if($pk == "DistrFormID"){
            $end = 'a';
        }
        elseif($pk == 'HHVFormID'){
            $end = 'b';
        }
        elseif($pk == 'DistrID'){
            $end = 'c';
        }
        elseif($pk == 'HHVID'){
            $end = 'd';
        }

        echo "Table is $table, Primary Key Column is: $pk, errorType is: $errorID, end is: $end" . PHP_EOL;

        //QUERIES THAT CHECK INDIVIDUAL RECORDS
    
        if($errorID == '1008a' || $errorID == '1008b'){ //Activity Date is NOT null and after SubmitDate
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 2 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE (ActivityDate is not NULL AND ActivityDate > SubmitDate AND SubmitDate <> '0000-00-00') And 
                    ( Source_TableID is NULL OR (Lvl = 2 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 2;";
        }

        if($errorID == '1011a' || $errorID == '1011b'){ //Has HHID, SerialNumber, Link_PhotoID and Link_Poster. Missing Link_PosterSig
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 2 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_PhotoID is NOT NULL AND Link_Poster is Not NULL and Link_PosterSig is NULL) AND 
                    ( Source_tableID is NULL OR (Lvl = 2 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 2;";            
        }

        if($errorID == '1012a' || $errorID == '1012b'){ //Has HHID, SerialNumber, Link_PhotoID, Link_PosterSig. Missing Link_Poster
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 2 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_PhotoID is NOT NULL AND Link_PosterSig is NOT NULL and Link_Poster is NULL) AND
                    ( Source_TableID is NULL OR (Lvl = 2 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 2;";                   
        }

        if($errorID == '1013a' || $errorID == '1013b'){ //Has HHID, SerialNumber, Link_Poster, Link_PosterSig. Missing Link_PhotoID
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 2 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_Poster is NOT NULL AND Link_PosterSig is NOT NULL and Link_PhotoID is NULL ) AND
                    ( Source_TableID is NULL OR (Lvl = 2 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 2;";
        }

        //Execute Query
         if($sql != NULL){
            //echo $sql .PHP_EOL;

            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query $errorID failed on $table" .PHP_EOL;
                echo $sql .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            else{
                $numRows = mysqli_affected_rows($conn_da);
                echo "Query $errorID affected $numRows records" .PHP_EOL;
            }
        
            $sql = NULL; //reset
        }
    }
*/
/*
    //get Error Types from tbl_ErrorType where Severity = 3
    $sql = "SELECT ETID, `Table` FROM tbl_ErrorType WHERE Severity = 3"; 
    $result = execute_query($conn_da, $sql);

    $errorTypes = array();

    while($row = mysqli_fetch_assoc($result)){
        $errorTypes[] = $row;
    }
    
    //print_r($errorTypes);


    $pk = ''; //variable to hold name of column that is primary key. Ex DistrFormID or HHVFormID
    $end = '';

    $count = count($errorTypes);

    for($i=0; $i<$count; $i++)
    {
        $sql = NULL; //reset veriable
        $result = NULL;

        $errorID = $errorTypes[$i]['ETID'];
        $table = $errorTypes[$i]['Table'];

        $pk = getPrimaryKey($errorID);

        if($pk == "DistrFormID"){
            $end = 'a';
        }
        elseif($pk == 'HHVFormID'){
            $end = 'b';
        }
        elseif($pk == 'DistrID'){
            $end = 'c';
        }
        elseif($pk == 'HHVID'){
            $end = 'd';
        }

        echo "Table is $table, Primary Key Column is: $pk, errorType is: $errorID, end is: $end" . PHP_EOL;
    
        if($errorID == '1003a' || $errorID == '1003b'){ //Has HHID, SerialNumber and Link_PhotoID. Missing Link_Poster and PosterSig
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 3 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_PhotoID is NOT NULL AND
                    Link_Poster is NULL AND Link_PosterSig is NULL ) 
                    AND (Source_TableID is NULL OR (Lvl = 3 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 3;";
        }

        if($errorID == '1004a' || $errorID == '1004b'){ //Has HHID, SerialNUmber and Link_Poster. Missing Link_PhotoID, and PosterSig
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 3 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_Poster is NOT NULL AND
                    Link_PhotoID is NULL AND Link_PosterSig is NULL ) 
                    AND (Source_TableID is NULL OR (Lvl = 3 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 3;";
        }

        if($errorID == '1005a' || $errorID == '1005b'){ //Has HHID, SerialNumber, and Link_PosterSig. Missing Link_PhotoID and Poster
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 3 FROM $table
                    LEFT JOIN tbl_ErrorSource as es ON es.Source_TableID = $pk
                    WHERE ( (HHID is not NULL or HHID <> 0) AND SerialNumber is NOT NULL AND Link_posterSig is NOT NULL AND
                    Link_PhotoID is NULL and Link_Poster is NULL )
                    AND (Source_TableID is NULL OR (Lvl = 3 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 3;";
        }

        
         //Execute Query
         if($sql != NULL){
            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query $errorID failed on $table" .PHP_EOL;
                echo $sql .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            else{
                $numRows = mysqli_affected_rows($conn_da);
                echo "Query $errorID affected $numRows records" .PHP_EOL;
            }

            $sql = NULL; //reset
        }
    }
*/
        //mark_inCleaning($conn_da, 2);
/*
    //get Error Types from tbl_ErrorType where Severity = 3
    $sql = "SELECT ETID, `Table` FROM tbl_ErrorType WHERE Severity = 4"; 
    $result = execute_query($conn_da, $sql);

    $errorTypes = array();

    while($row = mysqli_fetch_assoc($result)){
        $errorTypes[] = $row;
    }

    //print_r($errorTypes);


    $pk = ''; //variable to hold name of column that is primary key. Ex DistrFormID or HHVFormID
    $end = '';

    $count = count($errorTypes);

    for($i=0; $i<$count; $i++)
    {
        $sql = NULL; //reset veriable
        $result = NULL;

        $errorID = $errorTypes[$i]['ETID'];
        $table = $errorTypes[$i]['Table'];

        $pk = getPrimaryKey($errorID);

        if($pk == "DistrFormID"){
            $end = 'a';
        }
        elseif($pk == 'HHVFormID'){
            $end = 'b';
        }
        elseif($pk == 'DistrID'){
            $end = 'c';
        }
        elseif($pk == 'HHVID'){
            $end = 'd';
        }

        echo "Table is $table, Primary Key Column is: $pk, errorType is: $errorID, end is: $end" . PHP_EOL;
*/
    /*
        //DATA MATCHING QUERIES WITHIN SAME TABLE
        //TODO: these queries are lvl 3 
        //NOTE: should only run on clean records
    
        if($errorID == '1101a' || $errorID == '1101b'){ //Multiple records with matching HHID and SerialNumber 
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                    SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                    FROM $table
                    WHERE inCleaning <> -2 and HHID is not NULL and SerialNumber is not NULL 
                    GROUP BY HHID, SerialNumber
                    HAVING COUNT($pk) > 1;

                    INSERT INTO tbl_ErrorSource (TypeID, Source_TableID) 
                    SELECT '$errorID', $pk 
                    FROM $table INNER JOIN
                        table2 t on $table.HHID = t.HHID and $table.SerialNumber = t.SerialNumber;
                    DROP TABLE IF EXISTS  table2;";
            echo $sql .PHP_EOL;
        }
    
    
        //Multiple records with matching HHID but different SerialNumber
        if($errorID == '1102a' || $errorID == '1102b'){ 
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                    SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                    FROM $table
                    WHERE inCleaning <> -1 and HHID is NOT NULL and SerialNumber is not NULL
                    GROUP BY HHID
                    HAVING COUNT(HHID) > 1 and count(distinct(SerialNumber)) > 1; -- = count(HHID)

                    INSERT INTO tbl_ErrorSource (TypeID, Source_TableID) 
                    SELECT '$errorID', $pk
                    FROM $table INNER JOIN
                        table2 t on $table.HHID = t.HHID;
                    DROP TABLE IF EXISTS  table2;";
            echo $sql .PHP_EOL;
        }

    
        //Multiple records with matching SerialNumber but different HHIDs
        if($errorID == '1103a' || $errorID == '1103b'){ 
            $sql =" CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                    SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                    FROM $table
                    WHERE inCleaning <> -1 and HHID is not NULL and SerialNumber is NOT NULL
                    GROUP BY SerialNumber 
                    HAVING COUNT(SerialNumber) > 1 and count(distinct(HHID)) > 1;

                    INSERT INTO tbl_ErrorSource (TypeID, Source_TableID) 
                    SELECT '$errorID', $pk
                    FROM $table INNER JOIN
                        table2 t on $table.SerialNumber = t.SerialNumber; 
                    DROP TABLE IF EXISTS  table2;";
            echo $sql .PHP_EOL;
        }
    */
/*
        //Execute Query
        if($sql != NULL){
            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query $errorID failed on $table" .PHP_EOL;
                echo $sql .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            else{
                $numRows = mysqli_affected_rows($conn_da);
                echo "Query $errorID affected $numRows records" .PHP_EOL;
            }

            $sql = NULL; //reset
        }
    }
*/
        //DATA MATCHING QUERIES ACROSS TABLES
    /*
        //This query compares records between Distr and HHVs
        //SerialNumber is found in HHVs, but not Distr table
        if($errorID == '1201b'){ 
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', HHVFormID FROM fd_HHVs h
                    LEFT OUTER JOIN fd_Distr as d ON d.SerialNumber = h.SerialNumber
                    WHERE d.SerialNumber is NULL AND h.SerialNumber is NOT NULL and h.HHID is NOT NULL
                    AND (h.`inCleaning_Lvl1-2` is NULL AND h.inCleaning_Lvl3 = 0)";
        }
    

        //Query compares records between Distr and HHVs table
        //Records that share serialNumbers between dist and HHV table, but associated HHIDs are completely different
        if($errorID == '1104a'){ 
            $sql = "SELECT DistrFormID, d.HHID as HHID1, d.SerialNumber as SN1, 
                        HHVFormID, h.HHID as HHID2, h.SerialNumber as SN2
                    FROM dbs1qpdncyglvh.fd_Distr d
                    LEFT JOIN fd_HHVs as h on h.SerialNumber = d.SerialNumber
                    WHERE h.HHID <> d.HHID and h.HHID is NOT NULL
                    GROUP BY d.HHID, h.HHID
                    HAVING count(d.HHID) > 1 and count(h.HHID) = count(d.HHID)
                    ORDER BY HHID1, HHID2;";
        }

        multi_query($conn_da, $sql, "Query for errorID $errorID failed");

    }

    //mark_inCleaning($conn_da, 3);
    */

    
