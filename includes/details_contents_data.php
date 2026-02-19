<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$receiving_id = $_POST['rid'];
$supplier_id = $_POST['sid'];
$received_by = "";
$no_records = 0;
?>
<style>
.table-thth th{background: #838383;color: #fff;font-weight:normal !important}
.table-tdth {text-align:center;padding:4px;font-size:12px;font-weight: 600 !important;border-bottom:3px solid #aeaeae !important;background:#f1f1f1;color:#636363;}
.table td {padding:4px;font-size:12px;font-weight: normal;}
.table-hover:hover {background: #f7f8eb;}
.closeruni {position:absolute;margin-left: auto;top: 9px;right:10px;padding: 0px 6px 0px 6px;border-radius:5px;cursor: pointer;text-align:center;}
.closeruni:hover {background: dodgerblue;color: #fff;}
.transaction-closed {
	font-style:italic;
	border: 1px solid orange;
	padding: 5px;
	background:#fbf6e3;
	border-radius: 5px;
}
</style>
<div style="padding:10px;">
<table style="width: 100%" class="table table-bordered">
	<tr class="table-thth">
		<th colspan="8" style="position:relative">
			<i class="fa-solid fa-warehouse"></i> Supplier: <strong> <?php echo $function->GetSupplierName($supplier_id,$db); ?></strong>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
			<i class="fa-solid fa-note"></i> P.O. No: <strong><?php echo $function->getPOSI($receiving_id,'po_no',$db); ?></strong>
			&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
			<i class="fa-solid fa-note"></i> Invoice No: <strong><strong><?php echo $function->getPOSI($receiving_id,'si_no',$db); ?></strong>
			<div class="closeruni" onclick="load_data()"><i class="fa-solid fa-x"></i></div>
		</th>
	</tr>
	<tr class="table-tdth">
		<th style="width:50px !important;text-align:center">#</th>
		<th style="width:80px !important">Item Code</th>
		<th>Item Description</th>
		<th style="width:60px !important">UOM</th>
		<th style="width:80px !important">Quantity</th>
		<th style="width:80px !important">Unit Price</th>
		<th style="width:80px !important">Total Cost</th>
		<th style="width:150px !important">Expiration Date</th>
	</tr>
<?php
	$sqlQuery = "SELECT * FROM wms_receiving_details WHERE receiving_id='$receiving_id' AND supplier_id='$supplier_id'";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$n=0;
    	$grand_total=0;
    	while($ROW = mysqli_fetch_array($results))  
		{
			$n++;
			$rowid = $ROW['receiving_detail_id'];
			$received_by = $ROW['received_by'];
			$po_number = $ROW['po_no'];
			if($ROW['expiration_date'] != '')
			{
				$expiration_date = date("F m, Y", strtotime($ROW['expiration_date']));
			} else {
				$expiration_date = "-|-";
			}
			if($ROW['total_cost'] >= 0)
			{
				$total_cost = $ROW['total_cost'];
			} else {
				$total_cost = 0;
			}
			
			$grand_total +=  $total_cost;
?>	
	<tr class="table-hover" title="Double click to change" ondblclick="changeDetails('edit','<?php echo $receiving_id; ?>','<?php echo $supplier_id; ?>','<?php echo $rowid; ?>')" style="cursor:pointer">
		<td style="width:50px !important;text-align:center"><?php echo $n; ?></td>
		<td style="text-align:center"><?php echo $ROW['item_code']; ?></td>
		<td><?php echo $ROW['item_description']; ?></td>
		<td><?php echo $ROW['uom']; ?></td>
		<td style="text-align:right !important; padding-right:10px"><?php echo $ROW['quantity_received']; ?></td>
		<td style="text-align:right !important; padding-right:10px"><?php echo $ROW['unit_price']; ?></td>
		<td style="text-align:right !important; padding-right:10px"><?php echo number_format($total_cost,2); ?></td>
		<td style="text-align:center"><?php echo $expiration_date; ?></td>
	</tr>
<?php } ?>
	<tr class="table-tdtd-totals">
		<td colspan="6" style="text-align:right; padding-right:20px"> <strong>Grand Total</strong></td>
		<td style="text-align:right; font-weight:600;padding-right:10px;border-top:3px solid #232323;"><?php echo number_format($grand_total,2);?></td>
		<td></td>
	</tr>
<?php } else { $no_records = 1; ?>	
	<tr>
		<td colspan="7" style="text-align:center" valign="middle"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		<td style="padding:0">
			<button class="btn btn-danger btn-sm w-100">Void</button>
		</td>
	</tr>
<?php } ?>
</table>
<?php if($no_records != 1) {
	if($function->closeReceiving($receiving_id,$db) == 1) { 
?>
<div style="text-align: center" class="transaction-closed">
	This operation has already been finalized.
</div>
	<?php } else {?>
<div style="text-align:right">	
	<button id="closereceivingbtn" class="btn btn-primary" onclick="closeReceiving('<?php echo $receiving_id; ?>')">Close Receiving</button>
</div>
<?php } ?>
<div id="closingdata"></div>
<?php } ?>
<script>
function closeReceiving(rid)
{
	dialogue_confirm("Warning","Are you sure to Close this receiving?","warning","closeReceivingYes",rid,"red");
	return false;
}
function closeReceivingYes(params)
{
	rms_reloaderOn('Closing Receiving...');
	$('#closereceivingbtn').prop('disabled', true);
	$('#closereceivingbtn').html("Closing transaction...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/close_receiving.php", { rid: params },
		function(data) {		
			$('#closingdata').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function changeDetails(mode,receiving_id,supplier_id,rowid)
{
	$.post("./Modules/Warehouse_Management/apps/details_form.php", { mode: mode, rid: receiving_id, sid: supplier_id, rowid: rowid },
	function(data) {		
		$('#detailssidebar').html(data);
	});
}
function returnToBase()
{
	$('#' + sessionStorage.navwms).trigger('click');
}
</script>



