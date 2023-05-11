<?php

require $_SERVER['DOCUMENT_ROOT'] . '/da-forms/dbconn.php';
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$tripid = $_POST['truckID'];
$pdfType = $_POST['pdfType'];
$trips = array();

// This isn't throwing errors properly
// $sql = "SELECT Link_DelivNote
//         FROM tbl_LogTransOut
//         WHERE TID = $tripid AND Link_DelivNote IS NULL";

// $result = mysqli_query($conn_da, $sql);

// if (mysqli_num_rows($result) == 0){
//     echo "Signed delivery note has already been received for Truck ID $tripid.";
//     http_response_code(400);
// }

// Main Query
$sql = "SELECT 
        tbl_LogTransOutNin.TDID, 
        tbl_LogTransOutNin.CellID_Dest,
        tbl_LogTransOutNin.InvestorID,
        tbl_LogTransOutNin.QSent_Tots_Dura, 
        tbl_LogTransOutNin.QSent_Posters, 
        tbl_LogWarehouses.WarehouseName
        FROM tbl_LogTransOutNin
        LEFT JOIN tbl_LogWarehouses ON tbl_LogTransOutNin.WHID_Dest = tbl_LogWarehouses.WHID
        WHERE tbl_LogTransOutNin.TID = $tripid";

$result = mysqli_query($conn_da, $sql);

while($row = mysqli_fetch_assoc($result)) {
    $destid = $row['TDID'];
    $cellid = $row['CellID_Dest'];
    $bcfid = $row['InvestorID'] ?: '000';
    $posters = $row['QSent_Posters'];
    $stoves = $row['QSent_Tots_Dura'];
    $location = $row['WarehouseName'] ?: 'Cell Office';

    $trips[] = ["tdid" => $destid, "destid" => $cellid, "bcfid" => $bcfid, "posters" => $posters, "stoves" => $stoves, "location" => $location];
}

// Append Regions
$index = 0;

foreach($trips as $trip){
    $dest = $trip['destid'];

    $sql = "SELECT T2.RegionName, T2.GID, T2.RegionType
            FROM (
                SELECT
                    @r AS _id,
                    (SELECT @r := ParentID FROM g_Locations WHERE GID = _id) AS parent_id,
                    @l := @l + 1 AS lvl
                FROM
                    (SELECT @r := $dest, @l := 0) vars,
                    g_Locations m
                WHERE @r <> 0) T1
            JOIN g_Locations T2
            ON T1._id = T2.GID
            WHERE RegionType <> 'Country'
            ORDER BY T1.lvl DESC";
    $result = mysqli_query($conn_da, $sql);

    while($row = mysqli_fetch_assoc($result)) {
        $name = $row['RegionName'];
        $id = $row['GID'];
        $type = $row['RegionType'];

        $trips[$index][$type] = $name;
        $trips[$index][$type.'id'] = $id;
    }

    $index++;
}

// Generate QR String
$numTrips = count($trips);

for($i=0; $i<$numTrips; $i++){
    $totalStoves = $totalStoves + $trips[$i]['stoves'];
    $totalPosters = $totalPosters + $trips[$i]['posters'];

    $qrString = $qrString . sprintf('%05d', $trips[$i]['stoves']) . '_' . sprintf('%03d', $trips[$i]['bcfid']) . '_' . sprintf('%06d', $trips[$i]['destid']) . '_';
}

// Generate Page 2 "Delivery To"
// Stoves & Posters
foreach($trips as $trip){
    $qsent = $trip['stoves'];
    $delivery = $qsent . ' stoves to ' . $trip['District'] . ' District/' . $trip['Sector'] . ' Sector/' . $trip['Cell'] . ' Cell';
    $pDelivery = $trip['posters'] . ' posters to ' . $trip['District'] . ' District/' . $trip['Sector'] . ' Sector/' . $trip['Cell'] . ' Cell';

    $sDeliveries[] = array("delivery" => $delivery, "stoves" => $qsent);
    $pDeliveries[] = $pDelivery;
}

foreach($sDeliveries as $key => $s){
    if($s['stoves'] > 0){
        $ss .= "<span>".$s['delivery']."</span><br>";
    }
}

