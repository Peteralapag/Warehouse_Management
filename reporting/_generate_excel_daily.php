<?php
date_default_timezone_set('Asia/Manila');
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Plugins/PHPSheets/vendor/autoload.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;

$debugging = 0;

$years = $_REQUEST['years'];
$months = $_REQUEST['months'];
$days = $_REQUEST['days'];

$app_user = $_SESSION['wms_appnameuser'];
$date = date("F d, Y @ h:i A");
$title_date = date("Ymdhis");
$titleText = "DAILY INVENTORY REPORT";


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$spreadsheet = new Spreadsheet();
$writer = new Xlsx($spreadsheet);
$worksheet = $spreadsheet->getActiveSheet();


	$totalBranchCount = 0;
	$queryC = "SELECT cluster FROM tbl_cluster WHERE active=1";
	$resultsC = $db->query($queryC);	
	$startColumn = 'A3';
	$startColumnIndex = 0; 
	while ($ROWC = mysqli_fetch_array($resultsC))
	{
	    $cluster = $ROWC['cluster'];
	    $branch_cnt = $function->CountBranch($cluster, $db); 
		$totalBranchCount   += $branch_cnt;
		
		$endColumnIndex = $startColumnIndex + $totalBranchCount - 1;
	    $endColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endColumnIndex);
	
	    $mergeRange = $startColumn . '3:' . $endColumnLetter . '3';
	    $worksheet->mergeCells($mergeRange);
	
	    // Move the starting column index for the next cluster
	    $startColumnIndex = $endColumnIndex + 1;
    }


















if($debugging == 0)
{
	/* ################################################################################################ */
	/* ##################################### EXCEL OUTPUT DATA ######################################## */
	/* ################################################################################################ */
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="example.xlsx"');
	header('Cache-Control: max-age=0');
	header('Pragma: public');	
	$writer->save('php://output');
	exit;
	/* ################################################################################################ */
	/* ############################### CREATED BY RONAN SARBON 2023 ################################### */
	/* ################################################################################################ */
}
function generateExcelColumnNames($maxCount) {
    $columns = array();
    $letters = range('A', 'Z');

    $singleLetterCount = min(26, $maxCount);
    for ($i = 0; $i < $singleLetterCount; $i++) {
        $columns[] = $letters[$i];
    }
    $remainingCount = $maxCount - $singleLetterCount;
    if ($remainingCount > 0) {
        $doubleLetterCount = min(26, $remainingCount);
        for ($i = 0; $i < $doubleLetterCount; $i++) {
            foreach ($letters as $secondLetter) {
                $columns[] = $letters[$i] . $secondLetter;
                $remainingCount--;
                if ($remainingCount === 0) {
                    break 2; // Exit both loops when remainingCount reaches 0
                }
            }
        }
    }
    return $columns;
}
?>