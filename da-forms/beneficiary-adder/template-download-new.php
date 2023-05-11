<?php 

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$regions = $_POST['region'];
$parentID = end($regions);
$templateUrl = $_POST['template-url'] . '&download=1';

$context = stream_context_create(
    array(
        'http' => array(
            'follow_location' => false
        )
    )
);
$file = file_put_contents('ba_template.xlsx', file_get_contents($templateUrl, false, $context));

// Get Final Sub-Regions
$sql = "SELECT GID, RegionName AS Location
        FROM g_Locations
        WHERE ParentID = $parentID AND (RegionDescrip <> 'Urban' OR RegionDescrip IS NULL)
        ORDER BY Location ASC";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $id = $row['GID'];
    $location = $row['Location'];

    $locations[] = [$id, $location];
}

// Get Filename from IDs

$filename = '';
$hideCategory = 0;

foreach($regions as $region){
    $sql = "SELECT RegionName, RegionType
            FROM g_Locations
            WHERE GID = $region";

    $result = mysqli_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result)) {
        // if regiontype is country and regionname is rwanda, hide category column
        if($row['RegionType'] == 'Country' && $row['RegionName'] != 'Rwanda') {
            $hideCategory = 1;
        }
        $filename .= $row['RegionName'] . '_';
    }
}

$filename = rtrim($filename, '_') . '.xlsx';
$filepath = 'templates/' . $filename;

// Load Template
$template = 'Bennie_Adder_template.xlsx';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);
$sheet = $spreadsheet->getSheet(1);

// Write values to 'Lookups' sheet
$spreadsheet->getSheetByName('Lookups')->fromArray($locations, NULL, 'A2');

if($hideCategory == 1) {
    $spreadsheet->getSheetByName('BennieList')->getColumnDimension('H')->setVisible(false);
}
// $spreadsheet->getSheetByName('Lookups')->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

$url = 'https://insight.delagua.org/da-forms/beneficiary-adder/' . $filepath;
echo json_encode($url);