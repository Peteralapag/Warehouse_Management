<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_REPORT_PAGE'] = $_POST['reports'];
$_SESSION['WMS_RECIPIENT_REPORT'] = $_POST['recipient'];
$_SESSION['WMS_BRANCH_REPORT'] = $_POST['branch'];
if(isset($_SESSION['WMS_YEAR']))
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
if(isset($_SESSION['WMS_WEEK']))
{
	$week = $_SESSION['WMS_WEEK'];
} else {
	$week = 1;
}
?>
<style>
.subpage-wrapper {margin-top:5px;border:1px solid #aeaeae;background:#fff;}
.tableFixHead {margin-top:5px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 272px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:green; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
.subpage-wrapper {display: flex;gap: 5px;white-space:nowrap;border:1px solid #aeaeae;border-bottom: 3px solid #aeaeae;padding:10px;
background: #fff;min-width:600px;overflow-x:auto;}
</style>
<div class="subpage-wrapper">
	<select id="year" class="form-control form-control-sm" style="width:80px;text-align:center">
		<?php echo $function->GetYear($year); ?>
	</select>
	<select id="month" class="form-control form-control-sm" style="width:100px;text-align:center">
		<?php echo $function->GetMonths($month); ?>
	</select>
	<select id="week" class="form-control form-control-sm" style="width:130px">
		<?php echo $function->GetWeekOfMonth($week); ?>
	</select>
	<button id="loadbtn" class="btn btn-danger btn-sm" onclick="loadReport()">Load Data&nbsp;&nbsp;<i class="fa-solid fa-arrow-down"></i></button>
	<input type="text" id="searchitem" class="form-control form-control-sm" style="width:200px;margin-left:10px" placeholder="Search Item/Code" autocomplete="nonono">
	<button id="searchbtn" class="btn btn-info btn-sm color-white" onclick="searchItem()">Search</button>
	<span style="margin-left:auto">
		<button  class="btn btn-success btn-sm" style="width:100px" onclick="generaTeExcel()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;&nbsp;Excel</button>
	</span>
</div>
<div class="tableFixHead" id="iwd"></div>
<script>
function generaTeExcel()
{
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var week = $('#week').val();

	var query = "/?recipient=" + recipient + "&year=" + year + "&month=" + month + "&week=" + week;
	var pageUrl = "./Modules/Warehouse_Management/wms_report/excel_monthly_inventory_report.php";
	$('#reportxcl').show();
    $("#reportxcl_page").attr("src", pageUrl + query);

    var iframe = $('#reportxcl_page');
    iframe.on('load', function() {
        // RECERVED FOR FUTURE USED
    });
}
function searchItem()
{
	var search = $('#searchitem').val();
	if(search === '')
	{
		swal("Warning","Please enter your search term","warning");
		return false;
	}
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var week = $('#week').val();
	
	var page = 'monthly_inventory_report_data.php';
	rms_reloaderOn('Searching...');
	$.post("./Modules/Warehouse_Management/wms_report/" + page, { recipient: recipient, year: year, month: month, week: week, search: search },
	function(data) {		
		$('#iwd').html(data);
		rms_reloaderOff();
	});
}
$(function()
{
	$('#searchitem').keypress(function(event)
	{
		if (event.which === 13)
		{
			$('#searchbtn').trigger('click');
		}
	});
	$('#week,#month,#year').change(function()
	{
//		loadReport();
	});
});
function loadReport()
{
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var week = $('#week').val();
	if(week == 0)	
	{
		var page = 'monthly_inventory_report_data.php';
	} else {
		var page = 'weekly_inventory_report_data.php';
	}
	rms_reloaderOn('Loading');
	$.post("./Modules/Warehouse_Management/wms_report/" + page, { recipient: recipient, year: year, month: month, week: week },
	function(data) {		
		$('#iwd').html(data);
		rms_reloaderOff();
	});
}
</script>

