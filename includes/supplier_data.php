<?php
include '../../../init.php';
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
	
	$q = "WHERE name LIKE '%$search%' OR id LIKE '%$search%'";
} else {
	$q = "ORDER BY active DESC $limit";
}
?>
<style>
.table td {
	padding:2px 5px 2px 5px !important;
}
.change-btn {
	width:100%;
	padding:2px;
	text-align:center;
	color:#fff;
	border-radius:5px;
	cursor: pointer;
}
</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th>SUPPLIER</th>
			<th>SUPPLIER ADDRESS</th>
			<th>TELEPHONE</th>
			<th>CELLPHONE</th>
			<th>EMAIL</th>
			<th>CONTACT PERSON</th>
			<th>CONTACT No.</th>
			<th>STATUS</th>
			<th>ACTION</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT * FROM wms_supplier $q";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$sp=0;
    	while($SUPPLIERROWS = mysqli_fetch_array($results))  
		{
			$sp++;
			$rowid = $SUPPLIERROWS['id'];
			$address = mb_strimwidth($SUPPLIERROWS['address'], 0, 20, "...");
			if($SUPPLIERROWS['active'] == 1)
			{
				$status = "Active";
				$tdcolor = 'class="color-green"';
			} else {
				$status = "In-Active";
				$tdcolor = 'class="color-red"';
			}
?>
		<tr>
			<td style="text-align:center"><?php echo $sp;?></td>
			<td><?php echo $SUPPLIERROWS['name'];?></td>
			<td><?php echo $address;?></td>
			<td><?php echo $SUPPLIERROWS['telephone'];?></td>
			<td><?php echo $SUPPLIERROWS['cellphone'];?></td>
			<td><?php echo $SUPPLIERROWS['email'];?></td>
			<td><?php echo $SUPPLIERROWS['contact_person'];?></td>
			<td><?php echo $SUPPLIERROWS['person_contact'];?></td>
			<td style="text-align:center" <?php echo $tdcolor; ?>><?php echo $status;?></td>
			<td>
				<div class="change-btn btn-warning" onclick="Check_Access('edit','p_edit',supplierFormEdit)">Edit</div>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="9" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } ?>		
	</tbody>		
</table>
<script>
function supplierFormEdit(params)
{
	var rowid = '<?php echo $rowid; ?>';
	$('#modaltitle').html("UPDATE SUPPLIER");
	$.post("./Modules/Warehouse_Management/apps/supplier_form.php", { params: params, rowid: rowid },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
