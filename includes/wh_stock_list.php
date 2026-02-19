<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$inventory = new WMSInventory;
?>
<style>
.itemcls th {
	background: green;	
	color:#fff;
}
.itemcls td {
	padding: 3px !important;
	border: 1px solid #aeaeae;
	cursor: pointer;
}
.itemcls tr:hover {
	background:#f1f1f1;
}
.whtransferheader { overflow: auto; height: calc(100vh - 280px); width:100% }
.whtransferheader thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.whtransferheader table  { border-collapse: collapse;}
.whtransferheader th, .whtransferheader td { font-size:14px; white-space:nowrap } 
</style>
<div class="whtransferheader">
<table style="width: 100%" class="table table-striped itemcls">
	<thead>
	<tr>
		<th style="width:40px;text-align:center">#</th>
		<th>ITEM DESCRIPTION</th>
		<th style="width:120px">ONHAND STOCK</th>
		<th style="width:80px">UOM</th>
	</tr>
	</thead>
	<tbody>
<?php
$sqlQuery = "SELECT * FROM wms_inventory_stock";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$i=0;
    	while($ITEMROW = mysqli_fetch_array($results))  
		{
			$i++;
			$item_code = $ITEMROW['item_code'];
?>
	<tr ondblclick="getItemToForm('new','<?php echo $item_code; ?>')">
		<td style="text-align:center"><?php echo $i; ?></td>
		<td><?php echo $ITEMROW['item_description']; ?></td>
		<td style="text-align:right;padding-right:10px"><?php echo $ITEMROW['stock_in_hand']; ?></td>
		<td style="text-align:center"><?php echo $ITEMROW['uom']; ?></td>
	</tr>
<?php }} else { ?>
	</tbody>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
<?php } ?>
</table>
</div>
<script>
function getItemToForm(mode,itemcode)
{
	$.post("./Modules/Warehouse_Management/includes/wh_transfer_form.php", { mode: mode, itemcode: itemcode },
	function(data) {
		$('#conversionform').html(data);
	});
}
</script>