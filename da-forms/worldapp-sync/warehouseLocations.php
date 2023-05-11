<?php
    require '../dbconn.php';
    require_once 'util.php';

    //$file = fopen("C:/Users/JingKappes/github/delagua-1/da-forms/worldapp-sync/warehouseLoc.csv", 'w');
    $file = fopen('WorkRegionLocations/warehouseLoc.csv', 'w');
    fwrite($file, "WHID,Warehouse Name,Warehouse Type,WorkRegionID,SWR1,SWR2,SWR3,SWR4,GID,CountryID\n");
    fclose($file);

/*
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
*/
    //Select all from tbl_LogWarehouses
    $sql = "SELECT * FROM tbl_LogWarehouses;";
    $result = execute_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result))
    {
        $WHID = $row['WHID'];
        $GID = $row['GID'];
        $type = $row['WarehouseType'];
        $name = $row['WarehouseName'];

        //Get parent info from GID
        if($GID != NULL){
            $sql2 = "SELECT T2.GID, T2.RegionType,T2.RegionName, T2.WorkRegion_CHW
                    FROM (
                        SELECT
                            @r AS _id,
                            (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                            @l := @l + 1 AS lvl
                        FROM
                            (SELECT @r := $GID, @l := 0) vars,
                            g_Locations m
                        WHERE @r <> 0) T1
                    JOIN g_Locations T2
                    ON T1._id = T2.GID
                    ORDER BY T1.lvl DESC";

            $result2 = execute_query($conn_da, $sql2);
            $workflag = 0;
            //swr arrays will get combined to be a assoc array
            $swrKey = array("SWR1", "SWR2", "SWR3","SWR4");
            $swrVal = array();

            while($row2 = mysqli_fetch_assoc($result2))
            {
                if($row2['RegionType'] == 'Country'){
                    $countryID = $row2['GID'];
                    $workRegion = $row2['WorkRegion_CHW'];
                }

                if($row2['RegionType'] == $workRegion)
                {
                    $workRegionID = $row2['GID'];
                    $workflag = 1;
                }
                
                if($row2['RegionType'] != $workRegion && $workflag == 1) //found a SWR
                {
                    $swrVal[] = $row2['RegionName'];
                }
            }

            $items = count($swrVal);
            if( $items != 4){ //make sure array ends up with 4 item, so array_combine works
                $add = 4-$items;
            }

            for($i=0; $i<$add; $i++){
                $swrVal[] = 'blank';
            }

            $SWR = array_combine($swrKey, $swrVal);
            //print_r($SWR);
        }

        $data = "$WHID,$name,$type,$workRegionID,";

        foreach($SWR as $key => $value)
        {
            $data .= "$value,";
        }

        $data .= "$GID,$countryID\n";

        //print($data);
        //echo PHP_EOL;

        //$file = fopen("C:/Users/JingKappes/github/delagua-1/da-forms/worldapp-sync/warehouseLoc.csv", 'a');
        $file = fopen("WorkRegionLocations/warehouseLoc.csv", 'a');
        fwrite($file, $data);
        fclose($file);
    }