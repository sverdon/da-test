<?php
    require '../dbconn.php';
    require_once 'util.php';

    //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/country.csv', 'w');
    $file = fopen('WorkRegionLocations/country.csv', 'w');
    fwrite($file, "CountryID,Country,Level 1,Level 2,Level 3,Level 4,Level 5,Level 6,Level 7\n");
    fclose($file);

    //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/country_WR.csv', 'w');
    $file = fopen('WorkRegionLocations/country_WR.csv', 'w');
    fwrite($file, "CountryID,Country,SWR_1,SWR_2,SWR_3,SWR_4,PWR_1,PWR_2,PWR_3,PWR_4,Work_Region\n");
    fclose($file);

    //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/WorkRegion.csv', 'w');
    $file = fopen('WorkRegionLocations/WorkRegions.csv', 'w');
    fwrite($file, "CountryID,GID,ParentID,RegionName\n");
    fclose($file);

    for($i=1; $i<5;$i++){
        $name = 'PWR_' .$i; // .'.csv';
        //echo $name .PHP_EOL;
        //$file = fopen("C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/$name.csv", 'w');
        $file = fopen("WorkRegionLocations/$name.csv", 'w');
        fwrite($file, "CountryID,GID,ParentID,RegionName\n");
        fclose($file); 
    
        $name = 'SWR_' .$i; // .'.csv';
        //echo $name .PHP_EOL;
        //$file = fopen("C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/$name.csv", 'w');
        $file = fopen("WorkRegionLocations/$name.csv", 'w');
        fwrite($file, "CountryID,GID,ParentID,RegionName,WorkRegionID\n");
        fclose($file); 
    }

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

    //Select all countries from g_Locations
    //TODO:change WHERE condition
    //FIXME: only get most recently added country?
    $sql = "SELECT * FROM g_Locations WHERE RegionType = 'Country';"; // AND RegionName = 'Sierra Leone';";
    $result = execute_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result))
    {
        $countryGID = $row['GID'];
        $countryName = $row['RegionName'];

        //get work region info for country from g_Boundary tables
        $sql2 = "SELECT * FROM g_Boundaries WHERE CountryID = $countryGID";
        $result2 = execute_query($conn_da, $sql2);

        //assoc-array to hold region values and levels
        $levels = array("Country" => "", "1" => "", "2" => "", 
        "3" => "", "4" => "",  "5" => "",
        "6" => "", "7" => "",);

        while($row2 = mysqli_fetch_assoc($result2)){
            if($row2['CountryID'] == $countryGID){
                $levels['Country'] = $row2['Country'];
                $levels['1'] = $row2['Level1'];
                $levels['2'] = $row2['Level2'];
                $levels['3'] = $row2['Level3'];
                $levels['4'] = $row2['Level4'];
                $levels['5'] = $row2['Level5'];
                $levels['6'] = $row2['Level6'];
                $levels['7'] = $row2['Level7'];
                $workRegion = $row2['WorkRegion_CHW'];
                $distrRegion = $row2['DistrRegion'];
            }
        }

        echo "WorkRegion_CHW is $workRegion" .PHP_EOL;

        //print_r($levels);

        //record which index the $workRegion is
        $count = count($levels);
        $i = 0;
        $workIndex = -1;
        foreach($levels as $key => $value ){
            if($workRegion == $value){
                $workIndex = $i;
            }
            $i++;
        }

        //make arrays for PWR and SWR
        $preWorkRegions = array();
        $subWorkRegions = array();
        $k = 0;
        $j = 1; //want SWR_ to start at 1 again
        foreach($levels as $key => $value ){
            if($key != 'Country' && $value != $workRegion && $k < $workIndex){
                $preWorkRegions += ['PWR_' .$k => $value];
            }
            if($key != 'Country' && $value != $workRegion && $k > $workIndex){
                $subWorkRegions += ['SWR_' .$j => $value];
                $j++;
            }
            $k++;
        }

        //print_r($preWorkRegions);
        //print_r($subWorkRegions);

         //add to country CSVs
         $data = "$countryGID,";
         //Country: CountryID, Country, Level(s) - use Levels array
         $i = 1;

         //fill any 'NULL' values to 'blank'
         foreach($levels as $key => $value){
            if($value == NULL){
                $levels[$key] = 'blank';
            }
         }

         $data .= implode(",", $levels);

         echo "Country: $data" .PHP_EOL;
         //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/country.csv', 'a');
         $file = fopen("WorkRegionLocations/country.csv", 'a');
         fwrite($file, $data ."\n");
         fclose($file);

         //Country_SWR: CountryID, Country, SWR(s), PWR(s), WorkRegion
         $data = "$countryGID,$countryName,";

         //calculate PWR/SWR
         for($i=1; $i<5; $i++){ //start $1 at 1
             if(array_key_exists('PWR_' . $i, $preWorkRegions) == FALSE){
                 $preWorkRegions += ['PWR_' . $i => 'blank'];
             } 
             if(array_key_exists('SWR_' . $i, $subWorkRegions) == FALSE){
                 $subWorkRegions += ['SWR_' . $i => 'blank'];
             }
             elseif($subWorkRegions['SWR_' . $i] == NULL){ //
                 $subWorkRegions['SWR_' . $i] = 'blank';
             } 
         }

         //print_r($preWorkRegions);
         //print_r($subWorkRegions);

        
         //concat the Pre/Sub work regions
         $i = 0;
         foreach($subWorkRegions as $key => $value){
            if($i > 3){
                break; //only want to do 4 SWR 
            }
            $data .= $value . ',';
            $i++;
         }
        
         foreach($preWorkRegions as $key => $value){
             $data .= $value . ',';
         }

         $data .= $workRegion;

         echo "Country_WR: $data" .PHP_EOL;
         //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/country_WR.csv', 'a');
         $file = fopen("WorkRegionLocations/country_WR.csv", 'a');
         fwrite($file, $data ."\n");
         fclose($file);
    
        //get all children aka subregions in country
        //IMPORTANT: make sure $countryGID is set
    /*
        //old sql to find all children. ParentID < GID needs to be true for it to work
        $sql3 = "SELECT GID, RegionType, RegionName, ParentID 
                FROM (select * from g_Locations) locations,
                (select @id := $countryGID) parentid
                WHERE find_in_set(ParentID, @id)
                AND length(@id := concat(@id, ',', GID));";
    */
        $sql3 = "SELECT GID, RegionType, RegionName, ParentID, GetPath(GID) `path`
                FROM g_Locations
                WHERE FIND_IN_SET($countryGID, GetPath(GID));";

        $result3 = execute_query($conn_da, $sql3);

        while($row3 = mysqli_fetch_assoc($result3))
        {
            $regionType = $row3['RegionType'];
            $regionName = $row3['RegionName'];
            $regionGID = $row3['GID'];
            $regionParent = $row3['ParentID'];

            //if region is work region
            if($regionType == $workRegion){
                $WRID = $row3['GID'];
                $data = $countryGID . ',' .$regionGID .','. $regionParent .','. $regionName ."\n";
                //$file = fopen('C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/WorkRegion.csv', 'a');
                $file = fopen("WorkRegionLocations/WorkRegions.csv", 'a');
                fwrite($file, $data);
                fclose($file);
            }

            foreach($preWorkRegions as $key => $value)
            {
                if($regionType == $value){
                    $data = $countryGID . ',' .$regionGID .','. $regionParent .','. $regionName ."\n";
                    //$file = fopen("C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/$key.csv", 'a');
                    $file = fopen("WorkRegionLocations/$key.csv", 'a');
                    fwrite($file, $data);
                    fclose($file);
                }
            }

            foreach($subWorkRegions as $key => $value)
            {
                if($regionType == $value){
                    $data = $countryGID . ',' .$regionGID .','. $regionParent .','. $regionName .','. $WRID . "\n";
                    //$file = fopen("C:/Users/JingKappes/github/DelAgua-Test-PHP/WorkRegionLocations/$key.csv", 'a');
                    $file = fopen("WorkRegionLocations/$key.csv", 'a');
                    fwrite($file, $data);
                    fclose($file);
                }
            }
        }
    }