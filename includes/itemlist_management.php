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
.itemlist-page {
	background:#fff;
	border:1px solid #e5e7eb;
	border-radius:10px;
	padding:12px;
	box-shadow:0 2px 8px rgba(15, 23, 42, 0.04);
}
.smnav-header {
	display:flex;
	align-items:center;
	gap:8px;
	flex-wrap:wrap;
	margin-bottom:10px;
}
.smnav-header input[type=text] {
	width:100%;
	padding-left:28px;
	padding-right:30px;
	height:32px;
}
.smnav-header select {
	width:240px;
	height:32px;
}
.action-group {
	display:flex;
	align-items:center;
	gap:8px;
}
.right-actions {
	display:flex;
	align-items:center;
	gap:10px;
	margin-left:auto;
	flex-wrap:wrap;
}
.reload-data {
	display:flex;
	align-items:center;
	gap:8px;
	margin-left:6px;
}
.reload-label {
	font-size:12px;
	color:#475569;
	font-weight:600;
}
.search-shell {
	position:relative;
	width:300px;
}
.search-magnifying {
	position:absolute;
	left:9px;
	top:8px;
	color:#64748b;
	font-size:13px;
}
.search-xmark {
	position:absolute;
	top:5px;
	right:8px;
	font-size:17px;
	cursor:pointer;
	color:#94a3b8;
}
.search-xmark:hover {color:#ef4444;}
.btn-soft {
	border:1px solid #cbd5e1;
	background:#f8fafc;
	color:#334155;
}
.btn-soft:hover {
	background:#eef2f7;
}
.tableFixHead {
	margin-top:10px;
	background:#fff;
	border:1px solid #e5e7eb;
	border-radius:8px;
	overflow:auto;
	height:calc(100vh - 255px);
	width:100%;
}
.tableFixHead table {border-collapse:collapse;}
.tableFixHead th, .tableFixHead td {font-size:14px;white-space:nowrap;}
.loading-shell {
	padding:18px;
	color:#475569;
	font-size:13px;
}
</style>
<div class="itemlist-page">
	<div class="smnav-header">
		<div class="action-group">
			<button class="btn btn-primary btn-sm" onclick="itemsForm('add')"><i class="fa fa-plus"></i>&nbsp;Add Item</button>
			<button class="btn btn-soft btn-sm" onclick="reload_data()"><i class="fa fa-rotate"></i>&nbsp;Reload</button>
		</div>
		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search items or item code">
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
		<select id="category" class="form-control form-control-sm" onchange="selectCategory(this.value)">
			<?php echo $function->GetItemCategory('',$db)?>
		</select>
		<span class="right-actions">
			<button class="btn btn-info btn-sm" onclick="openStoreAppItemMapping()"><i class="fa fa-link"></i>&nbsp;StoreApp Mapping</button>
			<span class="reload-data">
				<span class="reload-label">Show</span>
				<select id="limit" style="width:80px" class="form-control form-control-sm" onchange="load_data()">
					<?php echo $function->GetRowLimit($show_limit); ?>
				</select>
			</span>
		</span>
	</div>
	<div class="tableFixHead" id="smnavdata">
		<div class="loading-shell">Loading data... <i class="fa fa-spinner fa-spin"></i></div>
	</div>
</div>

<script>
window.WMS_ITEM_MASTER_MODULE_CODE = window.WMS_ITEM_MASTER_MODULE_CODE || 'Warehouse_Management';
var ITEM_MASTER_MODULE_CODE = window.WMS_ITEM_MASTER_MODULE_CODE;

function selectCategory(category)
{
	var limit = '';
	if(category != '')
	{
		$.post("./Modules/Warehouse_Management/includes/itemlist_data.php", { limit: limit, category: category, module_code: ITEM_MASTER_MODULE_CODE },
		function(data) {		
			$('#smnavdata').html(data);
		});
	} else {
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
		$.post("./Modules/Warehouse_Management/includes/itemlist_data.php", { limit: limit, search: search, category: category, module_code: ITEM_MASTER_MODULE_CODE },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function reload_data()
{
	$('#' + sessionStorage.navwms).trigger('click');
}
function clearSearch()
{
	$('#search').val('');
	reload_data();
}
function load_data()
{
	var limit = $('#limit').val();
	rms_reloaderOn("Loading data...");
	$.post("./Modules/Warehouse_Management/includes/itemlist_data.php", { limit: limit, module_code: ITEM_MASTER_MODULE_CODE },
	function(data) {
		$('#smnavdata').html(data);
		rms_reloaderOff();
	});
}
function openStoreAppItemMapping()
{
	rms_reloaderOn("Loading StoreApp Item Mapping...");
	$.post("./Modules/Warehouse_Management/includes/itemlist_mapping.php", { },
	function(data) {
		$('#contents').html(data);
		rms_reloaderOff();
	});
}
</script>