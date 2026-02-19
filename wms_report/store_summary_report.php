<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.store_functions.php";
$function = new WMSFunctions;
$storeFunction = new WMSStoreFunctions;
$_SESSION['WMS_REPORT_PAGE'] = $_POST['reports'];
$_SESSION['WMS_RECIPIENT_REPORT'] = $_POST['recipient'];
$_SESSION['WMS_BRANCH_REPORT'] = $_POST['branch'];

if(isset($_SESSION['WMS_STORE_TARGET']))
{
	$target = $_SESSION['WMS_STORE_TARGET'];
} else {
	$target = "";
}
if(isset($_SESSION['WMS_YEARS']))
{
	$year = $_SESSION['WMS_YEAR'];
} else {
	$year = date("Y");
}
if(isset($_SESSION['WMS_MONTH']))
{
	$month = $_SESSION['WMS_MONTH'];
} else {
	$month = date("m");
}
?>
<style>
/*.smnav-header input[type=text] {width:100%;padding-left: 25px;padding-right:27px} */
.subpage-wrapper {margin-top:5px;border:1px solid #aeaeae;background:#fff;}
.tableFixHead {margin-top:5px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 272px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:green; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
.subpage-wrapper {display: flex;gap: 5px;white-space:nowrap;border:1px solid #aeaeae;border-bottom: 3px solid #aeaeae;padding:10px;
background: #fff;/*	border-radius: 7px 7px 0px 0px; */min-width:600px;overflow-x:auto;}
</style>
<div class="subpage-wrapper">
	<select id="year" class="form-control form-control-sm" style="width:80px;text-align:center">
		<?php echo $function->GetYear($year); ?>
	</select>
	<select id="month" class="form-control form-control-sm" style="width:100px">
		<?php echo $function->GetMonths($month); ?>
	</select>
	<select id="target" class="form-control form-control-sm" style="width:150px">
		<?php echo $storeFunction->GetReportTarget($target); ?>
	</select>
	<button class="btn btn-secondary btn-sm" onclick="loadReport()"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;Load Report</button>
	
	<span style="margin-left:auto; display:flex;gap:5px">
		<select id="selectElement" class="form form-control form-control-sm" style="width:150px;text-align:center" onchange="clearCache(this.value)">
			<option value=""> --- CLEAR--- </option>
			<option value="current">CLEAR CURRENT</option>
			<option value="all">CLEAR ALL</option>
		</select>
		<button class="btn btn-info btn-sm" style="width:" onclick="generateSummary()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;Generate</button>
		<button class="btn btn-success btn-sm" style="width:100px" onclick="generaTeExcel()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;&nbsp;Excel</button>
	</span>
</div>
<div class="tableFixHead" id="iwd"></div>
<div class="resultas"></div>
<script>
function clearCache(cache)
{
	if(cache == 'current')
	{
		var message = "Are you sure to clear the current cached records?";
	}
	else if(cache == 'all')
	{
		var message = "Are you sure to clear all cached records?";
	} else  {
		return false;
	}
	dialogue_confirm("Clear Records", message, "warning","clearCacheYes",cache,"red");
	return false;
}
function clearCacheYes(cache)
{
	var mode = 'clearcache';
	var year = $('#year').val();
	var month = $('#month').val();
	if(cache == "")
	{
		document.getElementById("selectElement").selectedIndex = 0;
		return false;
	} else {		
		$.post("./Modules/Warehouse_Management/actions/actions_store.php", { mode: mode, cache: cache, year: year, month: month },
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
			document.getElementById("selectElement").selectedIndex = 0;
		});
	}
}

function generateSummary()
{
	var mode = 'generaterawmatsdata';
	var year = $('#year').val();
	var month = $('#month').val();
	var target = $('#target').val();
	$('#iwd').empty();
	rms_reloaderOn('Generating...');
	setTimeout(function()
	{		
		$.post("./Modules/Warehouse_Management/actions/actions_store.php", { mode: mode, target: target, year: year, month: month },
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
		});
	},500);
}
function loadReport()
{
	var year = $('#year').val();
	var month = $('#month').val();
	var target = $('#target').val();
	if(target == '')
	{
		swal("Loading Error", "Please select Report", "warning");
		document.getElementById('target').focus();
		return false;
	}
	else if(target == 'RAWMATS')
	{
		var page = 'store_rawmats_summary_report';
	}
	else if(target == 'FINISH GOODS')
	{
		var page = 'store_fgts_summary_report';
	}
	rms_reloaderOn('Calculating...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/wms_report/" + page + ".php", { target: target, year: year, month: month },
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
		});
	},500);
}
</script>

