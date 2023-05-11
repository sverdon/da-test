<?php
    //require '../dbconn.php';
    require_once 'util.php';

    //$file = fopen("C:/Users/JingKappes/github/delagua-1/da-forms/worldapp-sync/distrRegions.csv", 'w');
    $file = fopen('WorkRegionLocations/distrRegions.csv', 'w');
    fwrite($file, "GID,RegionName,WRID,SWR1,SWR2,SWR3,SWR4,CountryGID\n");
    fclose($file);
    
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

     //swr arrays will get combined to be a assoc array
     $swrKey = array("SWR1", "SWR2", "SWR3","SWR4");
    

    //get distr Region from g_Boundaries
    //TODO: get rid of WHERE statement
    $sql = "SELECT  * FROM g_Boundaries;"; // WHERE CountryID = 18022;";

    $result = execute_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result))
    {
        $GID = $row['CountryID'];
        $distrRegionType = $row['DistrRegion'];
        $workRegionType = $row['WorkRegion_CHW'];

        echo "Country: $GID, DistrRegion: $distrRegionType, WorkRegion: $workRegionType\n".PHP_EOL;

        //get list of Distr regions for country
        $sql2 = "SELECT GID, RegionType, RegionName, ParentID, GetPath(GID) `path`
                FROM g_Locations
                WHERE RegionType = '$distrRegionType' AND FIND_IN_SET($GID, GetPath(GID));";

        $result2 = execute_query($conn_da, $sql2);

        $ParentID = '';
        //For each Distr Region, get the WorkRegion_CHW
        while($row2 = mysqli_fetch_assoc($result2))
        {
            $distrRegionGID = $row2['GID'];
            $distrRegionName = $row2['RegionName'];

            //echo "RegionID: $distrRegionGID, RegionName: $distrRegionName".PHP_EOL;

            if($row2['ParentID'] != $ParentID)
            {
                //echo "ParentID was $ParentID, ";
                $ParentID = $row2['ParentID'];
                //echo "now ParentID is $ParentID\n" .PHP_EOL;

                unset($swrVal);
                $swrVal = array();

                $sql3 = "SELECT T2.GID, T2.RegionType,T2.RegionName, T2.WorkRegion_CHW
                        FROM (
                            SELECT
                                @r AS _id,
                                (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                                @l := @l + 1 AS lvl
                            FROM
                                (SELECT @r := $distrRegionGID, @l := 0) vars,
                                g_Locations m
                            WHERE @r <> 0) T1
                        JOIN g_Locations T2
                        ON T1._id = T2.GID
                        ORDER BY T1.lvl DESC";

                $result3 = execute_query($conn_da, $sql3);
                
                $flag = 0; //mark if workRegion found

                // //swr arrays will get combined to be a assoc array
                // $swrKey = array("SWR1", "SWR2", "SWR3","SWR4");
                // $swrVal = array();

                while($row3 = mysqli_fetch_assoc($result3))
                {   
                    if($row3['RegionType'] == $workRegionType){
                        $WRID = $row3['GID'];
                        //echo "WRID: $WRID\n" .PHP_EOL;
                        $flag = 1;
                    }

                    //found a SWR that is not the WorkRegion_CHW and not the DistrRegion
                    if($row3['RegionType'] != $workRegionType && $flag == 1 && $row3['GID'] != $distrRegionGID) 
                    {
                        $swrVal[] = $row3['RegionName'];
                        //echo "Adding SWR " . $row3['RegionName'] .PHP_EOL;
                    }
                }
            }

           
            $items = count($swrVal);
            if($items > 4){
                exit("swrVal has more than 4 items, exiting");
            }
            //echo "$items elements in swrVal array\n" .PHP_EOL;
            if($items < 4){ //make sure array ends up with 4 item, so array_combine works
                $add = 4-$items;
                //echo "add is $add\n".PHP_EOL;
            }
            else{
                $add = 0;
            }

            //echo "add is: $add\n".PHP_EOL;
            for($i=0; $i<$add; $i++){
                $swrVal[] = 'blank';
            }

            //print_r($swrVal);
            $SWR = array_combine($swrKey, $swrVal);
            //print_r($SWR);
        

            $data = "$distrRegionGID,$distrRegionName,$WRID,";

            foreach($SWR as $key => $value)
            {
                $data .= "$value,";
            }

            $data .= "$GID\n";

            //echo "Data is: $data\n".PHP_EOL;
            //echo PHP_EOL;

            //$file = fopen("C:/Users/JingKappes/github/delagua-1/da-forms/worldapp-sync/distrRegions.csv", 'a');
            $file = fopen("WorkRegionLocations/distrRegions.csv", 'a');
            fwrite($file, $data);
            fclose($file);
        }
    }