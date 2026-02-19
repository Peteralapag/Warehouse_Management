<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
?>
<head>
<meta content="en-us" http-equiv="Content-Language">
<style>
.excel-table {border-collapse: collapse;}
.excel-table td {border: 1px solid #ccc;padding: 5px;min-width: 50px}
.excel-cell {text-align: ;}
.excel-table {width:100%;border-collapse:collapse; background:#fff !important}
.excel-table th {border: 1px solid #aeaeae;padding: 4px !important; padding-left: 10px !important; font-weight:normal !important}
.excel-table td {border: 1px solid #aeaeae;padding: 4px !important; white-space:nowrap}
.action-hover:hover {
	color:red;
	cursor:pointer;
}
</style>
</head>
<table class="excel-table resizable-table table table-bordered table-striped">
	<tr>
		<th style="width:50px;text-align:center">#</th>
		<th style="width:50px">Order</th>
		<th>Navigation Name</th>
		<th>Page Name</th>
		<th>Icon Class</th>
		<th>Active</th>
		<th>Action</th>
	</tr>
<?php
	$queryNav = "SELECT * FROM wms_navigation ORDER BY ordering ASC";
	$resultsNav = $db->query($queryNav);			
	if ( $resultsNav->num_rows > 0 )
    {	
		$a=0;
		while($NAVROWS = mysqli_fetch_array($resultsNav))  
		{
			$a++;
			$rowid = $NAVROWS['id'];
?>	
	<tr>
		<td style="text-align:center"><?php echo $a; ?></td>
		<td id="ordering<?php echo $a; ?>" contenteditable="true" style="text-align:center;"><?php echo $NAVROWS['ordering']; ?></td>
		<td id="menu_name<?php echo $a; ?>" contenteditable="true"><?php echo $NAVROWS['menu_name']; ?></td>
		<td id="page_name<?php echo $a; ?>" contenteditable="true"><?php echo $NAVROWS['page_name']; ?></td>
		<td id="icons<?php echo $a; ?>" contenteditable="true"><?php echo $NAVROWS['icon_class']; ?></td>
		<td id="active<?php echo $a; ?>" contenteditable="true" style="text-align:center"><?php echo $NAVROWS['active']; ?></td>
		<td style="text-align:center"><i class="fa fa-trash color-orange action-hover"></i></td>
	</tr>	
<?php } } else { ?>
	<tr>
		<td colspan="6"><i class="fa fa-bell"></i> No Records.</td>
	</tr>
<?php } ?>	
</table>
<div style="margin-top:10px;text-align:right">
	<button class="btn btn-primary btn-sm" onclick="UpdateNavs('<?php echo $a; ?>')">Update</button>
</div>
<script>
function UpdateNavs(aid)
{
    for (var a = 1; a < aid; a++)
    {
		const order = document.getElementById('ordering' + a);
		const value = order.textContent;  
		var ordering = value;
		console.log(ordering);
    }
}</script>