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
        echo "Successful Connection!" .PHP_EOL;
    }

    //get Error Correction Types from tbl_ErrorCorretionTypes
    $sql = "SELECT ECID, `Table`, `Reason4Change` FROM tbl_ErrorCorrectionTypes;"; // WHERE ECID = '2002b';";

    $result = execute_query($conn_da, $sql);

    $ECTypes = array();

    while($row = mysqli_fetch_assoc($result))
    {
        $ECTypes[] = $row;
    }

    //print_r($ECTypes);

    $pk = ''; //variable to hold name of column that is primary key. Ex DistrFormID or HHVFormID

    $count = count($ECTypes);
    $sql = NULL; //reset veriable
    $result = NULL;
    for($i=0; $i<$count; $i++)
    {
        $ECID = $ECTypes[$i]['ECID'];
        $table = $ECTypes[$i]['Table'];
        $change = $ECTypes[$i]['Reason4Change'];
        
        //Figure out what the name of the primary key column is. Ex DistrFormID or HHVFormID
        if(substr($ECID, -1) == 'a'){
            $pk = 'DistrFormID';
        }
        elseif(substr($ECID, -1) == 'b'){
            $pk = 'HHVFormID';
        }
        elseif(substr($ECID, -1) == 'c'){
            $pk = 'DistrID';
        }
        elseif(substr($ECID, -1) == 'd'){
            $pk = 'HHVID';
        }
        else{ 
            $pk = '';
        }
    /*
        //2002b - HHV Record has HHID, but no SerialNumber. Check DistrRecord with same HHID to get serialnumber BUT
        //Exclude records flagged as 1102a - these Distr records has multiple Serials tied to 1 HHID
        //2002a - Distr Record has HHID, but no SerialNumber. Check HHV records same HHID to get serialnumber BUT
        //Exclude records flagged as 1102b - these HHVs records has multiple Serials tied to 1 HHID
        if($ECID == '2002b' || $ECID == '2002a'){ 
            echo "ECID is: $ECID, Reason is $change" .PHP_EOL;
            if($ECID == '2002a'){
                $typeID = '1001a'; //Distr records that have HHID, missing SerialNumber
                $table2 = 'fd_HHVs';
                $pk2 = 'HHVFormID';
                $typeID2 = '1102b'; //HHV records that have multiple SerialNUmber tied to 1 HHID
            }
            elseif($ECID == '2002b'){
                $typeID = '1001b'; ///HHV records that have HHID, Missing SerialNumber
                $table2 = 'fd_Distr';
                $pk2 = 'DistrFormID';
                $typeID2 = '1102a'; //Distr records that have multiple SerialNumbers tied to 1 HHID
            }
            
            //Create temp table that gets the records with missing SerialNUmbers
            //Also join the Distr or HHV table on the HHID to get the missing SerialNumber
            //Insert into ErrorCorrectionSource table
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS updateSerial AS
                    SELECT TypeID, Source_TableID, $pk, HHID, SerialNumber
                    FROM tbl_ErrorSource
                    INNER JOIN $table on $pk = Source_TableID
                    WHERE TypeID = '$typeID';
                    
                    INSERT INTO tbl_ErrorCorrectionSource (`ErrorCorrection_Type`, `Source_TableID`, `New_Val`, `Old_Val`, `Reason4Change`, `User`)
                    SELECT '$ECID', us.$pk, $table2.SerialNumber, us.SerialNumber, '$change', 'ErrorCorrection Script'
                    FROM $table2 
                    INNER JOIN updateSerial as us ON us.HHID = $table2.HHID
                    LEFT OUTER JOIN tbl_ErrorSource T ON T.Source_TableID = $pk2 and T.TypeID = '$typeID2'
                    WHERE $table2.SerialNumber is not NULL and us.SerialNumber is null and T.Source_TableID is null;
                   
                    UPDATE $table
                    INNER JOIN $table2 on $table.HHID = $table2.HHID
                    INNER JOIN updateSerial as us ON us.HHID = $table2.HHID
                    LEFT OUTER JOIN tbl_ErrorSource T ON T.Source_TableID = $pk2 and T.TypeID = '$typeID2'
                    SET $table.SerialNumber = $table2.SerialNumber
                    WHERE $table2.SerialNumber is not NULL and $table.SerialNumber is null and T.Source_TableID is null;
                    DROP TABLE IF EXISTS updateSerial;";
                

            echo $sql ."\n". PHP_EOL;
            
           // multi_query($conn_da, $sql, "Query $ECID failed");
            if(mysqli_multi_query($conn_da, $sql)){
                do{
                    if (0 !== mysqli_errno($conn_da))
                    {
                        echo "Multi query failed". PHP_EOL; 
                        echo mysqli_error($conn_da);
                        break;
                    }
                }while(mysqli_next_result($conn_da)); 
            }
        
        }
    */
    
        //2003b - HHV Record has SerialNumber, but no HHID. Check DistrRecord with same SerialNumber to get missing HHID BUT
        //Exclude records flagged as 1103a - these Distr records has multiple HHIDs Tied to 1 SerialNumber
        //2003a - Distr Record has SerialNumber, missing HHID. Check HHV records with same SerialNumber to get missing HHID BUT
        //Exclude records fladded as 1103b - these HHV records have multiple HHIDs tied to 1 SerialNumber
        if($ECID == '2003b' || $ECID == '2003a'){ 
            echo "ECID is: $ECID, Reason is $change" .PHP_EOL;
            if($ECID == '2003a'){
                $typeID = '1002a'; //Distr records that have SerialNumber, missing HHID
                $table2 = 'fd_HHVs';
                $pk2 = 'HHVFormID';
                $typeID2 = '1103b'; //HHV records that have multiple HHIDS tied to 1 SerialNumber
            }
            elseif($ECID == '2003b'){
                $typeID = '1002b'; ///HHV records that have SerialNumber, missing HHID
                $table2 = 'fd_Distr';
                $pk2 = 'DistrFormID';
                $typeID2 = '1103a'; //Distr records that have multiple HHIDs tied to 1 SerialNumbers 
            }
            
            $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS updateHHID AS
                    SELECT TypeID, Source_TableID, $pk, HHID, SerialNumber
                    FROM tbl_ErrorSource
                    INNER JOIN $table on $pk = Source_TableID
                    WHERE TypeID = '$typeID';

                    INSERT INTO tbl_ErrorCorrectionSource (`ErrorCorrection_Type`, `Source_TableID`, `New_Val`, `Old_Val`, `Reason4Change`, `User`)
                    SELECT '$ECID', uh.$pk, $table2.HHID, uh.HHID, '$change', 'ErrorCorrection Script'
                    FROM $table2
                    LEFT OUTER JOIN tbl_ErrorSource T ON T.Source_TableID = $pk2 and T.TypeID = '$typeID2'
                    INNER JOIN updateHHID as uh ON uh.SerialNumber = $table2.SerialNumber
                    WHERE $table2.HHID is not NULL and uh.HHID is null and T.Source_TableID is null;
                    -- DROP TABLE IF EXISTS updateHHID;
                
                    UPDATE $table 
                    INNER JOIN $table2 ON $table2.SerialNumber = $table.SerialNumber
                    INNER JOIN updateHHID as uh ON uh.SerialNumber = $table2.SerialNumber
                    LEFT OUTER JOIN tbl_ErrorSource T ON T.Source_TableID = $pk2 and T.TypeID = '$typeID2'
                    SET $table.HHID = $table2.HHID
                    WHERE $table2.HHID is not NULL and $table.HHID is null and T.Source_TableID is null;

                    DROP TABLE IF EXISTS updateHHID;";
                
            //echo $sql ."\n". PHP_EOL;
        
            if(mysqli_multi_query($conn_da, $sql)){
                do{
                    if (0 !== mysqli_errno($conn_da))
                    {
                        echo "Multi query failed". PHP_EOL; 
                        echo mysqli_error($conn_da);
                        break;
                    }
                }while(mysqli_next_result($conn_da));

                $numRows = mysqli_affected_rows($conn_da);
                echo "Query affected $numRows records\n" .PHP_EOL;
            }
        
        }
    

    /*
        //For each group of records that have a matching HHID, different SieralNumbers (Record where TypeID = 1102)
        //Search for HHID in HHVs table – if corresponding HHID is different, change Distr record
        if($errorID == '2005a'){ //TODO: add this error to ErroreCorrectionTypes
            //INSERT INTO tbl_ErrorCorrectionSource (`ErrorCorrection_Type`, `Source_TableID`, `New_Val`, `Old_Val`, `Reason4Change`, `User`)
            //TODO: Still need to fine-tune this query
            $sql = "SELECT TypeID, DistrFormID, d.HHID as HHID1, d.SerialNumber as SN1, 
                    HHVFormID, h.HHID as HHID2, h.SerialNumber as SN2
                    FROM dbs1qpdncyglvh.fd_Distr d
                    INNER JOIN tbl_ErrorSource es ON es.Source_TableID = DistrFormID
                    INNER JOIN fd_HHVs as h ON h.SerialNumber = d.SerialNumber
                    WHERE TypeID ='1102a'and d.HHID <> h.HHID  and h.HHID is not NULL
                    -- Group by d.SerialNumber having count(h.HHID) = 1 
                    ORDER By HHID1, SN1, SN2;"; //IMPORTANT: order by is necessary
            $result = execute_query($conn_da, $sql);
            
            $matching = array();
            while($row = mysqli_fetch_assoc($result)){
                $oldHHID = $row['HHID1'];
                $newHHID = $row['HHID2'];
                $ID = $row['DistrFormID'];

                $sql2 = "INSERT INTO tbl_ErrorCorrectionSource (`ErrorCorrection_Type`, `Source_TableID`, `New_Val`, `Old_Val`, `Reason4Change`, `User`)
                        VALUES ('$errorID', $ID, '$newHHID', '$oldHHID', '$change', 'ErrorCorrection Script')";
                
                $reulst2 = execute_Query($conn_da, $sql);
            }
        }
    */
    }