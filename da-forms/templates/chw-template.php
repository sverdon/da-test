<?php 

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$regions = $_POST['region'];
$country = $regions[1];
$parentID = end($regions);
$templateUrl = $_POST['template-url'] . '&download=1';

// $context = stream_context_create(
//     array(
//         'http' => array(
//             'follow_location' => false
//         )
//     )
// );
// $file = file_put_contents('chw_template.xlsx', file_get_contents($templateUrl, false, $context));

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
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);

// Write country GID
$spreadsheet->getSheetByName('template')->setCellValue('AJ1', $country);

// Write values to 'Lookups' sheet
$spreadsheet->getSheetByName('Lookups')->fromArray($locations, NULL, 'A2');

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

$url = 'https://dash-delagua.com/da-forms/templates/' . $filepath;
echo json_encode($url);