foreach($pDeliveries as $p){
    $pp .= "<span>$p</span><br>";
}

// Generate QR Codes
$y = sprintf('%07d', $tripid);
$qrOne = '01_' . $y . '_' . rtrim($qrString,'_');

// Insert QR1 into database
$sql = "INSERT INTO log_QRcodes (QRCode, TID) VALUES ('$qrOne', '$y')";
$result = mysqli_query($conn_da, $sql);

// Generate PDF
$pdf = new TCPDF();
$pdf->SetPrintHeader(false);

// Page 1
$pdf->AddPage();

$html = '<img src="https://dash-delagua.com/wp-content/uploads/2022/05/DelAgua_Logo_50.png">';
$html .= "<p>Trip Number: $tripid</p>";
$html .= "<p>Truck Type: Fuso</p>";
$html .= "<table>
            <thead>
                <tr>
                    <th>Products</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kunikoa</td>
                    <td>$totalStoves</td>
                </tr>
                <tr>
                    <td>Posters</td>
                    <td>$totalPosters</td>
                </tr>
            </tbody>
        </table>";
$html .= '<p style="line-height: 50px; color: white;">.</p>'; // spacer
$html .= '<img src="https://quickchart.io/qr?text=' . $qrOne . '">';
$html .= "<style>
            table, th, td{
                border: 1px solid black;
            }
        </style>";

$pdf->writeHTML($html, true, 0, true, 0, 'C');

if($pdfType === 'scanning'){
    // generate PDF and exit script
    $pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/da-forms/delivery-notes/outbound_scanning_form.pdf', 'F');
    exit();
}

// Page 2
$pdf->AddPage();

$html = '<img src="https://dash-delagua.com/wp-content/uploads/2022/05/DelAgua_Logo_50.png"><br>';
$html .= 'DELAGUA<br>';
$html .= 'OUTBOUND DELIVERY NOTE<br>';
$html .= 'Trip Number: ' . $tripid;
$html .= '<p style="line-height: 15px; color: white;">.</p>'; // spacer

$pdf->writeHTML($html, true, 0, true, 0, 'C');

$html = "TRANSPORTER DRIVER IDENTIFICATION:<br>";
$html .= "License Plate:<br>";
$html .= "Drivers Phone #:";
$html .= "<p>PRODUCT DESCRIPTION:</p>";
$html .= '<table>
            <thead>
                <tr>
                    <th width="25%">ITEM</th>
                    <th width="25%"># OF ITEMS</th>
                    <th width="50%">FOR DELIVERY TO*</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td width="25%">COOKSTOVES</td>
                    <td width="25%">'.$totalStoves.'</td>
                    <td width="50%">'.$ss.'</td>
                </tr>
                <tr>
                    <td width="25%">POSTERS</td>
                    <td width="25%">'.$totalPosters.'</td>
                    <td width="50%">'.$pp.'</td>
                </tr>
            </tbody>
        </table>';
$html .= "*Locations described in Destination Delivery Note";
$html .= '<p style="line-height: 15px; color: white;">.</p>'; // spacer
$html .= "<style>
            table, th, td{
                border: 1px solid black;
            }
        </style>";

$pdf->writeHTML($html, true, 0, true, 0);

$html = "<table>
        <thead>
            <tr>
                <th>DELAGUA</th>
                <th>TRANSPORT DRIVER</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>NAME:</td>
                <td>NAME:</td>
            </tr>
            <tr>
                <td>DATE:</td>
                <td>DATE:</td>
            </tr>
            <tr>
                <td>SIGNATURE:</td>
                <td>SIGNATURE:</td>
            </tr>
        </tbody>
    </table>";
$html .= '<p style="line-height: 15px; color: white;">.</p>'; // spacer
$html .= '<img style="text-align: center;" src="https://quickchart.io/qr?text=' . $qrOne . '">';

$pdf->writeHTML($html, true, 0, true, 0);

