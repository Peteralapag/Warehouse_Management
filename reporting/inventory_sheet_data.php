<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$cluster_cnt = $function->CountRows("tbl_cluster",$db);

$_SESSION['WMS_YEARS'] = $_POST['theyears'];
$_SESSION['WMS_MONTHS'] = $_POST['themonths'];
$_SESSION['WMS_DAYS'] = $_POST['thedays'];

$year = $_POST['theyears'];
$month = $_POST['themonths'];
$day = $_POST['thedays'];
$trans_date = $year."-".$month."-".$day;
?>
<style>
.sheetmen {border-collapse:collapse}
.sheetmen td,.sheetmen th {border:1px solid #636363;}
.tr-center {text-align:center}
.tr-height th {padding:5px;white-space:normal;}
.tr-value td {padding:5px;white-space:nowrap;font-size:11px;}
.tr-value th {padding:5px;white-space:nowrap;font-size:11px; color:#232323 !important}
.tr-bg-yellow td {background: #fcf4dc;}
.tr-bg-blue {background: #dcedfc;}
.tr-upper th {white-space:nowrap !important;}
.pcount-input {position: absolute;top: 0;left: 0;bottom:0;margin: 0;border:0;width:100%;font-size:12px;text-align:center;background:#fbe8ea;cursor: pointer;}
.remarks-input {position: absolute;top: 0;left: 0;bottom:0;margin: 0;border:0;width:100%;font-size:12px;text-align:center;background:#f9f6e3;cursor: pointer;}
.tr-total td {
	border-top: 3px solid #232323 !important;
	padding:5px !important;
	text-align:center;
	background:#788c90;
	font-size:12px;
}
.second-th th {
	background:#cad5d7 !important;
	font-weight:normal !important;
}
</style>
<table style="width: 100%" class="sheetmen">
	<thead>
	<tr class="tr-center tr-height tr-bg-blue tr-upper">
		<th colspan="4" style="text-align:center" class="bg-success color-white">INVENTORY</th>
		<th colspan="6" class="bg-warning color-white">DELIVERIES</th>
<?php
	$query = "SELECT cluster FROM tbl_cluster WHERE active=1";
	$results = $db->query($query);			
	while($ROW = mysqli_fetch_array($results))  
	{
		
	
		$cluster = $ROW['cluster'];
		$branch_cnt = $function->CountBranch($cluster,$db);
?>
		<th colspan="<?php echo $branch_cnt; ?>"><?php echo $ROW['cluster']; ?></th>
<?php } ?>
		<th colspan="11" class="bg-info color-white">TOTALS</th>
	</tr>
	</thead>
     <thead>
	<tr class="tr-center tr-value tr-bg-yellow second-th">
		<th style="width:100px">ITEMCODE</th>
		<th>ITEM NAME</th>
		<th style="width:80px !important">UM</th>
		<th>BEGINNING</th>
		<th>WEEK1 (1-7)</th>
		<th>WEEK2 (8-14)</th>
		<th>WEEK3 (15-21)</th>
		<th>WEEK4 (22-28)</th>
		<th>WEEK5 (29-31)</th>
		<th>TOTAL</th>
<?php
	$queryCluster = "SELECT * FROM tbl_cluster WHERE active=1";
	$resultsC = $db->query($queryCluster);			
	while($ROWS = mysqli_fetch_array($resultsC))  
	{
		$ccluster = $ROWS['cluster'];

		$queryBranch = "SELECT * FROM tbl_branch WHERE location='$ccluster'";
		$resultss = $db->query($queryBranch);			
		while($BROW = mysqli_fetch_array($resultss))  
		{

?>
		<th><?php echo $BROW['branch']; ?></th>
<?php } } ?>
		<th class="bg-secondary color-white" style="border:1px solid #fff">TOTAL OUT</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">EXPECTED STOCKS</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">PHY.COUNT</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">VARIANCES</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">UNIT PRICE</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">VARNCE AMT</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">SHORT AMT.</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">OVER AMT.</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff;width:150px;">REMARKS</th>
		<th class="bg-secondary color-white" style="border:1px solid #fff">EXPIRATION DATE</th>
	</tr>
	</thead>
<?php
	$maxLength = 20;	
	$queryItems = "SELECT * FROM wms_itemlist WHERE category='RAWMATS' AND active=1";
	$resultsItems = $db->query($queryItems);			
	$variance_amount_total=0;
	$shortages_total=0;
	$overages_total=0;
	$variances_total=0;	
	$na=0;
	while($ROWDATA = mysqli_fetch_array($resultsItems))  
	{
		$na++;
		$itemcode = $ROWDATA['item_code'];
		$item_name = $ROWDATA['item_description'];
		$beg = $inventory->getDailyBeginning($itemcode,$month,$year,$day,$db);
		$w1 = $inventory->getWeeklyIn($itemcode,'w1',$month,$year,$day,$db);
		$w2 = $inventory->getWeeklyIn($itemcode,'w2',$month,$year,$day,$db);
		$w3 = $inventory->getWeeklyIn($itemcode,'w3',$month,$year,$day,$db);
		$w4 = $inventory->getWeeklyIn($itemcode,'w4',$month,$year,$day,$db);
		$w5 = $inventory->getWeeklyIn($itemcode,'w5',$month,$year,$day,$db);
		
		$v_total = ($beg+$w1+$w2+$w3+$w4+$w5);
?>	
	<tr class="tr-value" title="<?php echo $item_name; ?>">
		<td style="text-align:center"><?php echo $ROWDATA['item_code']?></td>
		<td><?php echo $ROWDATA['item_description']?></td>
		<td style="text-align:center"><?php echo $ROWDATA['uom']?></td>
		<td style="text-align:center"><?php echo $beg; ?></td>
		<td style="text-align:center"><?php echo $w1; ?></td> <!-- WEEK 1 -->
		<td style="text-align:center"><?php echo $w2; ?></td> <!-- WEEK 2 -->
		<td style="text-align:center"><?php echo $w3; ?></td> <!-- WEEK 3 -->
		<td style="text-align:center"><?php echo $w4; ?></td> <!-- WEEK 4 -->
		<td style="text-align:center"><?php echo $w5; ?></td> <!-- WEEK 5 -->
		<td style="text-align:center;width:50px"><?php echo $v_total; ?></td> <!-- TOTAL -->
<?php	
	$queryCluster = "SELECT cluster FROM tbl_cluster WHERE active=1";
	$resultsC = $db->query($queryCluster);
	$grand_total=0;	$nb=0;$clusterData=[];
	while($ROWS = mysqli_fetch_array($resultsC))  
	{
		$clusterData[] = $ROWS;
	}
	foreach ($clusterData as $clusterRows)
	{
		$nb++;
		$cluster = $clusterRows['cluster'];
		$queryBranch = "SELECT branch FROM tbl_branch WHERE location='$cluster'";
		$resultss = $db->query($queryBranch);

		$branchData = []; 
		while ($BROW = mysqli_fetch_assoc($resultss)) {
		    $branchData[] = $BROW; 
		}
		foreach ($branchData as $branchRow)
		{
		    $branch = $branchRow['branch'];
		    $out = $inventory->GetInventoryOut($branch,$itemcode,$year,$month,$day,$db);
			$pcount = $inventory->GetPcount($itemcode,$trans_date,$db);
			$grand_total += $out;
			$exp_stocks = ($v_total-$grand_total);
			$variances = ($pcount - $exp_stocks);
			$variance_amount = ($function->GetUnitPrice($itemcode,$db) * $variances);
			
			$shortages = ($variance_amount < 0) ? $variance_amount : 0;
            $overages = ($variance_amount > 0) ? $variance_amount : 0;
			
			$remarks = $function->GetRemarksDateQuery($itemcode,$trans_date,'remarks',$db);								
?>
		<td style="text-align:center"><?php echo $out; ?></td>
<?php } } 

		$variance_amount_total += $variance_amount;
        $shortages_total += $shortages;
        $overages_total += $overages;
?>
		<td style="text-align:center"><?php echo $grand_total; ?></td>
		<td style="text-align:center"><?php echo $exp_stocks; ?></td>
		<td class="trvalue" data-pcount="<?php echo $na; ?>" style="text-align:center;cursor:pointer;background:#dda3a9;padding:0 !important;position:relative">
			<input id="phycount<?php echo $na; ?>" class="pcount-input" type="text" value="<?php echo $pcount; ?>" disabled>						
			<input id="itemcode<?php echo $na; ?>" type="hidden" value="<?php echo $itemcode; ?>">
		</td>
		<td style="text-align:center"><?php echo $variances; ?></td>
		<td style="text-align:right"><?php echo $function->GetUnitPrice($itemcode,$db); ?></td>
		<td style="text-align:center"><?php echo number_format($variance_amount,2); ?></td>
		<td style="text-align:center"><?php echo number_format($shortages,2); ?></td>
		<td style="text-align:center"><?php echo number_format($overages,2); ?></td>
		<td class="remvalue" data-remarks="<?php echo $na; ?>" style="text-align:center;cursor:pointer;background:#dda3a9;padding:0 !important;position:relative">
			<input id="remarker<?php echo $na; ?>" class="remarks-input" value="<?php echo $remarks ; ?>" disabled>
		</td>
		<td class="expdatevalue" data-remarks="<?php echo $na; ?>" style="text-align:center;cursor:pointer;background:#dda3a9;padding:0 !important;position:relative">
			<input id="expdate<?php echo $na; ?>" class="remarks-input" value="<?php echo $function->GetRemarksDateQuery($itemcode,$trans_date,'expiration_date',$db); ?>" disabled>			
		</td>
	</tr>
<?php } ?>
	<tfoot>
	<tr class="tr-total" style="border-top:3px solid #aeaeae">
		<td colspan="10" style="text-align:center"></td>
<?php
    $queryC = "SELECT cluster FROM tbl_cluster WHERE active=1";
    $resultC = $db->query($queryC);$branchCount=0;
    while($CROW = mysqli_fetch_array($resultC))
    {
        $c = $CROW['cluster'];
        $queryB = "SELECT branch FROM tbl_branch WHERE location='$c'";
        $resultB = $db->query($queryB);
        $branchCount += mysqli_num_rows($resultB);
     }
?>      
        <td colspan="<?php echo $branchCount ; ?>">&nbsp;<?php echo $branchCount ; ?></td>
        <td colspan="5" class="color-white"><strong>GRAND TOTAL</strong></td>
        <td style="background:#728c80;font-weight:600" class="color-white"><?php echo number_format($variance_amount_total,2); ?></td>
        <td style="background:#b5ae9a;font-weight:600" class="color-white"><?php echo number_format($shortages_total,2); ?></td>
        <td style="background:#8fa7ac;font-weight:600" class="color-white"><?php echo number_format($overages_total,2); ?></td>
        <td colspan="2"></td>
	</tr>
	</tfoot>
</table>
<script>
$(function()
{
	$('.expdatevalue').dblclick(function()
	{
		var trans_date = '<?php echo $trans_date; ?>';
		var rem_id = $(this).attr('data-remarks');	
		var expdate = $('#expdate' + rem_id).val();	
		var itemcode = $('#itemcode' + rem_id).val();
		$('#formodalsmtitle').html("EXPIRATION DATE");		
		$.post("./Modules/Warehouse_Management/reporting/expdate_form.php", { itemcode: itemcode, expdate: expdate, trans_date: trans_date, elemid: rem_id },
		function(data) {		
			$('#formodalsm_page').html(data);
			$('#formodalsm').show();
		});
	});
	$('.remvalue').dblclick(function()
	{
		var trans_date = '<?php echo $trans_date; ?>';
		var rem_id = $(this).attr('data-remarks');	
		var remarks = $('#remarker' + rem_id).val();	
		var itemcode = $('#itemcode' + rem_id).val();
		$('#formodalsmtitle').html("REMARKS");		
		$.post("./Modules/Warehouse_Management/reporting/remarks_form.php", { itemcode: itemcode, remarks: remarks, trans_date: trans_date, elemid: rem_id },
		function(data) {		
			$('#formodalsm_page').html(data);
			$('#formodalsm').show();
		});
	});

	$('.trvalue').dblclick(function()
	{
		var trans_date = '<?php echo $trans_date; ?>';
		var button_id = $(this).attr('data-pcount');	
		var phycount = $('#phycount' + button_id).val();	
		var itemcode = $('#itemcode' + button_id).val();
		$('#formodalsmtitle').html("PHYSICAL COUNT");		
		$.post("./Modules/Warehouse_Management/reporting/phycount_form.php", { itemcode: itemcode, phycount: phycount, trans_date: trans_date, elemid: button_id },
		function(data) {		
			$('#formodalsm_page').html(data);
			$('#formodalsm').show();
		});
	});
});
</script>
<?php $db->close(); ?>
