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

     //get Error Types from tbl_ErrorType where Severity = 3
     //TODO: Get rid of ETID LIKE... in WHERE
     $sql = "SELECT ETID, `Table` FROM tbl_ErrorType WHERE Severity = 4"; // and ETID LIKE '%a'"; 
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
 
    /*
         //DATA MATCHING QUERIES WITHIN SAME TABLE
         //These queries are lvl 4
     
         if($errorID == '1101a' || $errorID == '1101b'){ //Multiple records with matching HHID and SerialNumber 
             $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                     SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                     FROM $table
                     LEFT JOIN tbl_ErrorSource ON Source_TableID = $pk
                     WHERE (Source_TableID is NULL or (TypeID LIKE '%$end' and Lvl = 4 and TypeID <> '$errorID') ) and HHID is not NULL and SerialNumber is not NULL
                     GROUP BY HHID, SerialNumber
                     HAVING COUNT($pk) > 1;
 
                     INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 4
                     FROM $table INNER JOIN table2 t on $table.HHID = t.HHID and $table.SerialNumber = t.SerialNumber;
                     DROP TABLE IF EXISTS  table2;";
             //echo $sql .PHP_EOL;
         }
     
     
         //Multiple records with matching HHID but different SerialNumber
         if($errorID == '1102a' || $errorID == '1102b'){ 
             $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                     SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                     FROM $table
                     LEFT JOIN tbl_ErrorSource ON Source_TableID = $pk
                     WHERE (Source_TableID is NULL or (TypeID LIKE '%$end' and Lvl = 4 and TypeID <> '$errorID') ) and HHID is NOT NULL and SerialNumber is not NULL
                     GROUP BY HHID
                     HAVING COUNT(HHID) > 1 and count(distinct(SerialNumber)) > 1; -- = count(HHID)
 
                     INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 4
                     FROM $table INNER JOIN table2 t on $table.HHID = t.HHID;
                     DROP TABLE IF EXISTS  table2;";
             //echo $sql .PHP_EOL;
         }
 
         //Multiple records with matching SerialNumber but different HHIDs
         if($errorID == '1103a' || $errorID == '1103b'){ 
             $sql =" CREATE TEMPORARY TABLE IF NOT EXISTS table2 AS
                     SELECT HHID, SerialNumber,  COUNT($pk) as CNT
                     FROM $table
                     LEFT JOIN tbl_ErrorSource ON Source_TableID = $pk
                     WHERE (Source_TableID is NULL or (TypeID LIKE '%$end' and Lvl = 4 and TypeID <> '$errorID') ) and HHID is not NULL and SerialNumber is NOT NULL
                     GROUP BY SerialNumber 
                     HAVING COUNT(SerialNumber) > 1 and count(distinct(HHID)) > 1;
 
                     INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', $pk, 4
                     FROM $table INNER JOIN table2 t on $table.SerialNumber = t.SerialNumber; 
                     DROP TABLE IF EXISTS  table2;";
             //echo $sql .PHP_EOL;
         }
    
         //Execute Query
        multi_query($conn_da, $sql, "Query for errorID $errorID failed on $table");
        //$numRows = mysqli_affected_rows($conn_da);
       // echo "Query $errorID affected $numRows records" .PHP_EOL;
        $sql = NULL; //reset
         
     }
    */

        //DATA MATCHING QUERIES ACROSS TABLES

        //This query compares records between Distr and HHVs
        //SerialNumber is found in HHVs, but not Distr table
        if($errorID == '1201b'){ 
            $sql = "INSERT INTO tbl_ErrorSource (`TypeID`, `Source_TableID`, `Lvl`) SELECT '$errorID', HHVFormID, 5 FROM fd_HHVs h
                    LEFT OUTER JOIN fd_Distr as d ON d.SerialNumber = h.SerialNumber
                    WHERE (d.SerialNumber is NULL AND h.SerialNumber is NOT NULL and h.HHID is NOT NULL and h.HHID <> 0) AND
                    (Source_TableID is NULL OR (Lvl = 5 and TypeID <> '$errorID' and TypeID LIKE '%$end') )
                    ON DUPLICATE KEY UPDATE `TypeID` = '$errorID', `Source_TableID` = $pk, `Lvl` = 5;";
                
        }


        //Query compares records between Distr and HHVs table
        //Records that share serialNumbers between dist and HHV table, but associated HHIDs are completely different
        if($errorID == '1202a'){ 
            $sql = "SELECT DistrFormID, d.HHID as HHID1, d.SerialNumber as SN1, 
                        HHVFormID, h.HHID as HHID2, h.SerialNumber as SN2
                    FROM dbs1qpdncyglvh.fd_Distr d
                    LEFT JOIN fd_HHVs as h on h.SerialNumber = d.SerialNumber
                    WHERE h.HHID <> d.HHID and h.HHID is NOT NULL
                    GROUP BY d.HHID, h.HHID
                    HAVING count(d.HHID) > 1 and count(h.HHID) = count(d.HHID)
                    ORDER BY HHID1, HHID2;";
        }

        //Execute Query
        multi_query($conn_da, $sql, "Query for errorID $errorID failed on $table");

        $sql = NULL; //reset
    }
         
     
 

 
  
     
     

 