// Create Destination Delivery Note for each record returned
for($i=0; $i<$numTrips; $i++){

    // Exit loop if no stoves for particular cell
    if($trips[$i]['stoves'] == 0){
        return;
    }

    // If ID is already in array, skip it
    if(in_array($trips[$i]['destid'], $destinations)){
        return;
    }

    // Add DestID to array each time we loop though
    $destinations[] = $trips[$i]['destid'];

    // Get sum of investor stoves on truck
    $sql = "SELECT SUM(QSent_Tots_Dura) AS stoves FROM tbl_LogTransOutNin WHERE TID = $tripid AND CellID_Dest = " . $trips[$i]['destid'];
    $result = mysqli_query($conn_da, $sql);
    while($row = mysqli_fetch_assoc($result)) {
        $numStoves = $row['stoves'];
    }

    // Generate QR Code 2 and insert into database
    $tdid = sprintf('%07d', $trips[$i]['tdid']);
    $qrTwo = '02_' . $tdid . '_' . sprintf('%05d', $numStoves) . '_000_' . sprintf('%06d', $trips[$i]['destid']);

    // Insert QR2 into database
    $sql = "INSERT INTO log_QRcodes(QRCode, TDID) VALUES('$qrTwo', '$tdid')";
    $result = mysqli_query($conn_da, $sql);

    // Add Page
    $pdf->AddPage();

    // Write HTML
    $html = '<img src="https://dash-delagua.com/wp-content/uploads/2022/05/DelAgua_Logo_50.png">';
    $html .= '<p>DELAGUA</p>';
    $html .= "Truck Trip #: $tripid";
    $html .= '<p>DESTINATION DELIVERY NOTE</p>';

    $pdf->writeHTML($html, true, 0, true, 0, 'C');

    $html = "<table>
                <tbody>
                    <tr>
                        <td>Destination ID</td>
                        <td>".$trips[$i]['destid']."</td>
                    </tr>
                    <tr>
                        <td>Location</td>
                        <td>".$trips[$i]['location']."</td>
                    </tr>
                    <tr>
                        <td>Cell</td>
                        <td>".$trips[$i]['Cell']."</td>
                    </tr>
                    <tr>
                        <td>Sector</td>
                        <td>".$trips[$i]['Sector']."</td>
                    </tr>
                    <tr>
                        <td>District</td>
                        <td>".$trips[$i]['District']."</td>
                    </tr>
                    <tr>
                        <td>Province</td>
                        <td>".$trips[$i]['Province']."</td>
                    </tr>
                </tbody>
            </table>";
    $html .= '<p style="line-height: 20px; color: white;">.</p>'; // spacer
    $html .= "<table>
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th># OF ITEMS EXPECTED</th>
                        <th># OF ITEMS DELIVERED</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>COOKSTOVES</td>
                        <td>".$trips[$i]['stoves']."</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>POSTERS</td>
                        <td>".$trips[$i]['posters']."</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>";
    $html .= "<p style='margin-bottom: 50px;'>DELIVERY COMMENTS IF ANY:</p>";
    $html .= '<p style="line-height: 40px; color: white;">.</p>'; // spacer
    $html .= "<style>
                table, th, td{
                    border: 1px solid black;
                }
            </style>";

    $pdf->writeHTML($html, true, 0, true, 0);

    $html = "<table>
        <thead>
            <tr>
                <th>TRANSPORT DRIVER</th>
                <th>LOCAL RESPONSIBLE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>NAME:</td>
                <td>NAME:</td>
            </tr>
            <tr>
                <td>DATE:</td>
                <td>DATE:</td>
            </tr>
            <tr>
                <td></td>
                <td>PHONE:</td>
            </tr>
            <tr>
                <td>SIGNATURE:</td>
                <td>SIGNATURE:</td>
            </tr>
        </tbody>
    </table>";
    $html .= '<p style="line-height: 25px; color: white;">.</p>'; // spacer
    $html .= '<img style="text-align: center;" src="https://quickchart.io/qr?text=' . $qrTwo . '">';

    $pdf->writeHTML($html, true, 0, true, 0);
}

$pdf->Output($_SERVER['DOCUMENT_ROOT'] . '/da-forms/delivery-notes/outbound_scanning_form.pdf', 'F');