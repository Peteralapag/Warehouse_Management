<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
if(isset($_POST['category']) AND !isset($_POST['search']))
{
	$category = $_POST['category'];
	$q = "WHERE category='$category'";
} else {
	if($_POST['limit'] != '')
	{
		$limit = "LIMIT ".$_POST['limit'];
	} else {
		$limit = "";
	}
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];	
		if($_POST['category'] != '')
		{
			$category = $_POST['category'];	
			$q = "WHERE item_description LIKE '%$search%' OR item_code LIKE '%$search%' OR qr_code LIKE '%$search%' AND  category='$category'";
		} else {
			$q = "WHERE item_description LIKE '%$search%' OR item_code LIKE '%$search%' OR qr_code LIKE '%$search%'";
		}		
	} else {
		$q = "WHERE active=1 ORDER BY active DESC $limit";
	}
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
			<th>SUPP. ID</th>
			<th>RECIPIENT</th>
			<th>ITEM LOCATION</th>
			<th>ITEM CODE</th>
			<th>CATEGORY</th>
			<th>ITEM DESCRIPTION</th>
			<th>UNIT PRICE</th>
			<th>UOM</th>
			<th>CONVERSION</th>
			<th>ADDED BY</th>
			<th>DATE ADDED</th>
			<th>STATUS</th>			
			<th>MIN. LEADTIME</th>
			<th>MAX. LEADTIME</th>
			<th>ACTION</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT * FROM wms_itemlist $q";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$sp=0;
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$sp++;
			$rowid = $ITEMSROW['id'];
			$item_description = mb_strimwidth($ITEMSROW['item_description'], 0, 40, "...");
			if($ITEMSROW['active'] == 1)
			{
				$status = "Active";
				$tdcolor = 'class="color-green"';
			} else {
				$status = "In-Active";
				$tdcolor = 'class="color-red"';
			}
			if($ITEMSROW['date_added'] != '')
			{
				$date_added = date("F m, Y @h:i A");
			} else {
				$date_added = "--|--";
			}
?>
		<tr ondblclick="itemlistFormEdit('edit','<?php echo $rowid; ?>')">
			<td style="text-align:center"><?php echo $sp;?></td>			
			<td style="text-align:center"><?php echo $ITEMSROW['supplier_id'];?></td>
			<td style="text-align:center"><?php echo $ITEMSROW['recipient'];?></td>
			<td style="text-align:center"><?php echo $ITEMSROW['item_location'];?></td>
			<td style="text-align:center"><?php echo $ITEMSROW['item_code'];?></td>
			<td><?php echo $ITEMSROW['category'];?></td>
			<td><?php echo $item_description;?></td>			
			<td style="text-align:right;padding-right:20px;"><?php echo $ITEMSROW['unit_price'];?></td>
			<td><?php echo $ITEMSROW['uom'];?></td>
			<td><?php echo $ITEMSROW['conversion'];?></td>
			<td><?php echo $ITEMSROW['added_by'];?></td>
			<td><?php echo $date_added;?></td>
			<td style="text-align:center" <?php echo $tdcolor; ?>><?php echo $status;?></td>
			<td style="text-align:center"><?php echo $ITEMSROW['average_leadtime'];?></td>
			<td style="text-align:center"><?php echo $ITEMSROW['max_leadtime'];?></td>
			<td>
				<div class="change-btn btn-warning w-100" onclick="itemlistFormEdit('edit','<?php echo $rowid; ?>')">Edit</div>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="15" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } ?>		
	</tbody>		
</table>
<script>
function itemlistFormEdit(params,rowid)
{
	$('#modaltitle').html("UPDATE ITEMLIST");
	$.post("./Modules/Warehouse_Management/apps/itemlist_form.php", { params: params, rowid: rowid },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
