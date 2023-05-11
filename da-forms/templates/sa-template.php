<?php 

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$country = $_POST['country'];
$templateUrl = $_POST['template-url'] . '&download=1';

// need to figure out how to download file from url to server
// ob_get_clean();
// $file = file_put_contents('sa_template.xlsx', file_get_contents($templateUrl));
// ob_end_clean();
// exit();

$filepath = 'sa-templates/sa_template.xlsx';

// Load Template
$template = 'Team_Member_Adder_Staff.xlsx';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);

// Write country GID
$spreadsheet->getSheetByName('template')->setCellValue('AJ1', $country);

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

$url = 'https://dash-delagua.com/da-forms/templates/' . $filepath;
echo json_encode($url);