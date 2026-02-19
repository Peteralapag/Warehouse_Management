<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
//$receiving_id = $_POST['receiving_id'];
//$supplier_id = $_POST['supplier_id'];
$rowid = $_POST['rowid'];
?>
<style>
.details-wrapper {display: flex;flex-direction: row;height: calc(100vh - 225px);gap: 20px;}
.details-sidebar {width:300px !important;height:calc(100vh - 226px);overflow-y:auto}
.details-contents {margin-left: auto;height:calc(100vh - 226px);width: calc(100vw - 500px);padding-top:10px}
</style>
<div class="details-wrapper">
	<div class="details-sidebar" id="detailssidebar"></div>
	<div class="details-contents" id="detailscontents"></div>
</div>
<script>
$(function()
{
	load_request_sideform('<?php echo $rowid; ?>');
})
function load_request_sideform(rowid)
{
	$.post("./Modules/Warehouse_Management/apps/create_order_form.php", { rowid: rowid },
	function(data) {		
		$('#detailssidebar').html(data);
		load_order_contents(rowid);
	});
}
function load_order_contents(rowid)
{
	$.post("./Modules/Warehouse_Management/includes/mrs_contents_data.php", { rowid: rowid },
	function(data) {		
		$('#detailscontents').html(data);
	});
}
</script>