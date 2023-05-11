<?PHP  
    //require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database
    require('../dbconn.php');
    require('util.php');
    exit(); //pause data Cleaning script for now
  
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
        echo "Successful Connection!\n";
    }    
 */
    $table = 'fd_Distr';
    $DformID = '';
    $HformID = '';
    $DistrFormID = 'DistrFormID';
    $HHVFormID = -1;
    $queryNum = 0; 
    $formID = '';
    
    //uncomment to have same queries also run on fd_HHVs table
    $k = 0;
    while($k < 2)
    {
        echo "Table is $table" . PHP_EOL;
        echo "Form is $DistrFormID, $HHVFormID" .PHP_EOL;
     //query 1000
        $queryNum = 1000;
    
        //echo "Marking all records in fd_Distr where HHID, SerialNumber, and links are empty" .PHP_EOL;
        $sql = "UPDATE $table SET IgnoreRecord = -1, BadSubmit = -1 WHERE (HHID is null OR HHID = 0 ) and SerialNumber is null and Link_PhotoID is null and Link_Poster is null "
                . "and Link_PosterSig is null;";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1000 failed on $table" .PHP_EOL;
            echo $sql .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;

        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1000 affected $numRows records" .PHP_EOL;
    
    
        //query 1001
        //echo "Running Query 1001" .PHP_EOL;
        $queryNum = 1001;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            . " where ((HHID is null  OR HHID =0 ) and SerialNumber is null) and (Link_PhotoID is not null and Link_Poster is not null and Link_PosterSig is not null) "
            ."and IgnoreRecord <> -1 and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1001 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1001 affected $numRows records" .PHP_EOL;
    
        //query 1002
        $queryNum = 1002;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            . " where ((HHID is null  OR HHID =0 ) and SerialNumber is null) and (Link_PhotoID is null OR Link_Poster is null OR  Link_PosterSig is  null)  "
            ."and IgnoreRecord <> -1 and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1002 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1002 affected $numRows records" .PHP_EOL;
    
        //query 1003
        $queryNum = 1003;
        if($table == 'fd_Distr'){
            $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
                . " where ((HHID is not null and HHID <>0 ) and SerialNumber is not null) and (Link_PhotoID is  null OR Link_Poster is null OR Link_PosterSig is null)  "
                ."and IgnoreRecord <> -1 and inCleaning is NULL";
            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query #1003 failed on $table" .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            //echo $sql .PHP_EOL;
            $numRows = mysqli_affected_rows($conn_da);
            echo "Query 1003 affected $numRows records" .PHP_EOL;
        }
    
    
        //query 1004
        $queryNum = 1004;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            . " where (HHID is null  OR HHID =0 ) and SerialNumber is not null  and IgnoreRecord <> -1 and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1004 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1004 affected $numRows records" .PHP_EOL;

    
        //query 1005
        $queryNum = 1005;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            . " where (HHID is not null  and HHID <>0 ) and SerialNumber is null and IgnoreRecord <> -1 and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1005 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1005 affected $numRows records" .PHP_EOL;

    
        //query 1006
        $queryNum = 1006;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table"
            ." where IgnoreRecord <> -1 and ActivityDate is null and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1006 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1006 affected $numRows records" .PHP_EOL;
    
        //query 1007
        $queryNum = 1007;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            ."WHERE `IgnoreRecord` <> -1 and (ActivityDate is not null and  ActivityDate < '2020-01-01') "
            . " OR (SubmitDate is not null and ActivityDate > SubmitDate) and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        if(!$result){
            echo "Query #1007 failed on $table" .PHP_EOL;
            echo mysqli_error($conn_da);
        }
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1007 affected $numRows records" .PHP_EOL;

    /*
        //query 1008
        $queryNum = 1008;
        if($table == 'fd_Distr'){
            $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table d"
                ."left join g_Locations g ON g.PreviousID_DA = d.VillageID "
                ."left join sch_Distrs plan on plan.GID = g.ParentID and plan.DistrDate = d.ActivityDate " 
                ."WHERE `IgnoreRecord` <> -1 and plan.DistrID is null and ActivityDate <> '0000-00-00' and inCleaning is NULL";
            $result = mysqli_query($conn_da, $sql);
            if(!$result){
                echo "Query #1008 failed on $table" .PHP_EOL;
                echo mysqli_error($conn_da);
            }
            //echo $sql .PHP_EOL;
            $numRows = mysqli_affected_rows($conn_da);
            echo "Query 1008 affected $numRows records" .PHP_EOL;  
        }
    */
        //query 1009
        $queryNum = 1009;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            ."WHERE SerialNumber = NULL and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1009 affected $numRows records" .PHP_EOL;  
  
        //Query 1010
        $queryNum = 1010;
        $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`, `HHVFormID`, `QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT 0, $HHVFormID, $queryNum, $DistrFormID, HHID, Sup_DAID from $table "
            ."WHERE (SubmitDate = '0000-00-00' or SubmitDate is NULL) and inCleaning is NULL";
        $result = mysqli_query($conn_da, $sql);
        //echo $sql .PHP_EOL;
        $numRows = mysqli_affected_rows($conn_da);
        echo "Query 1010 affected $numRows records" .PHP_EOL;  
        

      //DATA MATCHING QUERIES

        if($DistrFormID == -1){
            $formID = 'HHVFormID';
        }
        else if ($HHVFormID == -1){
            $formID = 'DistrFormID';
        }

        echo "FormID is $formID" .PHP_EOL;
    
        //query 1101
        //$aSurveyDistr = array();
        $queryNum = '#1101';
        $sql = "SELECT HHID, $formID, count(*) as total,count(HHID), SerialNumber, count(SerialNumber), "
             ."Link_PhotoID, count(Link_PhotoID), Link_Poster, count(Link_Poster), Link_PosterSig, count(Link_PosterSig) FROM $table "  
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1101) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(HHID)>1 and count(HHID)=count(SerialNumber) and count(HHID)=count(Link_PhotoID) "
            ."and count(HHID)=count(Link_Poster) and count(HHID)=count(Link_PosterSig)";
        //echo $sql .PHP_EOL;
        
        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
    
        //unset($aSurveyDistr);
    
    
        //query 1102
        //$aSurveyDistr = array(); 
        $queryNum = '#1102';
        $sql = "SELECT HHID, $formID, count(*) as total,count(HHID), SerialNumber, count(SerialNumber), "
            ."Link_PhotoID, count(Link_PhotoID), Link_Poster, count(Link_Poster), Link_PosterSig, count(Link_PosterSig) FROM $table "   
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1102) "    
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(HHID)>1 and count(HHID)=count(SerialNumber) and count(Link_PhotoID)=1 and count(Link_Poster)=1 and count(Link_PosterSig)=1 ";

        //echo $sql .PHP_EOL;
        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr); 
    
        //query 1103
        //$aSurveyDistr = array(); 
        $queryNum = '#1103';
        $sql = "SELECT HHID, $formID, count(*) as total,count(HHID), SerialNumber, count(SerialNumber), "
        ."Link_PhotoID, count(Link_PhotoID), Link_Poster, count(Link_Poster), Link_PosterSig, count(Link_PosterSig) FROM $table "  
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1103) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(HHID)>1 and count(HHID)=count(SerialNumber) and (count(Link_PhotoID)=count(HHID) OR count(Link_Poster)=count(HHID) OR count(Link_PosterSig)=count(HHID) )";
        //echo $sql .PHP_EOL;   

        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr);
    
        //query 1104
        //$aSurveyDistr = array();
        $queryNum = '#1104';
        $sql = "SELECT HHID, $formID, count(*) as total,count(HHID), SerialNumber, count(SerialNumber), "
            ."Link_PhotoID, count(Link_PhotoID), Link_Poster, count(Link_Poster), Link_PosterSig, count(Link_PosterSig) FROM $table "   
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1104) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(SerialNumber)>1 and count(HHID)=1 and count(Link_PhotoID)=count(HHID) and count(Link_Poster)=count(HHID) and count(Link_PosterSig)=count(HHID)";
    
        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum); 
        //unset($aSurveyDistr);
    
        //query 1105
        //$aSurveyDistr = array();
        $queryNum = '#1105';
        $sql = "SELECT HHID, $formID, count(*) as total,count(HHID), SerialNumber, count(SerialNumber), "
        ."Link_PhotoID, count(Link_PhotoID), Link_Poster, count(Link_Poster), Link_PosterSig, count(Link_PosterSig) FROM $table "   
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1105) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(SerialNumber)>1 and count(HHID)=1 and (count(Link_PhotoID)=count(SerialNumber) "
            ."OR count(Link_Poster)=count(SerialNumber) OR count(Link_PosterSig)=count(SerialNumber) )";

        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr);
    
        //query 1106
        //$aSurveyDistr = array();
        $queryNum = '#1106';
        $sql = "SELECT HHID, $formID,count(*) as total,count(HHID),count(SerialNumber),count(Link_PhotoID),count(Link_Poster),count(Link_PosterSig)  FROM $table "  
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1106) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(HHID)>1 and count(SerialNumber)=1 and count(Link_PhotoID)=count(SerialNumber) "
            ."and count(Link_Poster)=count(SerialNumber) and count(Link_PosterSig)=count(SerialNumber)";

        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr);
    
        //query 1107
        //$aSurveyDistr = array();
        $queryNum = '#1107';
        $sql = "SELECT HHID,$formID,count(*) as total,count(HHID),count(SerialNumber),count(Link_PhotoID),count(Link_Poster),count(Link_PosterSig) FROM $table "  
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1107) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."having count(HHID)>1 and count(SerialNumber)=1 and (count(Link_PhotoID)=count(HHID) "
            ."OR count(Link_Poster)=count(HHID) OR count(Link_PosterSig)=count(HHID) )";

        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr);
    
        //query 1108
        //$aSurveyDistr = array();
        $queryNum = '#1108';
        $sql = "SELECT HHID,$formID,count(*) as total,count(HHID),count(SerialNumber),count(Link_PhotoID),count(Link_Poster),count(Link_PosterSig) FROM $table "  
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1108) "
            ."WHERE IgnoreRecord <> -1 GROUP by `HHID`,`SerialNumber`,`Link_PhotoID`,`Link_Poster`,`Link_PosterSig` "
            ."HAVING count(HHID)=1 and count(SerialNumber)=1 and  (count(Link_PhotoID)>1 "
            ."OR count(Link_Poster)>1 OR count(Link_PosterSig)>1)";

        dataMatching_Query($conn_da, $sql, $table, $formID, $HHVFormID, $DistrFormID, $queryNum);
        //unset($aSurveyDistr); 

        //query 1109
        $aSurveyDistr = array();
        
        //Query excludes records already in SurveyCleaning for this QNum
        $sql = "SELECT $formID, HHID, count(*) as total, count(HHID), SerialNumber, count(SerialNumber) FROM $table "
            //."WHERE $formID NOT in ( SELECT $formID FROM tbl_SurveyCleaning SC WHERE SC.QNum = 1109) "
            ."WHERE IgnoreRecord <> -1  GROUP by HHID, SerialNumber HAVING count(HHID)>1 and count(HHID)=count(SerialNumber);";
        $result = mysqli_query($conn_da, $sql);
        if(!$result)
        {
            echo "Query 1109 failed on $table". PHP_EOL;
            echo mysqli_error($conn_da);
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $aSurveyDistr[] = $row;
        }
    
        if (!empty($aSurveyDistr)) {
            $count = count($aSurveyDistr);
            for($i = 0; $i<$count; $i++)
            {          
                $sql = "INSERT INTO `tbl_SurveyCleaning`( `GroupID`,  HHVFormID,`QNum`, `DistrFormID`, `HHID`, `DAID`) SELECT " . ($i + 1) . ", $HHVFormID, 1109, $DistrFormID, HHID, Sup_DAID "
                        . "from $table where HHID =" . $aSurveyDistr[$i]['HHID'] . " and SerialNumber = '". $aSurveyDistr[$i]['SerialNumber'] ."' and IgnoreRecord <> -1";
                $result = mysqli_query($conn_da, $sql);
                if(!$result)
                {
                    echo "Insert for query 1109 failed on $table" .PHP_EOL;
                    echo $sql .PHP_EOL;
                    echo mysqli_error($conn_da);
                } 
            }
        }
        echo 'QUERY #1109 effected ' . count($aSurveyDistr) . ' record(s)', PHP_EOL;
        unset($aSurveyDistr); 


         //Query to mark records that have been put in surveycleaning table. 
        $sql = "Update $table Set inCleaning = -1 WHERE Exists ( 
                        SELECT DISTINCT($formID)
                        FROM dbthezxpnokgxv.tbl_SurveyCleaning SC
                        Group by SC.$formID);";
        //echo $sql . PHP_EOL;
        $result = mysqli_query($conn_da, $sql);
        if(!$result)
        {
            echo "Query to mark inCleaning = -1 failed on $table". PHP_EOL;
            echo mysqli_error($conn_da);
        }
        $numRows = mysqli_affected_rows($conn_da);
        echo "$numRows records marked as in-cleaning" .PHP_EOL;
    
        //Query to mark records that have been checked already and are clean
        $sql = "Update $table Set inCleaning = 0 WHERE inCleaning is NULL;";
        $result = mysqli_query($conn_da, $sql);
        if(!$result)
        {
            echo "Query to mark inCleaning = 0 failed on $table". PHP_EOL;
            echo mysqli_error($conn_da);
        }
        $numRows = mysqli_affected_rows($conn_da);
        echo "$numRows records marked as clean" .PHP_EOL;   

        //uncomment to have queries run on fd_HHVs table
        $table = 'fd_HHVs';
        $DistrFormID = -1;
        $HHVFormID = 'HHVFormID';
        $k++;
    }

    //delete duplicates in SurveyCleaning
    echo "calling procedure to delete duplicate".PHP_EOL;
    $result = mysqli_Query($conn_da, "CALL SC_deleteDuplicates()");
    if(!$result)
    {
        echo "Failed to call SC_deleteDuplicates". PHP_EOL;
        echo mysqli_error($conn_da);
    }
    $numRows = mysqli_affected_rows($conn_da);
    echo "$numRows records deleted" .PHP_EOL;   
