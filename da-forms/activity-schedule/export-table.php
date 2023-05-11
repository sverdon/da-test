<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet

$tableData = $_POST['tableData'];
$headers = $_POST['headers'];
$teamIDs = array_chunk($_POST['teamIDs'], 1);

// combine headers and table data
foreach($tableData as $row){
    $newData[] = array_combine($headers, $row);
}

// save original values for comparison
foreach($newData as $row){
    $originalValues[] = array($row['Team ID'], $row['Activity Date'], $row['# HHs']);
}

$filepath = 'exports/Activity_Schedule_Export.xlsx';

// Load Template
$template = 'Schedule_template.xlsx';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);

// Add headers to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($headers, NULL, 'A1');

// Add table data to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($newData, NULL, 'A2');

// Add original values to 'Schedule' sheet
$spreadsheet->getSheetByName('Schedule')->fromArray($originalValues, NULL, 'W2');

// Add teamIDs to 'Lookups' sheet
$spreadsheet->getSheetByName('Lookups')->fromArray($teamIDs, NULL, 'A2');

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

echo json_encode($filepath);