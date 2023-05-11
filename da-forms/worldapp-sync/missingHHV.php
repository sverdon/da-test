<?php
    require '../dbconn.php';
    require_once 'util.php';

    echo "Running missingHHV script".PHP_EOL;
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
    //Clear the r_MissingHHVs table
    $sql = "TRUNCATE r_MissingHHVs;";
    $result = execute_query($conn_da, $sql);

    //get any barcodes, and associated HHID, that are in Distr table but not in HHVs table
    // $sql = "SELECT DistrFormID, HHID, BarcodeID, SerialNumber FROM fd_Distr d
    //         WHERE NOT EXISTS
    //             (Select SerialNumber FROM fd_HHVs h
    //             WHERE h.BarcodeID = d.BarcodeID)
    //             AND HHID is not null";

    //NOTE: Alternative method 
    // SELECT d.HHID, d.SerialNumber 
    // FROM fd_Distr d
    // LEFT OUTER JOIN fd_HHVs h ON h.SerialNumber = d.SerialNumber
    // WHERE d.HHID is not NULL and h.SerialNumber is NULL;

    $sql = "CREATE TEMPORARY table matches as
            SELECT distinct h.SerialNumber as SerialNumber
            FROM fd_HHVs h
                INNER JOIN fd_Distr as d ON h.SerialNumber = d.SerialNumber
            WHERE d.HHID is not null;

            ALTER TABLE `matches` ADD INDEX `SerialNumber_index` (`SerialNumber`);

            SELECT HHID, fd.SerialNumber
            from fd_Distr fd left outer join
                matches m on fd.SerialNumber = m.SerialNumber
            where m.SerialNumber is null
            ORDER BY HHID;
            Drop Table if Exists matches;";

    $missing = array();

    //$result = execute_query($conn_da, $sql);
    if(mysqli_multi_query($conn_da, $sql)){
        do{
            if($result = mysqli_store_result($conn_da)){
                while($row = mysqli_fetch_assoc($result)){
                    $missing [] = $row;
                    //echo "HHID: " .$row['HHID'] . " SerialNumber: " . $row['SerialNumber'] .PHP_EOL;
                }
            }
        }while(mysqli_next_result($conn_da));
    }


    //while($row = mysqli_fetch_assoc($result)) //for each record where SerialNumber is in Distr but not in HHV
    $count = count($missing);
    for($i=0; $i<$count; $i++)
    {
        $HHID = $missing[$i]['HHID']; 
        $serialNumber = $missing[$i]['SerialNumber'];
        //echo "HHID: $HHID, SerialNumber: $serialNumber" .PHP_EOL;

        //Use the associated HHID to get the beneficiary info tied to that HHID
        if($HHID != NULL){
            $sql = "SELECT HOH_GivenName, HOH_SurName, VillageID FROM tbl_BenList WHERE HHID = $HHID";
            //echo $sql .PHP_EOL;
            $result2 = execute_query($conn_da, $sql);

            while($row2 = mysqli_fetch_assoc($result2))
            {
                //echo "Row is: $row" .PHP_EOL;
                //Clean names and Concat the First and Last name
                $first = $row2['HOH_GivenName'];
                $first = mysqli_real_escape_string($conn_da, $first);
                $last = $row2['HOH_SurName'];
                $last = mysqli_real_escape_string($conn_da, $last);
                $fullName = $first. " " .$last;
                echo "Fullname: $fullName "; //.PHP_EOL;

                //Get the Province/District/Sector/Cell
                $villageID = $row2['VillageID'];
                echo "VillageID is: $villageID".PHP_EOL;


                $regions = get_parentGIDs($conn_da, $villageID); //, $country, $boundary1, $boundary2, $boundary3, $boundary4, $boundary5, $boundary6, $boundary7);
          
                $country = $regions['Country'];
                $boundary1 = $regions['Boundary1'];
                $boundary2 = $regions['Boundary2'];
                $boundary3 = $regions['Boundary3'];
                $boundary4 = $regions['Boundary4'];
                $boundary5 = $regions['Boundary5'];
                $boundary6 = $regions['Boundary6'];
                $boundary7 = $regions['Boundary7'];

                if($fullName != NULL || $serialNumber != NULL){ //$country != NULL || $district != NULL || $sector != NULL || $cell != NULL){
                    $sql = "INSERT INTO r_MissingHHVs (`Country`, `Boundary1`, `Boundary2`, `Boundary3`, `Boundary4`, `Boundary5`, `Boundary6`, `Boundary7`,`HOH Name`, `Barcode`) "
                            ."VALUES ('$country', '$boundary1', '$boundary2', '$boundary3', '$boundary4', '$boundary5', '$boundary6', '$boundary7', '$fullName', '$serialNumber')";
                    //echo $sql .PHP_EOL;
                    $result4 = execute_query($conn_da, $sql);
                }
        

            }//end of while loop
        
        }//end of IF HHID != NULL
    }
