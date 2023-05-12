<?php 

set_time_limit(30);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$regions = $_POST['region'];
$country = $regions[1];
$parentID = end($regions);

// Get Final Sub-Regions
$sql = "SELECT GID, RegionName AS Location
        FROM g_Locations
        WHERE ParentID = $parentID
        ORDER BY Location ASC";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['GID'];
    $location = $row['Location'];

    $locations[] = [$id, $location];
}

// Get Filename from IDs
$filename = '';

foreach($regions as $region){
    if(!$region){
        continue;
    }
    $sql = "SELECT RegionName
            FROM g_Locations
            WHERE GID = $region";

    $result = mysqli_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result)) {
        $filename .= $row['RegionName'] . '_';
    }
}

$filename = 'CHWAdder_' . rtrim($filename, '_') . '.xlsx';
$filepath = 'chw-templates/' . $filename;

// Load Template
$template = 'Team_Member_Adder_CHW.xlsx';
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template) or die('unable to load sheet');

// Write country GID
$spreadsheet->getSheetByName('template')->setCellValue('AJ1', $country);

// Write values to 'Lookups' sheet
$spreadsheet->getSheetByName('Lookups')->fromArray($locations, NULL, 'A2');

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

$url = 'https://insight.delagua.org/da-forms/templates/' . $filepath;
echo json_encode($url);