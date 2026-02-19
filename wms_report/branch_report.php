<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_REPORT_PAGE'] = $_POST['reports'];
$_SESSION['WMS_RECIPIENT_REPORT'] = $_POST['recipient'];
$_SESSION['WMS_BRANCH_REPORT'] = $_POST['branch'];
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
if(isset($_SESSION['WMS_WEEK']))
{
	$week = $_SESSION['WMS_WEEK'];
} else {
	$week = 1;
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
	<select id="month" class="form-control form-control-sm" style="width:100px;text-align:center">
		<?php echo $function->GetMonths($month); ?>
	</select>
	<span style="margin-left:auto">
		<button class="btn btn-success btn-sm" style="width:100px" onclick="generaTeExcel()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;&nbsp;Excel</button>
	</span>
</div>
<div class="tableFixHead" id="iwd"></div>
<script>
function generaTeExcel()
{
	rms_reloaderOn('Generating Excel...');
	$("#genxcel_page").attr("src", "../reports_data/" + filename + "_excel.php");
	$("#genxcel").show();
	var iframe_document = document.querySelector('#excelreport_page').contentDocument;
    if (iframe_document.readyState !== 'loading') onLoadingCompleted();
    else iframe_document.addEventListener('DOMContentLoaded', onLoadingCompleted);
    function onLoadingCompleted()
    {
       	setInterval(function()
		{
			$("#genxcel").hide();
			sessionStorage.setItem("excelreport", 0);
			rms_reloaderOff();
		},2000);
    }
}
$(function()
{
	$('#week,#month,#year').change(function()
	{
		loadReport();
	});
	loadReport();
});
function loadReport()
{
	var branch = $('#branch').val();
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var page = 'branch_inventory_report_data.php';

	rms_reloaderOn('Loading');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/wms_report/" + page, { recipient: recipient, branch: branch, year: year, month: month },
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
		});
	},500);
}
</script>

