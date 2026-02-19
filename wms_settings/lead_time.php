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
		<th>MIN. LEAD TIME</th>
		<th>MAX. LEAD TIME</th>
	</tr>
<?php
	$queryNav = "SELECT * FROM wms_inventory_leadtime";
	$resultsNav = $db->query($queryNav);			
	if ( $resultsNav->num_rows > 0 ) 
    {	
		$a=0;
		while($NAVROWS = mysqli_fetch_array($resultsNav))  
		{
			$a++;
?>	
	<tr>
		<td id="minlt" contenteditable="true" style="text-align:center;"><?php echo $NAVROWS['average_leadtime']; ?></td>
		<td id="maxlt" contenteditable="true" style="text-align:center"><?php echo $NAVROWS['max_leadtime']; ?></td>
	</tr>	
<?php } ?>
		<tr>
		<td contenteditable="false" style="text-align:center;">Days</td>
		<td contenteditable="false" style="text-align:center;">Days</td>
	</tr>
<?php } else { ?>
	<tr>
		<td colspan="2"><i class="fa fa-bell"></i> No Records.</td>
	</tr>
<?php } ?>	
</table>
<div style="margin-top:50px;text-align:center">
	<button class="btn btn-primary btn-update" onclick="UpdateLeadTime('<?php echo $a; ?>')">Update</button>
</div>
<div id="setResults"></div>
<script>
function UpdateLeadTime(aid)
{
	var mode = 'updateleadtime';
	const minlt = document.getElementById('minlt');
	const minValue = minlt.textContent;  
	const maxlt = document.getElementById('maxlt');
	const maxValue = maxlt.textContent;  
	$('.btn-update').html('Updating <i class="fa fa-spinner fa-spin"></i>');
	$('.btn-update').prop('disabled', true);
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, minValue: minValue, maxValue: maxValue },
		function(data) {
			$('#setResults').html(data);
			$('.btn-update').prop('disabled', false);
			$('.btn-update').html('Update');
		});
	},1000);
}
</script>