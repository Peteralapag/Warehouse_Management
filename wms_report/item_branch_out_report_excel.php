<?php
/*
    ###################################################################
    ##                                                               ##
    ##                   CODED BY RONAN SARBON 2023                  ##
    ##                                                               ##
    ###################################################################
*/
include $_SERVER['DOCUMENT_ROOT'] . "/init.php";
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT'] . "/Plugins/PHPExcel/Classes/PHPExcel.php";
$objRMSExcel = new PHPExcel();

$recipient = $_REQUEST['recipient']; 
$dateFrom = $_REQUEST['dateFrom']; 
$dateTo = $_REQUEST['dateTo']; 
$branch = $_REQUEST['branch']; 
$filters = $_REQUEST['filters']; 
$titleText = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $branch . "_" . date("Y-m-d"));

// ###################### REPORT TITLE ##########################
$objRMSExcel->getActiveSheet()->SetCellValue('A1', $branch . " - DELIVERY CONSOLIDATED ITEMS");
$objRMSExcel->getActiveSheet()->mergeCells('A1:G1');
$objRMSExcel->getActiveSheet()->getStyle('A1:G1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objRMSExcel->getActiveSheet()->getStyle("A1")->getFont()->setBold(true);

// ###################### DATE RANGE ##########################
$objRMSExcel->getActiveSheet()->SetCellValue('A2', "FROM " . date("F d, Y", strtotime($dateFrom)) . " TO " . date("F d, Y", strtotime($dateTo)));
$objRMSExcel->getActiveSheet()->mergeCells('A2:G2');
$objRMSExcel->getActiveSheet()->getStyle('A2:G2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

// ###################### DATA HEADER ##########################
$headers = ["#", "QUANTITY", "ORDER DATE", "DELIVERY DATE", "CONTROL No.", "D.R. NUMBER", "ITEM NAME"];
$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
$columnWidths = [5, 10, 15, 15, 15, 15, 30];

// Define the header style
$headerStyle = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
    ],
/*    'fill' => [
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => [
            'rgb' => 'F1F1F1',
        ],
    ], */
];

foreach ($headers as $key => $header) {
    // Set the header text
    $cell = $columns[$key] . '4';
    $objRMSExcel->getActiveSheet()->SetCellValue($cell, $header);

    // Apply the style to the header cell
    $objRMSExcel->getActiveSheet()->getStyle($cell)->applyFromArray($headerStyle);

    // Set the column width
    $objRMSExcel->getActiveSheet()->getColumnDimension($columns[$key])->setWidth($columnWidths[$key]);
}


// ###################### QUERY DATA ##########################
$EXCELQUERY = "SELECT * 
    FROM wms_order_request WOR
    INNER JOIN wms_branch_order WBO
    ON WOR.control_no = WBO.control_no
    WHERE WOR.recipient='$recipient' AND WBO.branch='$branch' AND WOR.delivery_date BETWEEN '$dateFrom' AND '$dateTo'
    ORDER BY WBO.$filters";
$EXCELRESULTS = mysqli_query($db, $EXCELQUERY);
if ($EXCELRESULTS->num_rows > 0) {
    $ex = 0; // Row number in Excel
    $i = 5;  // Starting Excel row for data
    while ($ROWS = mysqli_fetch_array($EXCELRESULTS)) {
        $ex++;
        $objRMSExcel->getActiveSheet()->SetCellValue('A' . $i, $ex);
        $objRMSExcel->getActiveSheet()->SetCellValue('B' . $i, $ROWS['actual_quantity']);
        $objRMSExcel->getActiveSheet()->SetCellValue('C' . $i, date("M d, Y", strtotime($ROWS['trans_date'])));
        $objRMSExcel->getActiveSheet()->SetCellValue('D' . $i, date("M d, Y", strtotime($ROWS['delivery_date'])));
        $objRMSExcel->getActiveSheet()->SetCellValue('E' . $i, $ROWS['control_no']);
        $objRMSExcel->getActiveSheet()->SetCellValue('F' . $i, $ROWS['dr_number']);
        $objRMSExcel->getActiveSheet()->SetCellValue('G' . $i, $ROWS['item_description']);
	    $objRMSExcel->getActiveSheet()->getStyle('A' . $i . ':F' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $i++;
    }
} else {
    $objRMSExcel->getActiveSheet()->SetCellValue('A6', "No records found for the given criteria.");
    $objRMSExcel->getActiveSheet()->mergeCells('A6:G6');
    $objRMSExcel->getActiveSheet()->getStyle('A6:G6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}

/* ################################################################################################ */
/* ##################################### EXCEL OUTPUT DATA ######################################## */
/* ################################################################################################ */
$objRMSExcel->getProperties()
    ->setCreator("Ronan M. Sarbon")
    ->setLastModifiedBy("Ronan M. Sarbon")
    ->setTitle($titleText)
    ->setSubject("Excel Export")
    ->setDescription("Generated Excel file by RMS.")
    ->setKeywords("Excel export, PHP, PHPExcel")
    ->setCategory("Export");

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $titleText . '.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objRMSExcel, 'Excel2007');
ob_start();
$objWriter->save('php://output');
$fileData = ob_get_clean();
echo $fileData;

/* ################################################################################################ */
/* ############################### CREATED BY RONAN SARBON 2023 ################################### */
/* ################################################################################################ */

?>
