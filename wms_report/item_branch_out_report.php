<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

if(isset($_SESSION['WMS_DATEFROM']))
{
	$dateFrom = $_SESSION['WMS_DATEFROM'];
} else {
	$dateFrom = date("Y-m-d");
}
if(isset($_SESSION['WMS_DATETO']))
{
	$dateTo = $_SESSION['WMS_DATETO'];
} else {
	$dateTo = date("Y-m-d");
}
if(isset($_SESSION['WMS_ITEMBRANCH']))
{
	$itemBranch = $_SESSION['WMS_ITEMBRANCH'];
} else {
	$itemBranch = '';
}
if(isset($_SESSION['WMS_ITEMFILTERS']))
{
	$filters = $_SESSION['WMS_ITEMFILTERS'];
} else {
	$filters = '';
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
.input-wrapper {
	display: flex;
	margin:0;	
	align-items: center; /* Center vertically */
}
.input-wrapper input,.input-wrapper select {
	width:180px;
}
.i-frame {
	display: nones;
}
</style>
<div class="subpage-wrapper">
	<span class="input-wrapper">
		<!-- label>DATE RANGE:::</label>&nbsp; -->
		<input id="dateFrom" type="date" class="form-control form-control-sm" value="<?php echo $dateFrom?>">&nbsp;TO&nbsp;
		<input id="dateTo" type="date" class="form-control form-control-sm" value="<?php echo $dateTo?>">&nbsp;&nbsp;
		<select id="itembranch" class="form-control form-control-sm">
			<?php echo $function->GetBranch($itemBranch,$db); ?>
		</select>&nbsp;
		<select id="filters" class="form-control form-control-sm">
			<?php echo $function->GetItemFilters($filters); ?>
		</select>&nbsp;
		<button class="btn btn-primary btn-sm" onclick="loadReportData()">Load</button>
	</span>
	<span style="margin-left:auto;display: flex">		
		<button class="btn btn-success btn-sm" style="width:100px" onclick="generaTeExcel()"><i class="fa-sharp fa-solid fa-file-excel"></i>&nbsp;&nbsp;&nbsp;Excel</button>
	</span>
</div>
<div class="tableFixHead" id="iwd"></div>
<iframe class="i-frame" id="excelresults" style="width: 335px; height: 183px"></iframe>
<script>
function generaTeExcel() {
    var recipient = $('#recipient').val();
    var dateFrom = $('#dateFrom').val();
    var dateTo = $('#dateTo').val();
    var branch = $('#itembranch').val();
    var filters = $('#filters').val();

    rms_reloaderOn('Loading Report...');
    setTimeout(function() {
        // Create the URL with parameters to send to the PHP script
        var url = "./Modules/Warehouse_Management/wms_report/item_branch_out_report_excel.php" +
                  "?recipient=" + encodeURIComponent(recipient) +
                  "&dateFrom=" + encodeURIComponent(dateFrom) +
                  "&dateTo=" + encodeURIComponent(dateTo) +
                  "&branch=" + encodeURIComponent(branch) +
                  "&filters=" + encodeURIComponent(filters);

        // Set the iframe source to the PHP script URL
        $('#excelresults').attr('src', url);

        rms_reloaderOff();
    }, 500);
}
function loadReportData()
{
	var recipient = $('#recipient').val();
	var dateFrom = $('#dateFrom').val();
	var dateTo = $('#dateTo').val();
	var branch = $('#itembranch').val();
	var filters = $('#filters').val();
	console.log(filters);
	
	rms_reloaderOn('Loading Report...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/wms_report/item_branch_out_report_data.php", {  recipient: recipient, dateFrom: dateFrom, dateTo: dateTo, branch: branch, filters: filters },
		function(data) {		
			$('#iwd').html(data);
			rms_reloaderOff();
		});
	},500);

}
$(document).ready(function()
{

});
</script>

