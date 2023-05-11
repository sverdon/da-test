<?php
    require '../dbconn.php';
    require_once 'util.php';

/* 
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
*/


    //Find records in HHV where the Barcode/SerialNumber is not in the Distr table
    $sql = " SELECT SystemID, SubmitDate, ActivityDate, HHID, SerialNumber, Sup_DAID, VillageID, FormID FROM fd_HHVs h "
          //."Respondent_FirstName, Respondent_LastName, Phone, Link_PhotoID, Link_PhotoAgreement, Link_PhotoSig, Link_FormResponse, FormID FROM fd_HHVs h "
          ."WHERE h.SerialNumber not in (SELECT DISTINCT(SerialNumber) FROM fd_Distr);";

    $result = execute_query($conn_da, $sql);
    // $result = mysqli_query($conn_da, $sql);
    // if(!$result){
    //     echo $sql . PHP_EOL;
    //     echo mysqli_error($conn_da);
    // }

    $HHIDs = array();
     //save query results into array
     while ($row = mysqli_fetch_row($result)) {
        $HHIDs[] = $row;
    }

    //print_r($HHIDs);

    //For each result
    if (!empty($HHIDs)) {
        $count = count($HHIDs);
        for($i = 0; $i<$count; $i++)
        {                 
            //print_r($HHIDs[$i]);
            //Get array values into a string
            $values = implode("','",$HHIDs[$i]);
            //echo $values .PHP_EOL;

            //insert the HHID and other info into Distr table
            $sql = "INSERT INTO fd_Distr(SystemID, SubmitDate, ActivityDate, HHID, SerialNumber, Sup_DAID, VillageID, FormID) VALUES ('$values');";
            $result = execute_query($conn_da, $sql);
            // $result = mysqli_query($conn_da, $sql);
            // if(!$result){
            //     echo "Failed to insert into Distr table" .PHP_EOL;
            //     echo $sql . PHP_EOL;
            //     echo mysqli_error($conn_da);
            // }
        }
    }

    unset($HHIDs); //clear array

    //Find records in Distr that have the same HHID but different barcodes
    $sql = "SELECT DistrFormID, HHID, SerialNumber FROM fd_Distr "
          ."Group BY HHID HAVING count(HHID) > 1 and count(DISTINCT SerialNumber) > 1;";
    $result = execute_query($conn_da, $sql);
    
    // $result = mysqli_query($conn_da, $sql);
    // if(!$result){
    //     echo $sql . PHP_EOL;
    //     echo mysqli_error($conn_da);
    // }

    
     //save query results into array
     $HHIDs = array();
     while ($row = mysqli_fetch_assoc($result)) {
        $HHIDs[] = $row;
    }

    //print_r($HHIDs);

     //For each result - each HHID that has multiple SerialNumbers tied to it
    if (!empty($HHIDs)) {
        $count = count($HHIDs);
        for($i = 0; $i<$count; $i++)
        {          
            $HHID = $HHIDs[$i]['HHID'];
            //echo "HHID is $HHID" .PHP_EOL;
            //$SerialNumber = $HHIDs[$i]['SerialNumber'];

            //Find all records in the Distr table that has that HHID 
            //IMPORTANT to have the "order by SerialNumber"
            $sql = "SELECT DistrFormID, HHID, SerialNumber FROM fd_Distr WHERE HHID = $HHID ORDER BY SerialNumber;";
            $result = execute_query($conn_da, $sql);
            // $result = mysqli_query($conn_da, $sql);
            // if(!$result){
            //     echo $sql . PHP_EOL;
            //     echo mysqli_error($conn_da);
            // }
        
            //Save results
            $distrHHID = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $distrHHID [] = $row;
            }

            //print_r($distrHHID);

            //for each SerialNumber search in HHV
            if (!empty($distrHHID)) {
                $count2 = count($distrHHID);
                $condition = "";
                for($k = 1; $k<$count2; $k++){ // start with the 2nd one
                    $condition .= " or SerialNumber = '" .$distrHHID[$k]['SerialNumber'] ."'";
                }

                 //IMPORTANT to have the "order by SerialNumber"
                $sql = "SELECT HHVFormID, HHID, SerialNumber FROM fd_HHVs WHERE SerialNumber = '" .$distrHHID[0]['SerialNumber'] ."'" . $condition ." ORDER BY SerialNUmber;";
                //echo $sql .PHP_EOL;
                $result = execute_query($conn_da, $sql);
                // $result = mysqli_query($conn_da, $sql);
                // if(!$result){
                //     echo $sql . PHP_EOL;
                //     echo mysqli_error($conn_da);
                // }

                //Save results
                $HHV_HHID = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $HHV_HHID [] = $row;
                }

                //print_r($HHV_HHID);

                $differences = array(); //array to store the differenes

                //Compare the results from HHV table to what is in Distr table
                if (!empty($HHV_HHID)) {
                    $count3 = count($HHV_HHID);
                   
                    //$m =0;
                    //for($j = 0; $j<$count2; $j++){ 
                    $j = 0;
                    while($j < $count2){ //$count2 is length of array of HHIDs/Barcodes from Dist table
                        $distHHID = $distrHHID[$j]['HHID'];
                        $distNum = $distrHHID[$j]['SerialNumber'];

                        //echo "HHID is $distHHID, SerialNumber is $distNum, j is $j" .PHP_EOL;
                        //for($h = 0; $h<$count3; $h++){ 
                        $h = 0;

                        while($h <$count3 && $j < $count2){
                            $hhvHHID = $HHV_HHID[$h]['HHID'];
                            $hhvNum = $HHV_HHID[$h]['SerialNumber'];

                            //echo "HHID is $hhvHHID, SerialNumber is $hhvNum, h is $h" . PHP_EOL;

                            if( ($distHHID == $hhvHHID) && ($distNum == $hhvNum)){ //If HHID/Serial match
                                $j++;
                               // echo "Incremented j, now HHID is $hhvHHID, SerialNumber is $hhvNum, h is $h" . PHP_EOL;
 
                            }
                            else if ( ($distHHID != $hhvHHID) && ($distNum == $hhvNum)){ //barcodes match, but not HHID
                                array_push($differences, array('DistFormID' => $distrHHID[$j]['DistrFormID'], 'OldHHID' => $distHHID, 'NewHHID' => $hhvHHID, 'SerialNumber'=> $hhvNum));
                                $j++;
                                //echo "added to differences array. j is now $j, HHID is $hhvHHID, serialNumber is $hhvNum". PHP_EOL;
                            }
                            else{
                                $h++;
                                continue;
                            }

                            if($j < $count2){ 
                                $distHHID = $distrHHID[$j]['HHID'];
                                $distNum = $distrHHID[$j]['SerialNumber'];
                            }
                            $h++;
                        }
                        $j++;
                        if($j < $count2){ 
                            $distHHID = $distrHHID[$j]['HHID'];
                            $distNum = $distrHHID[$j]['SerialNumber'];
                        }
                    }
                    //print_r($differences);
                
                
                    //insert corrected values into Distr
                    $count4 = count($differences);
                    for($n=0; $n<$count4; $n++)
                    {
                        $oldHHID = $differences[$n]['OldHHID'];
                        $newHHID = $differences[$n]['NewHHID'];
                        $ID = $differences[$n]['DistFormID'];
                        if($newHHID != 0 && $newHHID != NULL){
                            $sql = "UPDATE fd_Distr SET HHID = $newHHID WHERE DistrFormID = $ID";
                            $result = execute_query($conn_da, $sql);
                            // $result =  mysqli_query($conn_da, $sql);
                            // if(!$result){
                            //     echo $sql . PHP_EOL;
                            //     echo mysqli_error($conn_da);
                            // }

                            //get current date/time to insert
                            date_default_timezone_set('America/Los_Angeles'); //set timezone to PST
                            $date = date('Y-m-d'); //H:i:s');
                            $sql = "INSERT INTO tbl_AuditTrail (DistrFormID, HHVFormID, FieldName, OldVal, NewVal, QueryNum, Reason4Change, ChangeDate) "
                                ." VALUES($ID, -1, 'HHID', $oldHHID, $newHHID, 0, 'Update HHID', '$date');";
                            $result = execute_query($conn_da, $sql);
                            // $result =  mysqli_query($conn_da, $sql);
                            // if(!$result){
                            //     echo $sql . PHP_EOL;
                            //     echo mysqli_error($conn_da);
                            // }
                        }
                    }

                    //HHV table had the same HHID/Barcode combo that was listed in the Distr table
                    //means same number of rows in each each distr and HHV array, and no values in $difference array
                    $count2 = count($distrHHID);
                    $count3 = count($HHV_HHID);
                    if($count2 == $count3 && !$differences){
                        for($j = 0; $j<$count2; $j++){ //for each result from the Distr table
                            $formID = $distrHHID[$j]['DistrFormID'];
                            $HHID = $distrHHID[$j]['HHID'];
                            if(!$HHID){ $HHID = 0;}
                            $sql = "INSERT INTO tbl_SurveyCleaning (GroupID, HHVFormID, QNum, DistrFormID, HHID) "
                                  ."VALUES( $j, -1, 1110, $formID, $HHID);";
                            $result = execute_query($conn_da, $sql);   
                           // $result =  mysqli_query($conn_da, $sql);
                           // if(!$result){
                           //     echo $sql . PHP_EOL;
                          //     echo mysqli_error($conn_da);
                           // }

                            $sql = "UPDATE fd_Distr SET inCleaning = -1 WHERE DistrFormID = $formID";
                            $result = execute_query($conn_da, $sql);
                            //$result =  mysqli_query($conn_da, $sql);
                            //if(!$result){
                            //    echo $sql . PHP_EOL;
                            //    echo mysqli_error($conn_da);
                            //}
                        }
                        for($h = 0; $h<$count3; $h++){ //for each result from the HHV table
                            $formID = $HHV_HHID[$h]['HHVFormID'];
                            $HHID = $HHV_HHID[$h]['HHID'];
                            if(!$HHID){ $HHID = 0;}
                            $sql = "INSERT INTO tbl_SurveyCleaning (GroupID, HHVFormID, QNum, DistrFormID, HHID) "
                                  ."VALUES( $h, $formID, 1110, -1, $HHID);";
                            $result = execute_query($conn_da, $sql);
                            //$result =  mysqli_query($conn_da, $sql);
                            //if(!$result){
                            //    echo $sql . PHP_EOL;
                            //    echo mysqli_error($conn_da);
                            //}
                            $sql = "UPDATE fd_HHVs SET inCleaning = -1 WHERE HHVFormID = $formID";
                            $result = execute_query($conn_da, $sql);
                            //$result =  mysqli_query($conn_da, $sql);
                            //if(!$result){
                            //   echo $sql . PHP_EOL;
                            //    echo mysqli_error($conn_da);
                            //}
                        }

                    }
                
                }
            } 

            unset($distrHHID);
            unset($HHV_HHID);
            unset($differences);
        }
    }
     

