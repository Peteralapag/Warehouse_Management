<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$receiving_id = $_POST['receiving_id'];
$supplier_id = $_POST['supplier_id'];
?>
<style>
.details-wrapper {display: flex;flex-direction: row;height: calc(100vh - 225px);gap: 20px;}
.details-sidebar {width:300px;height:calc(100vh - 226px);}
.details-contents {margin-left: auto;height:calc(100vh - 226px);width: calc(100vw - 500px);padding-top:10px}
</style>
<div class="details-wrapper">
	<div class="details-sidebar" id="detailssidebar"><input type="text" class="form-control"></div>
	<div class="details-contents" id="detailscontents"></div>
</div>
<script>
$(function()
{
	load_dtls_sideform('<?php echo $receiving_id; ?>','<?php echo $supplier_id; ?>');
})
function load_dtls_sideform(rid,sid)
{
	$.post("./Modules/Warehouse_Management/apps/details_form.php", { rid: rid, sid: sid },
	function(data) {		
		$('#detailssidebar').html(data);
		load_dtls_contents(rid,sid);
	});
}
function load_dtls_contents(rid,sid)
{
	$.post("./Modules/Warehouse_Management/includes/details_contents_data.php", { rid: rid, sid: sid },
	function(data) {		
		$('#detailscontents').html(data);
	});
}



</script>