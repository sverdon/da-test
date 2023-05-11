<?php 

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // load phpspreadsheet
include $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php'; // database

$country = $_POST['country'];
$boundary = $_POST['boundary'];
$sublevel = $_POST['sublevel'];

$sql = "SELECT GID, RegionName FROM g_Locations WHERE RegionType = '$boundary' AND FIND_IN_SET($country, GetPath(GID)) ORDER BY RegionName ASC";
$result = mysqli_query($conn_da, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $gid = $row['GID'];
    $name = $row['RegionName'];

    $sql = "SELECT CONCAT(' (', GROUP_CONCAT(T2.RegionName SEPARATOR ', '), ')') AS RegionName
            FROM (
                SELECT
                    @r AS _id,
                    (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                    @l := @l + 1 AS lvl
                FROM
                    (SELECT @r := $gid, @l := 0) vars,
                    g_Locations m
                WHERE @r <> 0) T1
            JOIN g_Locations T2
            ON T1._id = T2.GID
            WHERE T2.RegionType <> '$boundary'
            ORDER BY T1.lvl DESC";
    $result2 = mysqli_query($conn_da, $sql);
    $rows = $result2->fetch_all(MYSQLI_ASSOC);

    $regions[] = array($gid, $name . $rows[0]['RegionName']);
}

$sql = "SELECT $sublevel FROM g_Boundaries WHERE CountryID = $country";
$result = mysqli_query($conn_da, $sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);

// Load Template
$filepath = 'ra_template.xlsx';
$template = 'RegionAdder.xlsx';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($template);

// Write regions to Lookups tab
$spreadsheet->getSheetByName('lookups')->fromArray($regions, NULL, 'A2');

// Update cell values
$spreadsheet->getSheetByName('regions')->setCellValue('S1', $rows[0][$sublevel]);
$spreadsheet->getSheetByName('regions')->setCellValue('A1', $boundary . ' Name');
$spreadsheet->getSheetByName('regions')->setCellValue('B1', $rows[0][$sublevel] . ' Name');

// Save file
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($filepath);

$url = 'https://dash-delagua.com/da-forms/templates/' . $filepath;
echo json_encode($url);