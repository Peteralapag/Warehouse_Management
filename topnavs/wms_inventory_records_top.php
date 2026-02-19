<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$inventory = new WMSInventory;
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
if(isset($_SESSION['WMS_ITEMCATEGORY']))
{
	$inv_cat = $_SESSION['WMS_ITEMCATEGORY'];
} else {
	$inv_cat = '';
}
if(isset($_SESSION['WMS_MONTH']))
{
	$inv_month = $_SESSION['WMS_MONTH'];
} else {
	$inv_month = date("m");
}
if(isset($_SESSION['WMS_YEAR']))
{
	$inv_year = $_SESSION['WMS_YEAR'];
} else {
	$inv_year = date("Y");
}
$columns = implode(",", $inventory->getColumns($tableName,$db));
?>
<style>
.reload-data{margin-left: auto;}
.calendar-date {border:1px solid red;height:10px;width:100px}
#subinvstatus {border:1px solid #ff5100;}
#category {border:1px solid #0090ff;}
#month { border: 1px solid orange }
#year { border: 1px solid #232323 }
.monthlyreport {
	display: nones;
}
</style>
<div class="search-shell" style="margin-right:10px;width:180px;position:relative">
	<select id="subinvstatus" style="width:180px" class="form-control form-control-sm" onchange="loadSubInvStats(this.value)">
		<?php echo $function->getWMSSubReport($tableName,$inv_submenu,$db); ?>
	</select>
</div>
<div class="search-shell" style="margin-right:10px;width:200px">	
	<select id="category" style="width:200px" class="form-control form-control-sm" onchange="loadSubInvStats()">
		<?php echo $function->GetItemCategory($inv_cat,$db);?>
	</select>
</div>
<div class="search-shell monthlyreport" style="margin-right:10px;width:80px">	
	<select id="year" style="width:80px;text-align:center" class="form-control form-control-sm" onchange="loadSubInvStats()">
		<?php echo $function->GetYear($inv_year);?>
	</select>
</div>
<div class="search-shell monthlyreport" style="margin-right:10px;width:130px">	
	<select id="month" style="width:130px" class="form-control form-control-sm" onchange="loadSubInvStats()">
		<?php echo $function->GetMonths($inv_month);?>
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
$(function()
{
  
});;
function GenerateExcel(table)
{
	sessionStorage.setItem("stringKo", '<?php echo $columns; ?>');
	if(sessionStorage.stringKo === undefined || sessionStorage.stringKo === null)
	{
		swal("Select Column", "Please select at least one column to generate", "warning");
	} else {
		rms_reloaderOn('Generating Excel...');
		let limit = $('#limit').val();
		let month = $('#month').val();
		let year = $('#year').val();
		let category = $('#category').val();
		let resultString = removeIdFromString(sessionStorage.stringKo,'id');
		
		if($('#subinvstatus').val() == 'inventory_status')
		{
			console.log(table);
			$("#genxcel_page").attr("src", "./Modules/Warehouse_Management/reporting/generate_excel_inventory_status.php?table=" + table + "&columns=" + resultString + "&month=" + month + "&year=" + year + "&limit=" + limit + "&category=" + category);
		}
		else if($('#subinvstatus').val() == 'inventory_monthly')
		{
			$("#genxcel_page").attr("src", "./Modules/Warehouse_Management/reporting/generate_excel_inventory_monthly.php?table=" + table + "&columns=" + resultString + "&month=" + month + "&year=" + year + "&limit=" + limit + "&category=" + category);
		}
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
}
function removeIdFromString(str) {
	return str.replace(/\bid\b,?/, '');
}
</script>