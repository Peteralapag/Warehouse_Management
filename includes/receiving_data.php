<?php
include '../../../init.php';
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$_SESSION['WMS_SHOW_LIMIT'] = $_POST['limit'];
if($_POST['limit'] != '')
{
	$limit = "LIMIT ".$_POST['limit'];
} else {
	$limit = "";
}
if(isset($_POST['search']))
{
	$search = $_POST['search'];	
	$q = "WHERE po_no LIKE '%$search%' OR si_no LIKE '%$search%' OR created_by LIKE '%$search%'";	
} else {
	$q = "ORDER BY (status = 'open') DESC, receiving_id DESC $limit";
}


if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = $_POST['limit'];
	$_SESSION['WMS_SHOW_LIMIT'] = $_POST['limit'];
}
$limit = "LIMIT ".$show_limit;
?>
<style>
.table td {
	padding:2px 5px 2px 5px !important;
}
</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th>SUPPLIER</th>
			<th>P.O. No.</th>
			<th>SALES INV. No.</th>
			<!--th>TOTAL COST</th-->
			<!--th>DELVR. STATUS</th-->
			<th>RCVNG. STATUS</th>
			<th>DATE CREATED</th>
			<th>STATUS</th>
			<th>POSTED BY</th>
			<th>ACTIONS</th>
		</tr>
	</thead>
	<tbody>	
<?PHP
	$sqlQuery = "SELECT * FROM wms_receiving $q";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$n=0;
    	while($RECVROW = mysqli_fetch_array($results))  
		{
			$n++;
			$total_cost = number_format($RECVROW['total_cost'],2);
			$receiving_id = $RECVROW['receiving_id'];
			$supplier_id = $RECVROW['supplier_id'];
			if($RECVROW['status'] == 'Closed')
			{
				$stats_bg = 'class="bg-danger color-white"';
			} else {
				$stats_bg = '';
			}
?>	
		<tr>
			<td style="text-align:center"><?php echo $n; ?></td>
			<td><?php echo $function->GetSupplierName($supplier_id,$db); ?></td>
			<td><?php echo $RECVROW['po_no']; ?></td>
			<td><?php echo $RECVROW['si_no']; ?></td>
			<!--td><?php echo $total_cost; ?></td-->
			<!--td><?php echo $RECVROW['delivery_status']; ?></td-->
			<td><?php echo $RECVROW['receiving_status']; ?></td>
			<td><?php echo $RECVROW['date_created']; ?></td>
			<td <?php echo $stats_bg; ?> style="text-align:center"><?php echo $RECVROW['status']; ?></td>
			<td><?php echo $RECVROW['created_by']; ?></td>
			<td style="width:120px">
				<div class="change-btn btn-warning" onclick="viewDetails('<?php echo $receiving_id; ?>','<?php echo $supplier_id; ?>')">Details</div>
			</td>
		</tr>
<?php } } else { ?>
		<tr>
			<td colspan="9" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?php } ?>
	</tbody>
</table>

<script>
function viewDetails(receiving_id,supplier_id)
{
	$.post("./Modules/Warehouse_Management/includes/recieving_details_data.php", { receiving_id: receiving_id, supplier_id: supplier_id },
	function(data) {		
		$('#smnavdata').html(data);
	});		
}
</script>
