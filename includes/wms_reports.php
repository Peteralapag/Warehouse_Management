<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['WMS_REPORT_PAGE']))
{
	$report_page = $_SESSION['WMS_REPORT_PAGE'];
} else {
	$report_page = '';
}
if(isset($_SESSION['WMS_RECIPIENT_REPORT']))
{
	$recipient = $_SESSION['WMS_RECIPIENT_REPORT'];
} else {
	$recipient = 'WAREHOUSE';
}
if(isset($_SESSION['WMS_REPORT_BRANCH']))
{
	$report_branch = $_SESSION['WMS_REPORT_BRANCH'];
} else {
	$report_branch = '';
}
?>
<style>
#branch {
	display: none;
}
</style>
<div class="smnav-header">
	<input type="text" id="branch" class="form-control form-control-sm" style="width:200px" onfocus="openBranch()" placeholder="Select Branch" value="<?php echo $report_branch; ?>" readonly>
	<select id="recipient" class="form-control form-control-sm" style="width:200px">
		<?php echo $function->GetRecipient($recipient,$db); ?>
	</select>
		<select id="reports" class="form-control form-control-sm" style="width:200px" onchange="showBranchInput(this.value)">
		<?php echo $function->GetWMSReports($report_page,$db); ?>
	</select>
	<button class="btn btn-primary btn-sm" onclick="loadInventory()"><i class="fa-solid fa-arrow-down-to-square"></i>&nbsp;&nbsp;Load Report</button>
</div>
<div id="report_results"></div>
<script>
$(function()
{
	const r_page = '<?php echo $report_page; ?>';
	if(r_page === 'Branch Out Deliveries')
	{
		$('#branch').show();
	} else {
		$('#branch').hide();
	}
});
function showBranchInput(report)
{
	if(report == 'Branch Out Deliveries')
	{
		$('#branch').show();
	} else {
		$('#branch').hide();
	}
}
function openBranch()
{
	$('#modaltitle').html("Select Branch");
	$.post("./Modules/Warehouse_Management/wms_report/inventory_branch_select.php", { },
	function(data) {		
		$('#formmodal_page').html(data);	
		$('#formmodal').show();
	});
}
function loadInventory()
{
	var branch = '<?php echo $report_branch; ?>';
	var recipient = $('#recipient').val();
	var reports = $('#reports').val();
	if(recipient == '')
	{
		swal("Recipient", "Please select Recipient", "warning");
		return false;
	}
	else if(reports == '')
	{
		swal("Recipient", "Please select Report", "warning");
		document.getElementById('reports').focus();
		return false;
	} 

	var r_page = $('#reports').val();
	$('#report_results').css("display","");
	if(r_page == 'Inventory Monitoring')
	{
		var _page = 'inventory_report.php';
		$('#report_results').css("display", "");		
	}
	else if(r_page == 'Branch Out Deliveries')
	{
		var _page = 'branch_report.php';
	}
	else if(r_page == 'Summary Report')
	{
		var _page = 'summary_report.php';
	}
	else if(r_page == 'Store Summary Report')
	{
		var _page = 'store_summary_report.php';
	}
	else if(r_page == 'Item Branch Out Report')
	{
		var _page = 'item_branch_out_report.php';
	}
	else if(r_page == 'Transfer Reconciliation Report')
	{
		var _page = 'transfer_reconciliation_report.php';
		$('#report_results').css("display", "flex");
	}
	$.post("./Modules/Warehouse_Management/wms_report/" + _page , { branch: branch, reports: reports, recipient: recipient },
	function(data) {		
		$('#report_results').html(data);	
	});
}
</script>