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
.smnav-header input[type=text] {width:100%;padding-left: 25px;padding-right:27px}
.smnav-header select {margin-left: 10px;width:270px;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.right-actions {display:flex;align-items:center;gap:10px;margin-left:auto;}
.mapping-header {display:flex;align-items:center;margin-bottom:8px;}
.mapping-header-title {font-size:16px;font-weight:600;}
.search-shell {position: relative;margin-left:10px;width:270px;}
.search-magnifying {position:absolute;top:3px;margin-left:5px;}
.search-xmark {position:absolute;top: 1px;right: 5px;font-size:20px;cursor: pointer;}
.search-xmark:hover {color: red;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 222px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap }
</style>

<div class="smnav-header">
	<div class="search-shell">
		<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Items/Products">
		<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
		<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
	</div>
	<select id="category" class="form-control form-control-sm" onchange="selectCategory(this.value)">
		<?php echo $function->GetItemCategory('',$db)?>
	</select>
	<span class="right-actions">
		<button class="btn btn-secondary btn-sm" onclick="backToItemlistManagement()">Back to Itemlist Management</button>
		<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
		</span>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>
function selectCategory(category)
{
	if(category != '')
	{
		$('#limit').prop('disabled', true);
		$.post("./Modules/Warehouse_Management/includes/itemlist_mapping_data.php", { category: category},
		function(data) {		
			$('#smnavdata').html(data);
		});
	} else {
		$('#limit').prop('disabled', false);
		load_data();
	}
}
function itemsForm(params)
{
	$('#modaltitle').html("ADD ITEMS");
	$.post("./Modules/Warehouse_Management/apps/itemlist_form.php", { params: params },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
$(function()
{
	$('#search').keyup(function()
	{
		if($('#category').val() != '')
		{
			var limit = '';
			var search = $('#search').val();
			var category = $('#category').val()
		} else {
			var limit = '';
			var search = $('#search').val();
			var category = '';
		}		
		$.post("./Modules/Warehouse_Management/includes/itemlist_mapping_data.php", { limit: limit, search: search, category: category },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function reload_data()
{
	load_data();
}
function clearSearch()
{
	$('#search').val('');
	reload_data();
}
function load_data()
{
	var limit = $('#limit').val();
	var category = $('#category').val();
	rms_reloaderOn("Loading data...");
	if(category != '')
	{
		$.post("./Modules/Warehouse_Management/includes/itemlist_mapping_data.php", { category: category },
		function(data) {
			$('#smnavdata').html(data);
			rms_reloaderOff();
		});
		return;
	}
	$.post("./Modules/Warehouse_Management/includes/itemlist_mapping_data.php", { limit: limit },
	function(data) {
		$('#smnavdata').html(data);
		rms_reloaderOff();
	});
}
function backToItemlistManagement()
{
	rms_reloaderOn("Loading Itemlist Management...");
	$.post("./Modules/Warehouse_Management/includes/itemlist_management.php", { },
	function(data) {
		$('#contents').html(data);
		rms_reloaderOff();
	});
}
</script>