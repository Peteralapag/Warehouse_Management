<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_POST['rtvstatus']))
{
	$_SESSION['WMS_RTV_STATUS'] = $_POST['rtvstatus'];
}
$status = $_POST['rtvstatus'];
$sqlQuery = "SELECT * FROM wms_return_to_vendor WHERE status='$status' ORDER BY requested_date DESC";
$results = mysqli_query($db, $sqlQuery);    
?>
<style>
.OtherFixHead {background:#fff;overflow: auto; height: calc(100vh - 280px) !important; width:100% }
.OtherFixHead thead th { position: sticky; top: 0; z-index: 1; background:#00d9ff; color:#fff; padding: 5px !important }
.OtherFixHead table  { border-collapse: collapse;}
.OtherFixHead th, .OtherFixHead td { font-size:14px; white-space:nowrap; vertical-align:middle !important} 
</style>
<div class="OtherFixHead">
	<table style="width: 100%" class="table table-bordered">
		<thead>
			<tr>
				<th style="width:60px !important;text-align:center">#</th>				
				<th>SUPPLIER</th>
				<th>PO NUMBER</th>
				<th>DESCRIPTION</th>
				<th>QUANTITY</th>
				<th>REQUESTED BY</th>
				<th>REQUESTED DATE</th>
				<th>APPROVED BY</th>
				<th>APPROVED DATE</th>
				<th>STATUS</th>
				<th>ACTIONS</th>
			</tr>
		</thead>			
		<tbody>
<?PHP
    if ( $results->num_rows > 0 ) 
    {
    	$sp=0;
    	while($DATAROWS = mysqli_fetch_array($results))  
		{
			$sp++;
			$status = $DATAROWS['status'];
			$rowid = $DATAROWS['id'];
?>		
			<tr>
				<td style="width:60px !important;text-align:center;font-weight:600">&nbsp;<?php echo $sp?>&nbsp;</td>
				<td><?php echo $function->GetSupplierName($DATAROWS['supplier_id'],$db) ?></td>				
				<td style="text-align:center"><?php echo $DATAROWS['po_no']?></td>
				<td><?php echo $DATAROWS['description']?></td>
				<td style="text-align:right;padding-right:10px !important;"><?php echo $DATAROWS['quantity_received']?></td>
				<td><?php echo $DATAROWS['requested_by']?></td>
				<td style="text-align:center">
				<?php 
					if (!empty($DATAROWS['requested_date'])) {
				        echo date("M. d, Y @h:i A", strtotime($DATAROWS['requested_date']));
				    }
				?>
				</td>
				<td><?php echo $DATAROWS['approved_by'];?></td>
				<td>
				<?php 
					if (!empty($DATAROWS['approved_date']) && $DATAROWS['approved_date'] != '0000-00-00 00:00:00') {
				        echo date("M. d, Y @h:i A", strtotime($DATAROWS['approved_date']));
				    }
				?>
				</td>
				<td style="text-align:center"><?php echo $DATAROWS['status'];?></td>
				<td style="padding:1px !important">
					<?php if($status == 'Pending') {?>
					<button class="btn btn-primary btn-sm" onclick="requestRTVApproveEdit('<?php echo $rowid?>')">Approved/Edit</button>
					<?php } else if($status == 'Approved' || $status == 'Rejected') {?>
					<button class="btn btn-secondary btn-sm w-100" onclick="requestRTVApproveEdit('<?php echo $rowid?>')">View</button>
					<?php } ?>
				</td>
			</tr>
<?php } } else { ?>			
			<tr>
				<td colspan="11" style="text-align:center"><i class="fa fa-bell color-orange"></i> NO RECORDS</td>
			</tr>
<?php } ?>			
		</tbody>
	</table>
</div>	
<script>
function requestRTVApproveEdit(rowid)
{
	var supplier = $('#searchrtv').val();
	var mode = 'edit';
	rms_reloaderOn('Searching...')
	$('#formodalsmtitle').html("RETURN TO VENDOR REQUEST");
	$.post("./Modules/Warehouse_Management/apps/rtv_approval_form.php", { mode: mode, rowid: rowid },
	function(data) {		
		$('#formodalsm_page').html(data);
		$('#formodalsm').show();
		rms_reloaderOff();
	});
}
</script>
