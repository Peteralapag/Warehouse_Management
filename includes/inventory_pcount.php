<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$module = 'Inventory Physical Count';
$username = $_SESSION['wms_username'];
$userlevel = $_SESSION['wms_userlevel'];
$permission = 'p_edit';
if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}
if(isset($_SESSION['WMS_CLUSTER']))
{
	$cluster = $_SESSION['WMS_CLUSTER'];
	if(isset($_SESSION['WMS_BRANCH']))
	{
		$thebranch = $_SESSION['WMS_BRANCH'];
	} else {
		$thebranch = '';
	}
} else {
	$cluster = '';
	$thebranch = '';
}
if(isset($_SESSION['WMS_ITEM_LOCATION']))
{
	$location = $_SESSION['WMS_ITEM_LOCATION'];
} else {
	$location = "";
}
if(isset($_SESSION['WMS_TRANSDATE']))
{
	$trans_date = $_SESSION['WMS_TRANSDATE'];
} else {
	$trans_date = date("Y-m-d");
}
$WMSmaintenance = WMS_MAINTENANCE;
?>
<style>
.smnav-header input[type=text]{width:270px;margin-left: 10px;}
.smnav-header select {width:70px;}
.reload-data {display: flex;gap: 20px;margin-left: auto;right:0;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHeadPcount { overflow: auto; height: calc(100vh - 208px); width:100% }
.tableFixHeadPcount thead th { position: sticky; top: 0; z-index: 1; background:#e6e6e6; color:#000; font-weight:normal }
.tableFixHeadPcount table  { border-collapse: collapse;}
.tableFixHeadPcount th, .tableFixHeadPcount td { font-size:14px; white-space:nowrap } 
.clear-icon {position:absolute;right:6px;top: 0;font-size: 22px;cursor: pointer;}
.clear-icon:hover {color: red;}
.search-icon {position:absolute;top: 0;left:6px;font-size: 22px;cursor: pointer;	}
#search {padding-right:30px;padding-left:30px;}
</style>
<div class="subnav-header">
	<span style="position:relative">
		<input id="search" type="text" class="form-control form-control-sm" style="width: 300px" placeholder="Search Itemcode, Item name">
		<i class="fa-solid fa-magnifying-glass search-icon color-dodger"></i>
		<i class="fa-solid fa-circle-xmark clear-icon" onclick="clearSearch()"></i>
	</span>
	<select id="location" class="form-control form-control-sm" style="width:200px;margin-right:10px" onchange="load_pcount()">
		<?PHP echo $function->GetItemLocation($location,$db); ?>
	</select>
	<input id="transdate" type="date" class="form-control form-control-sm" style="width:150px" value="<?php echo $trans_date; ?>" onchange="load_pcount()">
	<button id="phybutton" class="btn btn-primary btn-sm color-white" onclick="setPcount()"><i class="fa-solid fa-file-arrow-down"></i>&nbsp;&nbsp;Open PhyCount</button>
	<span class="reload-data">
	<?php if($function->GetModulePermission($username,$userlevel,$module,$permission,$db) == 0) { ?>
		<button class="btn btn-danger btn-sm" style="cursor:default" disabled><i class="fa-solid fa-eye"></i>&nbsp;&nbsp;Read Only</button>
	<?php } ?>
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px;text-align:center" class="form-control form-control-sm" onchange="load_pcount()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHeadPcount">
	<div id="phycountdata"></div>
</div>
<div id="pcountresults"></div>
<script>
function setPcount()
{
	var mode = 'setmaintenance';
	if ($('#phybutton').text().trim() == 'Open PhyCount') 
	{
		$('#phybutton').text('Close PhyCount');
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, maintenance: 1 },
		function(data) {		
			$('#pcountresults').html(data);
			rms_reloaderOff();
		});	
	} else {
		$('#phybutton').text('Open PhyCount');
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, maintenance: 0 },
		function(data) {		
			$('#pcountresults').html(data);
			rms_reloaderOff();
		});	
	}
}
function clearSearch()
{
	$('#search').val('');
	load_pcount();
}
function load_pcount()
{
	var limit = $('#limit').val();
	var cluster = $('#cluster').val();
	var branch = $('#branches').val();
	var location = $('#location').val();
	var trans_date = $("#transdate").val();	
	rms_reloaderOn('Loading...');
	$.post("./Modules/Warehouse_Management/includes/phycount_data.php", { location: location, trans_date: trans_date, limit: limit },
	function(data) {		
		$('#phycountdata').html(data);
		rms_reloaderOff();
	}); 
}
$(document).ready(function()
{
	if('<?php echo $WMSmaintenance?>' == 1)
	{
		$('#phycountdata').show();
		$('.maintenance').hide();
		$('#phybutton').text('Close PhyCount');
	} else {	
		$('#phycountdata').hide();
		$('.maintenance').show();
		$('#phybutton').text('Open PhyCount');
	}
	$('#search').keyup(function()
	{
		var search = $('#search').val();
		var limit = $('#limit').val();
		var cluster = $('#cluster').val();
		var branch = $('#branches').val();
		var location = $('#location').val();
		var trans_date = $("#transdate").val();
		$.post("./Modules/Warehouse_Management/includes/phycount_data.php", { search: search, location: location, trans_date: trans_date, limit: limit },
		function(data) {		
			$('#phycountdata').html(data);
			rms_reloaderOff();
		});	
	});
	load_pcount();		
});
</script>