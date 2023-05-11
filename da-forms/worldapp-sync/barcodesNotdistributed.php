<?php
    require '../dbconn.php';
    require_once 'util.php';

    echo "Running barcodesNotdistributed script" .PHP_EOL;
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
        echo "Successful Connection!" .PHP_EOL;
    }
*/
    $sql = "TRUNCATE r_BND";
    $result = execute_query($conn_da, $sql);

    //Get records from LogTransBC table where barcode is not present in fd_Distr
    $sql = "SELECT TID, Barcode FROM tbl_LogTransBCs bc
            WHERE Not EXISTS
                (Select SerialNumber FROM fd_Distr d
                WHERE d.SerialNumber = bc.Barcode 
                AND d.SerialNumber is not NULL);";
                //LIMIT 5;";

            // SELECT TID, Barcode 
            // FROM tbl_LogTransBCs b
            // LEFT OUTER JOIN fd_Distr d ON d.SerialNumber = b.Barcode
            // WHERE SerialNumber is NULL

            //NOTE:OR ALTERNATIVE

            // CREATE TEMPORARY table barcodes as
            // SELECT distinct d.SerialNumber as SerialNumber
            // FROM fd_Distr d
            //     INNER JOIN tbl_LogTransBCs as b ON b.Barcode = d.SerialNumber
            // WHERE d.SerialNumber is not null;

            // ALTER TABLE `barcodes` ADD INDEX `barcode_index` (`SerialNumber`);

            // SELECT TID, bc.Barcode
            // from tbl_LogTransBCs bc left outer join
            //     barcodes b on b.SerialNumber = bc.barcode
            // where b.SerialNumber is null;
            // Drop Table if Exists barcodes;

    $result = execute_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result))
    {
        $TID = $row['TID'];
        $barcode = $row['Barcode'];
        //echo "TID is: $TID, Barcode is: $barcode".PHP_EOL;

        //For each record, Get transDate using TID
        if($TID != NULL){
            $sql = "SELECT Trans_Date FROM tbl_LogTransOut WHERE TID = $TID";
            $result2 = execute_query($conn_da, $sql);
            while($row2 = mysqli_fetch_assoc($result2))
            {
                $transDate = $row2['Trans_Date'];
                //echo "TransDate is: $transDate".PHP_EOL;
            }

            //For each record, get all the cellIDs associated with that TID
            $sql = "SELECT CellID_Dest FROM tbl_LogTransOutNin WHERE TID = $TID";
            $result3 = execute_query($conn_da, $sql);
            //$rowCount = mysqli_num_rows($result3);
            //echo "$rowCount rows returned for CellID in LogTransOutNin WHERE TID = $TID" .PHP_EOL;

            $k = 0; //used to count how many cellID_Dest there are for particular TID
            while($row3 = mysqli_fetch_assoc($result3))
            {
                //for each cellID returned, get all parent locations
                $cellID = $row3['CellID_Dest'];
                //echo "CellID is $cellID".PHP_EOL;

                $regions = get_parentGIDs($conn_da, $cellID); //$country, $boundary1, $boundary2, $boundary3, $boundary4, $boundary5, $boundary6, $boundary7);
                $Region = ""; //empty string
                
                $Region = implode('-', $regions);
            /*
                $items = count($regions);
                $i = 0;
            
                foreach($regions as $key => $value)
                {
                    $next = next($regions);
                    //echo $next .PHP_EOL;
                    if( ($value != NULL) && ($next != NULL)){
                        $Region .= $value ."-";
                    }
                    elseif( ($value != NULL) && ($next == NULL) ){
                        $Region .= $value;
                    }
                }
            */

            /*
                if($Region != null){
                    echo $Region .PHP_EOL;
                }
            */
                //want to insert barcode and transdate even if no region info - means there was no cellID given the TID in LogTransOutNin
                if($k == 0){ //first region
                    $sql = "INSERT INTO r_BND (`Barcode`, `TransportDate`, `Region1`) VALUES ('$barcode', '$transDate', '$Region')";
                    //echo $sql .PHP_EOL;
                    $result5 = execute_query($conn_da, $sql);

                    //Get BNDID of last inserted row
                    $ID = mysqli_insert_id($conn_da);
                }

                if($Region != null){
                    if($k == 1){ //2nd region
                        $sql = "UPDATE r_BND SET `Region2` = '$Region' WHERE `BNDID` = $ID"; //`Barcode` = '$barcode'";
                        //echo $sql .PHP_EOL;
                        $result5 = execute_query($conn_da, $sql);
                    }
                    elseif($k == 2){ //3rd region
                        $sql = "UPDATE r_BND SET `Region3` = '$Region' WHERE `BNDID` = $ID"; //`Barcode` = '$barcode'";
                        $result5 = execute_query($conn_da, $sql);
                    }
                    elseif($k == 3){ //4th region
                        $sql = "UPDATE r_BND SET `Region4` = '$Region' WHERE `BNDID` = $ID"; //`Barcode` = '$barcode'";
                        $result5 = execute_query($conn_da, $sql);
                    }
                }
            
                $k++;
                //$region = ""; //reset to empty
            }
        }//end of IF TID !=NULL
    }