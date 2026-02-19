<?php
session_start();
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_TABLE'] = $_POST['table'];
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
	$inv_month = date('m');
}
?>
<style>
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 223px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="tableFixHead" id="tableData">&nbsp;&nbsp;Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>
function loadSubInvStats()
{
	var invpage = $('#subinvstatus').val();
	console.log(invpage);
	var limit = $('#limit').val();
	var category = $('#category').val();
	var months = $('#month').val();
	var year = $('#year').val();
	rms_reloaderOn();
	$.post("./Modules/Warehouse_Management/reporting/"+ invpage +"_report.php", { limit: limit, months: months, year: year, category: category, invpage: invpage },
	function(data) {		
		$('#tableData').html(data);
		rms_reloaderOff();
	});
}
$(function()
{
	var invpage = $('#subinvstatus').val();
	var category = '<?php echo $inv_cat; ?>';
	loadSubInvStats(invpage,category);
});
</script>