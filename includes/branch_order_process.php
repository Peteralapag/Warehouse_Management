<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$currentMonthDays = date('t');
$date = date("Y-m-d");
$control_no = $_POST['control_no'];
$showdtls = 1;
$branch = $function->GetOrderStatus($control_no,'branch',$db);
$trans_date = $function->GetOrderStatus($control_no,'trans_date',$db);
?>
<style>
.nav-button {display: flex;gap: 10px;}
.process-header {display:flex;padding:10px;background: #f1f1f1;border-bottom:2px solid #aeaeae;color:#636363;align-items: center;}
</style>
<div class="process-header">
	<span><strong>Branch:</strong> <?php echo $function->GetOrderStatus($control_no,'branch',$db); ?> | <strong>MRS No.:</strong> <?php echo $control_no; ?> | <strong>Date:</strong> <?php echo date("F d, Y", strtotime($function->GetOrderStatus($control_no,'approved_date',$db))); ?></span>
	<span style="margin-left: auto">
		<button class="btn btn-warning btn-sm" onclick="checkWMSRequest()"><i class="fa-solid fa-bells color-white"></i>&nbsp;&nbsp;Pending Request</button>
		<button class="btn btn-success btn-sm" onclick="loadPickList('<?php echo $control_no; ?>','<?php echo $function->GetOrderStatus($control_no,'trans_date',$db); ?>')"><i class="fa-solid fa-print"></i>&nbsp;&nbsp;Print All Picklist</button>
		<button class="btn btn-secondary btn-sm" onclick="load_data()"><i class="fa-solid fa-arrow-left"></i>&nbsp;&nbsp;Back</button>
	</span>
</div>
<?php if($function->GetOrderStatus($control_no,'logistics',$db) == 0) { $shodtls = 1; ?>
<div style="padding:10px">
	<div class="nav-button">
	<?php if($function->GetOrderStatus($control_no,'order_received',$db) == 0) { $en_recv = ''; } else { $en_recv = 'disabled'; } ?>
		<button <?php echo $en_recv; ?> class="btn btn-success btn-sm" onclick="receivedOrder('<?php echo $control_no; ?>')"><i class="fa-sharp fa-light fa-hands-holding-diamond"></i> Received Order</button>
	<?php if($function->GetOrderStatus($control_no,'order_received',$db) == 1) { ?>
		<button class="btn btn-danger btn-sm" onclick="savePickList('<?php echo $control_no; ?>')"><i class="fa-solid fa-plus color-black"></i> Add to Pick List</button>
	<?php if($function->GetPickCount($branch,$control_no,$db) == 1) { ?>	
		<button class="btn btn-info btn-sm color-white" onclick="loadPickListSingle('<?php echo $control_no; ?>')"><i class="fa-solid fa-print"></i> Print Picklist</button>
		<button class="btn btn-warning btn-sm color-white" onclick="prepareOrder('<?php echo $control_no; ?>')"><i class="fa-solid fa-basket-shopping"></i> Prepare Order</button>
	<?php if($function->GetOrderStatus($control_no,'order_preparing',$db) == 0) { $en_trans = 'disabled'; } else { $en_trans = ''; } 	?>
			<button id="finbtn" <?php echo $en_trans; ?> class="btn btn-primary btn-sm" onclick="createDR('<?php echo $control_no; ?>')"><i class="fa-solid fa-arrow-down-to-square"></i> Finalize Order</button>
	<?php } } ?>
	</div>
</div>
<?php } else { $showdtls = 0; ?>
<div style="padding:10px;border-bottom:1px solid #aeaeae">
	<button id="showdtls" class="btn btn-info btn-sm" onclick="showDTLS()">Show Order List</button>
</div>
<?php } ?>
<div class="order-details" id="orderdetails"></div>
<div class="order-details" id="drdetails">
	
</div>
<div style="padding:10px" id="results"></div>
<script>
function orderProcess(control_no)
{
	$.post("./Modules/Warehouse_Management/includes/branch_order_process.php", { control_no: control_no },
	function(data) {		
		$('#smnavdata').html(data);
	});
}
function checkWMSRequest()
{	
	var recipient = $('#recipient').val();
	$('#modaltitle').html("RE-OPEN ORDER REQUEST");	
	$.post("./Modules/Warehouse_Management/includes/pending_request_data.php", { recipient: recipient, recipient: recipient },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
function loadPickListSingle()
{
	var mode = 'singleprint';
	var transdate = '<?php echo $trans_date; ?>';
	var branch = '<?php echo $branch ?>';
	var control_no = '<?php echo $control_no ?>';
	$('#modaltitlePDF').html("Print Pick Lists");
	$.post("./Modules/Warehouse_Management/includes/pick_list_loader.php", { mode: mode, transdate: transdate, branch: branch, control_no: control_no  },
	function(data) {		
		$('#pdfviewer_page').html(data);
		$('#pdfviewer').show();
	});
}
function loadPickList(controlno,transdate)
{
	$('#modaltitlePDF').html("Print Pick Lists");
	var transdate = '<?php echo $trans_date; ?>';
	$.post("./Modules/Warehouse_Management/includes/pick_list_loader.php", { transdate: transdate, control_no: controlno },
	function(data) {		
		$('#pdfviewer_page').html(data);
		$('#pdfviewer').show();
	});
}
function createDR(controlno)
{
	var mode = 'forwardtologistics';
	var module = sessionStorage.module_name;
	$('#finbtn').prop('disabled', true);
	rms_reloaderOn("Generating Delivery Receipt...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, module: module },
		function(data) {		
			$('#results').html(data);
			rms_reloaderOff();
		});
	},1000);

}
$(function()
{
	if('<?php echo $showdtls; ?>' == 1)
	{
		if('<?php echo $function->GetOrderStatus($control_no,'order_preparing',$db) == 1; ?>')
		{
			var control_no = '<?php echo $control_no; ?>';
			$.post("./Modules/Warehouse_Management/includes/order_details.php", { control_no: control_no },
			function(data) {		
				$('#orderdetails').html(data);
			});
		}
	}
	if('<?php echo $function->GetOrderStatus($control_no,"logistics",$db) == 1; ?>')
	{
		var control_no = '<?php echo $control_no; ?>';
		dr_details(control_no);
	}	
});
function showDTLS()
{
	if('<?php echo $function->GetOrderStatus($control_no,'order_preparing',$db) == 1; ?>')
	{
		var control_no = '<?php echo $control_no; ?>';
		$.post("./Modules/Warehouse_Management/includes/order_details.php", { control_no: control_no },
		function(data) {		
			$('#orderdetails').html(data);
		});
	}
}
function dr_details(control_no)
{
	$.post("./Modules/Warehouse_Management/includes/delivery_details.php", { control_no: control_no },
	function(data) {		
		$('#drdetails').html(data);
	});
}
function prepareOrder(controlno)
{
	var mode = 'preparebranchorder';
	var module = sessionStorage.module_name;
	rms_reloaderOn('Loading Order...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, module: module  },
		function(data) {		
			$('#results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function savePickList(controlno)
{
	rms_reloaderOn("Saving to Pick List");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/picklist_process.php", { control_no: controlno },
		function(data) {		
			$('#results').html(data);
			rms_reloaderOff();
			orderProcess(controlno);
		});
	},500);
}
function receivedOrder(controlno)
{
	var mode = 'receivebranchorder';
	var module = sessionStorage.module_name;
	rms_reloaderOn('Receiving Order...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, module: module },
		function(data) {		
			$('#results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
</script>