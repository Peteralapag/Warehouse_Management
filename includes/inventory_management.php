<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$currentMonthDays = date('t');
$date_from = date("Y-m-01");
$date_to = date("Y-m-".$currentMonthDays);
$function = new WMSFunctions;
$location = '';
if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '';
}
if(isset($_SESSION['WMS_ITEM_LOCATION']) && $_SESSION['WMS_ITEM_LOCATION'] != '')
{
	$location = $_SESSION['WMS_ITEM_LOCATION'];
}
?>
<style>
.smnav-header input[type=text]{padding-left:25px;padding-right:27px}
.smnav-header select {margin-left: 10px;width:270px;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.date-shell {display: flex;gap: 10px;}
.date-shell input[type=text] {width:140px;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 222px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="smnav-header">
	<div class="search-shell">
		<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Inventory" autocomplete="no">	
		<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
		<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
	</div>
	<div class="search-shell">
		<select id="location" class="form-control form-control-sm">
			<?php echo $function->GetItemLocation($location,$db)?>
		</select>
	</div>
	<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>
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
	$('#location').change(function()
	{
		var limit = $('#limit').val();
		var location = $('#location').val();
		var search = $('#search').val();
		rms_reloaderOn();
		$.post("./Modules/Warehouse_Management/includes/inventory_data.php", { limit: limit, search: search, location: location },
		function(data) {
			$('#smnavdata').html(data);
			rms_reloaderOff();
		});
	});
	$('#search').keyup(function()
	{
		if($('#location').val() != '')
		{
			var limit = '';
			var search = $('#search').val();
			var location = $('#location').val();
		} else {
			var limit = '';
			var search = $('#search').val();
			var location = '';
		}		
		$.post("./Modules/Warehouse_Management/includes/inventory_data.php", { limit: limit, search: search, location: location },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function clearSearch()
{
	$('#search').val('');
	reload_data();
}
function reload_data()
{
	$('#' + sessionStorage.navwms).trigger('click');
}
function load_data()
{
	var limit = $('#limit').val();
	var search = $('#search').val();
	var location = $('#location').val();
	rms_reloaderOn();
	$.post("./Modules/Warehouse_Management/includes/inventory_data.php", { limit: limit, search: search, location: location },
	function(data) {
		$('#smnavdata').html(data);
		rms_reloaderOff();
	});
}
</script>