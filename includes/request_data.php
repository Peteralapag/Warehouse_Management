<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$inventory = new WMSInventory;
$year = date("Y");
$month = date("m");
$min_leadtime = $inventory->GetLeadTime('average_leadtime',$db);
$max_leadtime = $inventory->GetLeadTime('max_leadtime',$db);
$days_count = 31;
if($_POST['limit'] != '')
{
	$limit = "LIMIT ".$_POST['limit'];
} else {
	$limit = "";
}
if(isset($_POST['search']))
{
	$search = $_POST['search'];	
	$q = "WHERE supplier_id LIKE '%$search%' OR item_code LIKE '%$search%' OR item_description LIKE '%$search%'";
} else {
	$q = "ORDER BY supplier_id DESC $limit";
}
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
			<th style="width:130px">BRANCH</th>
			<th style="width:130px">CONTROL No.</th>
			<th style="width:130px">FORM TYPE</th>
			<th>RECIPIENT</th>
			<th style="width:150px">ORDER BY</th>
			<th style="width:80px">ORDER DATE</th>
			<th style="width:70px">STATUS</th>
			<th style="width: 70PX">ACTIONS</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT * FROM wms_order_request";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$i=0;
    	while($ORDERROW = mysqli_fetch_array($results))  
		{
			$i++;$average=0;
			$rowid = $ORDERROW ['request_id'];
?>			
		<tr ondblclick="editRequest('edit','<?php echo $rowid; ?>')">
			<td style="width:50px;text-align:center"><?php echo $i; ?></td>
			<td><?php echo $ORDERROW['branch']; ?></td>
			<td><?php echo $ORDERROW['control_no']; ?></td>
			<td><?php echo $ORDERROW ['form_type']; ?></td>
			<td><?php echo $ORDERROW['recipient']; ?></td>
			<td><?php echo $ORDERROW['created_by']; ?></td>
			<td><?php echo $ORDERROW['trans_date']; ?></td>
			<td style="text-align:center"><?php echo $ORDERROW ['status']; ?></td>
			<td>
				<div class="change-btn btn-warning" onclick="orderDetails('<?php echo $rowid; ?>')">Details</div>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="8" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } ?>			
	</tbody>
</table>
<script>
function editRequest(params,rowid)
{
	$('#modaltitle').html("UPDATE ORDER REQUEST");
	$.post("./Modules/Warehouse_Management/apps/branch_request_form.php", { params: params, rowid: rowid },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
function orderDetails(rowid)
{
	$.post("./Modules/Warehouse_Management/includes/request_details_data.php", { rowid: rowid  },
	function(data) {		
		$('#smnavdata').html(data);
	});	
}
</script>
