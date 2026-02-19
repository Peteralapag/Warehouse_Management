<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;

$_SESSION['WMS_MONTH'] = $_REQUEST['month'];
$_SESSION['WMS_YEAR'] = $_REQUEST['year'];

$recipient = $_REQUEST['recipient'];
$month = $_REQUEST['month'];
$year = $_REQUEST['year'];
$cnt_start = 1;
$days_cnt = 31;
$cnt_end =  31;
$col = '*';
$as_of_date = date("F Y", strtotime($year."-".$month));
?>
<style>
.paper-size {margin: 0 auto;margin-top: 10px;width: 8.5in;height: 13in;border: 1px solid #aeaeae;}
.lamesa td, th {border: 1px solid #232323;padding: 3px;font-size: 10px;}
.qty-box td {text-align: center;border-left: 0;border-right: 0;border-bottom: 0;width: 25%;font-weight: 600;}
.qty-box-data td {text-align: center;border-top: 0;border-left: 0;border-right: 0;border-bottom: 0;width: 25%;}
.numbox td {border: 0 !important;}
@media print {
    .paper-sizes {page-break-before: always;}
    .paper-inner {margin-bottom: 0.125in;margin-top: 0.25in;}
}
</style>
<?php
	$page = '';
	$sqlCnt1 = "SELECT COUNT(*) FROM wms_itemlist WHERE recipient='$recipient'";
	$res1 = mysqli_query($db, $sqlCnt1);    
	$r1 = mysqli_fetch_row($res1);
	$bilang = $r1[0];
	$lim = 55;
	$tot = ($bilang / $lim);
	$totalbilang = ceil($bilang / $lim);
		
	for ($x = 1; $x <= $totalbilang; $x++)
	{
		$page = $x;  
		$limit = $lim;  
		$start_from = $limit * ($page - 1); 

?>
<div class="paper-sizes">
	<div class="paper-inner">			
		<table style="width: 100%;border-collapse:collapse" class="lamesa">
			<tr>
				<td colspan="4" style="text-align:center;border:0; position:relative;font-size:18px">
					<span style="font-size:18px;font-weight:600"><?php echo strtoupper($recipient)?></span>
					<span style="position: absolute; right:10px;top:5px;font-size:14px;font-family:Arial, Helvetica, sans-serif">Page <?php echo $page?></span>
				</td>
			</tr>
			<tr>
				<td style="width:230px;text-align:center">As of <?php echo $as_of_date ?></td>
				<th colspan="3" style="text-align:center">SUMMARY</th>
			</tr>
			<tr>
				<td style="text-align:center;font-size:18px">ITEM LIST</td>
				<td style="padding:0">
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%">
						<tr>
							<td colspan="4" style="border:0;text-align:center;font-weight:600">QUANTITY</td>
						</tr>
						<tr class="qty-box">
							<td style="border-right:1px solid #232323;">Beg.</td>
							<td style="border-right:1px solid #232323">In</td>
							<td style="border-right:1px solid #232323">Out</td>
							<td>Ending</td>
						</tr>
					</table>					
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->					
				</td>
				<td style="width:20px;border-bottom:0">&nbsp;</td>
				<td style="padding:0">
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%">
						<tr>
							<td colspan="4" style="border:0;text-align:center;font-weight:600">AMOUNT</td>
						</tr>
						<tr class="qty-box">
							<td style="border-right:1px solid #232323">Beg.</td>
							<td style="border-right:1px solid #232323">In</td>
							<td style="border-right:1px solid #232323">Out</td>
							<td>Ending</td>
						</tr>
					</table>					
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->					
				</td>
			</tr>
<?php
	$QuerySummary = "SELECT * FROM wms_itemlist WHERE recipient='$recipient' LIMIT $start_from, $limit";
	$summaryResults = mysqli_query($db, $QuerySummary);		
	if ( $summaryResults->num_rows > 0 ) 
	{
		$s=0;$beg_amount=0;$inventory_in_amount=0;$inventory_out_amount=0;
		$inventory_ending_amount=0;
		while($ROWS = mysqli_fetch_array($summaryResults))  
		{
			$s++;
			$item_code = $ROWS['item_code'];
			
			$item_price = $inventory->GetAveragePrice($item_code,$month,$year,$db);			
			
			$weekly_1 = $inventory->getMonthlyIn(1,$item_code,$month,$year,$db);
		    $weekly_2 = $inventory->getMonthlyIn(2,$item_code,$month,$year,$db);
		    $weekly_3 = $inventory->getMonthlyIn(3,$item_code,$month,$year,$db);
		    $weekly_4 = $inventory->getMonthlyIn(4,$item_code,$month,$year,$db);
		    $weekly_5 = $inventory->getMonthlyIn(5,$item_code,$month,$year,$db);
			
			$beginning = $inventory->getInventoryBeginning($cnt_start,$cnt_end,$days_cnt,$col,$item_code,$month,$year,$db);
			$inventory_in = ($weekly_1 + $weekly_2 + $weekly_3 + $weekly_4 + $weekly_5);			
			$inventory_out = $inventory->getWHTotalOut($item_code,$month,$year,$db);			
			$inventory_ending = ($beginning + $inventory_in) - $inventory_out;
			
			$beginning_amount = ($beginning * $item_price);
			$inventory_in_amount = ($inventory_in * $item_price);
			$inventory_out_amount = ($inventory_out * $item_price);
			$inventory_ending_amount = ($inventory_ending * $item_price);
			
?>			
			<tr>
				<td style="padding:0 !important">					
					<table style="width: 100%" class="numbox" cellpadding="0" cellspacing="0">
						<tr>
							<td style="width:30px; text-align:center;border-right:1px solid #232323 !important"><?php echo $s;?></td>
							<td style="padding-left:10px"><?php echo $function->limitString($ROWS['item_description'], 30) ?></td>
						</tr>
					</table>
				<td style="padding:0">
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%">
						<tr class="qty-box-data">
							<td style="border-right:1px solid #232323"><?php echo $beginning ?></td>
							<td style="border-right:1px solid #232323"><?php echo $inventory_in?></td>
							<td style="border-right:1px solid #232323"><?php echo $inventory_out?></td>
							<td><?php echo $inventory_ending;?></td>
						</tr>
					</table>					
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->					
				</td>
				<td style="width:20px;border-top:0;border-bottom:0">&nbsp;</td>
				<td style="padding:0">
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%">
						<tr class="qty-box-data">
							<td style="border-right:1px solid #232323"><?php echo $beginning_amount?></td>
							<td style="border-right:1px solid #232323"><?php echo $inventory_in_amount?></td>
							<td style="border-right:1px solid #232323"><?php echo $inventory_out_amount?></td>
							<td><?php echo $inventory_ending_amount?></td>
						</tr>
					</table>					
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->					
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan="4">NO RECORDS</td>
		<tr>
<?php } ?>		
		</table>		
	</div>
</div>
<?php } ?> <!-- ############################# END OF PAGE LOAD TENGENE @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
<script>
window.print();
</script>