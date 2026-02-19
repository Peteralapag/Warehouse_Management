<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$trans_date = $_POST['trans_date'];
$phycount = $_POST['phycount'];
$itemcode = $_POST['itemcode'];
$elemid = $_POST['elemid'];
$itemname = $function->GetAnyQuery('wms_itemlist','item_code',$itemcode,'item_description',$db);
$category = $function->GetAnyQuery('wms_itemlist','item_code',$itemcode,'category',$db);
?>
<style>
.pcount-input-value {position: absolute;top: 0;left: 0;bottom:0;margin: 0;border:0;width:100%;font-size:16px;text-align:center;cursor: pointer;}
</style>
<div style="background:#fff;margin-bottom:10px;">
<table style="width: 100%" class="table">
	<tr>
		<th>ITEM CODE</th>
		<td><?php echo $itemcode?></td>
	</tr>
	<tr>
		<th>ITEM NAME</th>
		<td><?php echo $itemname ?></td>
	</tr>
	<tr>
		<th>PHYSICAL COUNT&nbsp;</th>
		<td style="padding:0 !important;position:relative">
			<input id="pcount" class="pcount-input-value" type="number" value="<?php echo $phycount; ?>" style="left: 2; top: 0; bottom: 1">
		</td>
	</tr>
</table>
<table style="width: 100%;margin-top:20px">	
	<tr>
		<td style="text-align:right">
			<button id="savepcount" class="btn btn-success" onclick="savePcount()">Save Physical Count</button>
			<button class="btn btn-danger" onclick="closeModal('formodalsm')">Close</button>
		</td>
</table>
<div>
<div class="resultes"></div>
<script>
function savePcount()
{
	var elemid = '<?php echo $elemid; ?>';
	var mode = 'savepcount';	
	var transdate = '<?php echo $trans_date; ?>';
	var itemcode = '<?php echo $itemcode; ?>';
	var category = '<?php echo $category; ?>';
	var phycount = $('#pcount').val();	
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, transdate: transdate, itemcode: itemcode, category: category, phycount: phycount },
	function(data) {		
		$('.resultes').html(data);	
		$('#phycount' + elemid).val(phycount);
		closeModal('formodalsm');			
	});	
}
$(function()
{
	document.getElementById('pcount').focus();
	$('#pcount').keydown(function(event) {
        if (event.keyCode === 13) {
        	$('#savepcount').trigger('click');
        }
    });
});
</script>



