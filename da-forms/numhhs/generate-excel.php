<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // load database

$regions = $_POST['region'];
$country = $regions[1];

// set $location variable to last region selected
foreach(array_reverse($regions) as $region){
    if($region !== ''){
        $location = $region;
        break 1;
    }
}

// Get data from table based on selected region
$sql = "SELECT  GID,
        NumHHs,
        RegionName,
        RegionType
        FROM (SELECT * FROM g_Locations
        ORDER BY ParentID, GID) locations,
        (SELECT @id := $location) parentid
        WHERE find_in_set(ParentID, @id)
        AND LENGTH(@id := concat(@id, ',', GID))";
$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $gid = $row['GID'];
    $numhhs = $row['NumHHs'];
    $regionName = $row['RegionName'];
    $regionType = $row['RegionType'];

    $excelData[] = array($gid, $numhhs);
    $originalValues[] = $numhhs;
    $headers = array('GID', 'NumHHs');
}

// get parent region info
foreach($excelData as $key=>&$data){
    $sql = "SELECT T2.RegionType,T2.RegionName
            FROM (
                SELECT
                    @r AS _id,
                    (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                    @l := @l + 1 AS lvl
                FROM
                    (SELECT @r := $data[0], @l := 0) vars,
                    g_Locations m
                WHERE @r <> 0) T1
            JOIN g_Locations T2
            ON T1._id = T2.GID
            ORDER BY T1.lvl DESC";
    $result = mysqli_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result)) {
        $regionType = $row['RegionType'];
        $regionName = $row['RegionName'];

        $data[] = $regionName;
        $pHeaders[$key][] = $regionType;
    }
}

// splice headers
$headers = array_merge($headers, max($pHeaders));

$filepath = 'exports/NumHHs_export.xlsx';

// Load Template
$template = 'NumHHS_template.xlsx';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);

// Add headers to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($headers, NULL, 'A1');

// Add table data to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($excelData, NULL, 'A2');

// Add original values to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($originalValues, NULL, 'T2');

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

echo json_encode('https://dash-delagua.com/da-forms/numhhs/' . $filepath);