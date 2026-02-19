<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$_SESSION['WMS_INVSUBMENU'] = $_POST['invpage'];
$_SESSION['WMS_ITEMCATEGORY'] = $_POST['category'];
$_SESSION['WMS_ITEMCATEGORY'] = $_POST['category'];
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
if(isset($_SESSION['WMS_YEARS']))
{
	$theyears = $_SESSION['WMS_YEARS'];
} else {
	$theyears = date("Y");	
}
if(isset($_SESSION['WMS_MONTHS']))
{
	$themonths = $_SESSION['WMS_MONTHS'];
} else {
	$themonths = date("m");	
}
if(isset($_SESSION['WMS_DAYS']))
{
	$thedays = $_SESSION['WMS_DAYS'];
} else {
	$thedays = date("d");	
}
$day_cnt = date('t', $themonths);
?>
<style>
.tableFixHeadBody { margin-top: 10px; background: #fff; }
.tableFixHeadBody { overflow: auto; height: calc(100vh - 288px); width: 100%; }
.tableFixHeadBody thead th { position: sticky; z-index: 1;}
.tableFixHeadBody thead:first-child th { top: 0; color:#fff}
.tableFixHeadBody thead:last-child th { top: 50px; color:#232323 !important}
.tableFixHeadBody table  { border-collapse: collapse; }
.tableFixHeadBody th, .tableFixHeadBody td { font-size: 11px; }
.tableFixHeadBody tfoot {position: sticky;bottom: 0;color: #333;}
.tableFixHeadBody tfoot tr {border-top: 3px solid #232323 !important;}
.datenum-parent {position:relative;}
.datenum {position:absolute;height: 200px;border:1px solid red;}
</style>
<div class="subnav-header">
	<select id="cluster" class="form-control form-control-sm" style="width:180px" onchange="setCluster(this.value)">
		<?PHP echo $function->GetCluster($cluster,$db); ?>
	</select>
	<select id="branches" class="form-control form-control-sm" style="width:180px" onchange="setBranch(this.value)"></select>
	<select id="location" class="form-control form-control-sm" style="width:180px;margin-right:10px" onchange="setCluster(this.value)">
		<?PHP echo $function->GetItemLocation($cluster,$db); ?>
	</select>
	<select id="years"  style="width:80px;text-align:center" class="form-control form-control-sm">
		<?php echo $function->GetYear($theyears); ?>
	</select>
	<select id="months" style="width:120px;text-align:center" class="form-control form-control-sm">
		<?php echo $function->GetMonths($themonths); ?>
	</select>
	<select id="days" style="width:80px;text-align:center" class="form-control form-control-sm">
		<?php echo $function->GetDays($thedays,$day_cnt); ?>
	</select>	
	<button class="btn btn-primary btn-sm color-white" onclick="loadDataNow()"><i class="fa-solid fa-file-arrow-down"></i>&nbsp;&nbsp;Load Inventory</button>
	<span style="margin-left:auto">
		<button class="btn btn-danger btn-sm" onclick="inventoryPcount()"><i class="fa-solid fa-tally"></i>&nbsp;&nbsp;Inventory PCount</button>
		<button class="btn btn-success btn-sm" style="width:100px" onclick="GenerateExcelDaily()">EXCEL&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-file-excel"></i></button>	
	</span>
</div>
<div class="tableFixHeadBody" id="sheetdata"><div style="margin-left:10px"></div></div>
<script>
function setBranch(branch)
{
	var cluster = $('#cluster').val();
	var mode = 'setbranch';
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, cluster: cluster, branch: branch },
	function(data) {		
		$('#sheetdata').html(data);
		rms_reloaderOff();
	}); 
}
function setCluster(cluster)
{
	var mode = 'setcluster';
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, cluster: cluster },
	function(data) {		
		$('#sheetdata').html(data);
		rms_reloaderOff();
		loadBranch(cluster);
	}); 
}
function loadBranch(cluster)
{
	var mode = 'loadbranch';
	var branch = '<?php echo $thebranch; ?>';
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, branch: branch, cluster: cluster },
	function(data) {		
		$('#branches').html(data);
		rms_reloaderOff();
	}); 
}
function GenerateExcelDaily()
{
		rms_reloaderOn('Generating Excel...');
		var theyears = $('#years').val();
		var themonths = $('#months').val();
		var thedays = $('#days').val();
		$("#genxcel_page").attr("src", "./Modules/Warehouse_Management/reporting/generate_excel_daily.php?years=" + theyears + "&months=" + themonths + "&days=" + thedays);
		$("#genxcel").show();
		var iframe_document = document.querySelector('#genxcel_page').contentDocument;
	    if (iframe_document.readyState !== 'loading') onLoadingCompleted();
	    else iframe_document.addEventListener('DOMContentLoaded', onLoadingCompleted);
	    function onLoadingCompleted()
	    {
	       	setInterval(function()
			{
			//	$("#genxcel").hide();
				sessionStorage.setItem("excelreport", 0);
				rms_reloaderOff();
			},2000);
	    }
}
function inventoryPcount()
{
	$('#sheetdata').empty();
	var theyears = $('#years').val();
	var themonths = $('#months').val();
	var thedays = $('#days').val();
	var location = $('#location').val();
	rms_reloaderOn('Loading Setup Data...');
	$.post("./Modules/Warehouse_Management/reporting/inventory_setup.php", { theyears: theyears, themonths: themonths, thedays: thedays, location: location },
	function(data) {		
		$('#sheetdata').html(data);
		rms_reloaderOff();
	}); 
}
function loadDataNow()
{
	$('#sheetdata').empty();
	var theyears = $('#years').val();
	var themonths = $('#months').val();
	var thedays = $('#days').val();
	rms_reloaderOn('Loading Data...');
	$.post("./Modules/Warehouse_Management/reporting/inventory_sheet_data.php", { theyears: theyears, themonths: themonths, thedays: thedays },
	function(data) {		
		$('#sheetdata').html(data);
		rms_reloaderOff();
	}); 
}
$(function()
{	
	if(sessionStorage.navinv !== 'null')
	{
		$("#"+sessionStorage.navinv).addClass('btn-warning');
		$("#"+sessionStorage.navinv).trigger('click');
	}
	$('.subnav-header button').click(function()
	{
		var nav_id = $(this).attr('data-navs');
		console.log(nav_id);
		sessionStorage.setItem("navinv",nav_id);
		$('.subnav-header button').removeClass('btn-warning');
		$(this).addClass('btn-primary');
		$("#"+nav_id).addClass('btn-warning');	
	});
	var cluster = $('#cluster').val();
	setCluster(cluster);
});
</script>
