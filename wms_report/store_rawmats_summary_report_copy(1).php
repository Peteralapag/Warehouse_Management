<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
include $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/con_init.php";
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.store_functions.php";
$function = new WMSFunctions;
$storeFunction = new WMSStoreFunctions;

$_SESSION['WMS_MONTH'] = $_POST['month'];
$_SESSION['WMS_YEAR'] = $_POST['year'];
$_SESSION['WMS_STORE_TARGET'] = $_POST['target'];

$month = $_POST['month'];
$year = $_POST['year'];
$cnt_start = 1;
$days_cnt = 31;
$cnt_end =  31;
$col = '*';
$as_of_date = date("F Y", strtotime($year."-".$month));
?>
<style>
.paper-size {margin:0 auto;margin-top: 10px;width:8.5in; /* height: 13in; */ border: 1px solid #aeaeae;padding:0.125in;}
.lamesa td, th {border: 1px solid #232323;padding: 3px;font-size: 10px;}
.qty-box td {text-align:center;border-left:0;border-right:0;border-bottom:0;width: 25%;font-weight:600}
.qty-box-data td {text-align:center;border-top:0;border-left:0;border-right:0;border-bottom:0;width: 25%;font-size:8px;}
.cluster-numbox td {border:0 !important;background:#f1f1f1}
.numbox td {border:0 !important}
.cluster-shell {
	border-collapse:collapse
}
.cluster-name td{
	padding:5px;
}
</style>
<div class="paper-size">
	<div class="paper-inner">
			
		<table style="width: 100%;border-collapse:collapse" class="lamesa">
			<tr>
				<td colspan="4" style="font-size:18px; text-align:center;font-weight:600"><?php echo $_POST['target']?></td>
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
				<td style="width:20px;border-bottom:">&nbsp;</td>
				<td style="padding:0">
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%">
						<tr>
							<td colspan="4" style="border:0;text-align:center;font-weight:600">AMOUNT</td>
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
			</tr>
<?php
	$QuerySummary = "SELECT * FROM tbl_cluster ORDER BY cluster ASC";
	$summaryResults = mysqli_query($db, $QuerySummary);		
	if ( $summaryResults->num_rows > 0 ) 
	{
		$c=0;
		while($ROWS = mysqli_fetch_array($summaryResults))  
		{
			$c++;
			$cluster = $ROWS['cluster'];
?>			
			<tr>
				<td colspan="4" style="padding:0;border:0">
			<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
					<table style="width: 100%" class="cluster-shell">
						<tr>
							<td style="padding:0 !important;border:0" colspan="4">					
								<table style="width: 100%;border:0;" class="cluster-numbox" cellpadding="0" cellspacing="0">
									<tr>
										<td style="width:30px; text-align:center;font-size:14px"><i class="fa-solid fa-layer-group"></i></td>
										<td style="padding-left:10px;font-size:14px"><?php echo $function->limitString($cluster, 30) ?></td>
									</tr>
								</table>
							</td>
						</tr>
<?php
	$QueryBranch = "SELECT * FROM tbl_branch WHERE location='$cluster' ORDER BY branch ASC";
	$branchResults = mysqli_query($db, $QueryBranch);		
	if ( $branchResults->num_rows > 0 ) 
	{
		$b=0;
		while($BROWS = mysqli_fetch_array($branchResults))  
		{
			$b++;
			$branch = $BROWS['branch'];
			echo $storeFunction->GetRawmatsSumData($year,$month,$branch,$conn);
?>						
						<tr>
							<td style="width:230px;padding-left:20PX;"><?php echo $b . " - " . $function->limitString($branch, 25)?></td>
							<td>
							<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
								<table style="width: 100%;border-collapse:collapse" cellpadding="0" cellspacing="0">
									<tr class="qty-box-data">
										<td style="border-right:1px solid #232323"><?php echo $storeFunction->GetRawmatsSumData('beginning',$year,$month,$branch,$conn)?></td>
										<td style="border-right:1px solid #232323">A</td>
										<td style="border-right:1px solid #232323"><?php echo $storeFunction->GetRawmatsSumData('transfer_out',$year,$month,$branch,$conn)?></td>
										<td><?php echo $storeFunction->GetRawmatsSumData('actual_count',$year,$month,$branch,$conn)?></td>
									</tr>
								</table>
							<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
							
							</td>
							<td style="width:20px;border:0;border-bottom:0">&nbsp;</td>
							<td>
								<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
								<table style="width: 100%;border-collapse:collapse" cellpadding="0" cellspacing="0">
									<tr class="qty-box-data">
										<td style="border-right:1px solid #232323">.00</td>
										<td style="border-right:1px solid #232323">.00</td>
										<td style="border-right:1px solid #232323">.00</td>
										<td>.00</td>
									</tr>
								</table>
							<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
							</td>
						</tr>
<?php } } else { ?>
						<tr>
							<td colspan="4">NO BRANCH NAME</td>
						</tr>
<?php } ?>												
					</table>
				<!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->					
				</td>
			</tr>

<?php
		}
	} else {
?>
<?php } ?>		
		</table>		
	</div>
</div>