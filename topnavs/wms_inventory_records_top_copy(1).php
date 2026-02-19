<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
/* ##################################################################################################### */ 
$tableName = $_POST['table'];
$_SESSION['WMS_TABLE'] = $tableName;
if(isset($_SESSION['WMS_INVSUBMENU']))
{
	$inv_submenu = $_SESSION['WMS_INVSUBMENU'];
} else {
	$inv_submenu = 'inventory_status';
}
?>
<style>
.reload-data{margin-left: auto;}
.calendar-date {border:1px solid red;height:10px;width:100px}
#subinvstatus {border:1px solid #ff5100;}
#category {border:1px solid #0090ff;}
</style>
<div class="search-shell" style="margin-right:10px;width:180px;position:relative">
	<select id="subinvstatus" style="width:180px" class="form-control form-control-sm" onchange="loadSubInvStats(this.value)">
		<?php echo $function->getWMSSubReport($tableName,$inv_submenu,$db); ?>
	</select>
</div>
<div class="search-shell" style="margin-right:10px;width:200px">	
	<select id="category" style="width:200px" class="form-control form-control-sm" onchange="selectCategory(this.value)">
		<?php echo $function->GetItemCategory('',$db)?>
	</select>
</div>
<div class="search-shell">
	<div class="btn-group" role="group" aria-label="Ronan Sarbon">
		<!-- button class="btn btn-secondary btn-sm" style="width:100px" onclick="">GENERATE</button -->
		<button class="btn btn-danger btn-sm" style="width:100px" onclick="">PDF&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-file-pdf pull-right"></i></button>
		<button class="btn btn-success btn-sm" style="width:100px" onclick="GenerateExcel('<?php echo $tableName; ?>')">EXCEL&nbsp;&nbsp;&nbsp;<i class="fa-solid fa-file-excel"></i></button>	
	</div>
</div>
<span class="reload-data">
	<span style="margin-left:20px;margin-top:4px;">Show</span>
	<select id="limit" style="width:70px;text-align:center !important" class="form-control form-control-sm showmodule" onchange="loadSubInvStats('<?php echo $inv_submenu; ?>')">
		<?php echo $function->GetRowLimit(); ?>
	</select>
</span>
<script>
function GenerateExcel(table)
{
	if(sessionStorage.stringKo === undefined || sessionStorage.stringKo === null)
	{
		swal("Select Column", "Please select at least one column to generate", "warning");
	} else {
		rms_reloaderOn('Generating Excel...');
		let resultString = removeIdFromString(sessionStorage.stringKo,'id');
		$("#genxcel_page").attr("src", "./Modules/Warehouse_Management/reporting/generate_excel.php?table=" + table + "&columns=" + resultString);
		$("#genxcel").show();
		var iframe_document = document.querySelector('#genxcel_page').contentDocument;
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
}
function removeIdFromString(str) {
	return str.replace(/\bid\b,?/, '');
}
</script>