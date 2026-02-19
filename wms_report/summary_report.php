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
if(isset($_SESSION['WMS_REPORT_TARGET']))
{
	$target = $_SESSION['WMS_REPORT_TARGET'];
} else {
	$target = "";
}
if(isset($_SESSION['WMS_REPORT_CLASSES']))
{
	$classes = $_SESSION['WMS_REPORT_CLASSES'];
} else {
	$classes = "";
}
if(isset($_SESSION['WMS_WEEK']))
{
	$week = $_SESSION['WMS_WEEK'];
} else {
	$week = 1;
}
if(isset($_SESSION['WMS_REPORT_CLUSTER']))
{
	$cluster = $_SESSION['WMS_REPORT_CLUSTER'];
} else {
	$cluster = "";
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
.brnselector {
	display: none;
}
</style>
<div class="subpage-wrapper">
	<select id="year" class="form-control form-control-sm" style="width:80px;text-align:center">
		<?php echo $function->GetYear($year); ?>
	</select>
	<select id="month" class="form-control form-control-sm" style="width:100px">
		<?php echo $function->GetMonths($month); ?>
	</select>
	<select id="target" class="form-control form-control-sm" style="width:150px">
		<?php echo $function->GetReportTarget($target); ?>
	</select>
	<select id="cluster" class="form-control form-control-sm brnselector" style="width:160px">
		<?php echo $function->GetReportCluster($cluster,$db); ?>
	</select>
	<select id="classification" class="form-control form-control-sm brnselector" style="width:150px">
		<?php echo $function->GetClassifications($classes,$db); ?>
	</select>
	<button class="btn btn-secondary btn-sm" onclick="loadReport()"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;Load</button>	
	<span style="margin-left:auto">
		<button class="btn btn-warning btn-sm" style="width:100px" onclick="printSummary('<?php echo $target?>')"><i class="fa-solid fa-print color-white"></i>&nbsp;&nbsp;Print</button>
		<!-- button class="btn btn-success btn-sm" style="width:100px" onclick="generaTeExcel()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;Excel</button -->
	</span>
</div>
<div class="tableFixHead" id="iwd"></div>
<script>
function onLoadingCompleted() {
  $("#printpage").hide();
  rms_reloaderOff();
}
function printSummary(target)
{
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var cluster = $('#cluster').val();
	var branch = $('#branch').val();
	var classes = $('#classification').val();

	if ($('#iwd').html().trim() === '')
	{
	    swal("Print Denied","Please load the content before printing.","warning");
	} else {
	    rms_reloaderOn('Loading Print Preview...');
	    $('#modaltitlePPage').html("Print Preview");
	    if(target == 'WAREHOUSES') {
	        $("#printpage_page").attr("src", "./Modules/Warehouse_Management/wms_report/print_warehouse_summary_report.php?recipient=" + recipient + "&month=" + month + "&year=" + year);
	    } else if(target == 'BRANCHES') {
	        $("#printpage_page").attr("src", "./Modules/Warehouse_Management/wms_report/print_branch_summary_report.php?recipient=" + recipient + "&month=" + month + "&year=" + year + "&classes=" + classes + "&cluster=" + cluster + "&branch=" + branch);
	    }
	    $('#printpage').show();
	    var iframe_element = document.querySelector('#printpage_page');
	    if (iframe_element) {
	        iframe_element.onload = onLoadingCompleted;
	    } else {
	        swal('Printing Error','Please load content first','warning','ok');
	    }
	}

}
function generaTeExcel()
{
	rms_reloaderOn('Generating Excel...');
	$("#genxcel_page").attr("src", "./Modules/Warehouse_Management/wms_report/" + filename + "_excel.php");
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
	$('#target').change(function()
	{
		if($('#target').val() === 'BRANCHES')
		{
			$('#branch').show();
			$('.brnselector').show();
		} else {
			$('#branch').hide();
		}
	});
	if($('#target').val() == 'BRANCHES')
	{
		$('.brnselector').show();
	} else {
		$('.brnselector').hide();
	}
	
/*	$('#month,#year,#target,#cluster,#classification').change(function()
	{
		loadReport();
	}); */
//	loadReport();
});
function loadReport()
{
	var branch = $('#branch').val();
	var recipient = $('#recipient').val();
	var year = $('#year').val();
	var month = $('#month').val();
	var target = $('#target').val();
	var cluster = $('#cluster').val();
	var classes = $('#classification').val();
	if(target == 'WAREHOUSES')
	{
		autoLoad(target);
		var page = 'warehouse_summary_report.php';
	}
	else if(target == 'BRANCHES')
	{
		autoLoad(target);
		var page = 'branch_summary_report.php';
	}

	rms_reloaderOn('Loading...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/wms_report/" + page, { 
			recipient: recipient,
			branch: branch,
			year: year,
			month: month,
			target: target,
			cluster: cluster,
			classes: classes
		},
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
		});
	},500);
}
function autoLoad(mode)
{
	if(mode == 'WAREHOUSES')
	{
		$('#branch').hide();
		$('.brnselector').hide();
	}
	else if(mode == 'BRANCHES')
	{
		$('#branch').show();
		$('.brnselector').show();
	}
}
</script>

