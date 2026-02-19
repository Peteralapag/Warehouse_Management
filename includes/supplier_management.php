<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}
?>
<style>
.smnav-header input[type=text]
{
	width:270px;
	margin-left: 10px;
}
.smnav-header select {
	width:70px;
}
.reload-data {
	display: flex;
	gap: 20px;
	margin-left: auto;
	right:0;
}
.tableFixHead {
	margin-top:15px;
	background:#fff;
}
.tableFixHead  { overflow: auto; height: calc(100vh - 222px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="smnav-header">
	<button class="btn btn-primary btn-sm" onclick="Check_Access('add','p_add',supplierForm)">Add Supplier</button>
	<button class="btn btn-success btn-sm" onclick="load_data()">List of Supplier</button>
	<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Supplier">
	<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>

function supplierForm(params)
{
	$('#modaltitle').html("ADD SUPPLIER");
	$.post("./Modules/Warehouse_Management/apps/supplier_form.php", { params: params },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
$(function()
{
	$('#search').keyup(function()
	{
		var limit = '';
		var search = $('#search').val();
		$.post("./Modules/Warehouse_Management/includes/supplier_data.php", { limit: limit, search: search },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function load_data()
{
	var limit = $('#limit').val();
	$.post("./Modules/Warehouse_Management/includes/supplier_data.php", { limit: limit },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>