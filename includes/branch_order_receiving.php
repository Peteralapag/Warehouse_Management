<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$currentMonthDays = date('t');
$date = date("Y-m-d");
$user_level = $_SESSION['wms_userlevel'];
if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '25';
}
if(isset($_SESSION['WMS_ORD']))
{
	$ordd = $_SESSION['WMS_ORD'];
} else {
	$ordd = "Process Order";
}
if(isset($_SESSION['wms_user_recipient']))
{
	if($user_level >= 60)
	{
		$user_recipient = $_SESSION['wms_user_recipient'];
		$admin = 1;			
	} else {
		$admin = 0;
		$user_recipient = $_SESSION['wms_user_recipient'];
	}
	
} else {

	if($user_level >= 60)
	{
		if(isset($_SESSION['wms_user_recipient']))
		{
			$user_recipient = $_SESSION['wms_user_recipient'];
		} else {
			$user_recipient = '';
		}
		$admin = 1;
	} else {
		$admin = 0;
		$user_recipient = 'NOT SET';
	}
}
$ord = '';
?>
<style>
.smnav-header input[type=text]{padding-left:25px;padding-right:27px}
.smnav-header select {margin-left: 10px;width:270px;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.date-shell {display: flex;gap: 5px;}
.date-shell input[type=text] {width:150px !important;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 222px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="smnav-header">
	<div class="search-shell">
		<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Order / Branch">	
		<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
		<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
	</div>
	<span class="date-shell">
	<?php if($admin == 1) { ?>
		<select id="recipient" class="form-control form-control-sm" style="width:190px" onchange="getRecipientData(this.value)">
			<?php echo $function->GetRecipient($user_recipient,$db); ?>
		</select>
	<?php } elseif($admin == 0) { ?>		
		<input id="recipient" style="width:190px" type="text" class="form-control form-control-sm" value="<?php echo $user_recipient; ?>" disabled>
	<?php } ?>
	<span>
		<select id="ord"  style="width:200px" class="form-control form-control-sm" onchange="getRecipientData()">
			<?php echo $function->GetOrderReceiving($ordd); ?>
		</select>
	</span>
	</span>
	<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata"></div>

<script>
function getRecipientData()
{
	var limit = $('#limit').val();
	var recipient = $('#recipient').val();
	var ord = $('#ord').val();
	rms_reloaderOn('Loading...');	
	$.post("./Modules/Warehouse_Management/includes/branch_order_receiving_data.php", { limit: limit, recipient: recipient, ord: ord },
	function(data) {		
		$('#smnavdata').html(data);
		rms_reloaderOff();
	});
}
$(function()
{
	var userlevel = '<?php echo $user_level; ?>';
	var ord = $('#ord').val();
	$('#search').change(function()
	{		
		if($('#recipient').val() != '' || $('#recipient').val() != '')
		{
			var limit = $('#limit').val();
			var search = $('#search').val();
			if(userlevel >= 60)
			{
				var recipient = $('#recipient').val()
			} else {
				var recipient = $('#recipient').val()
			}
		} else {
			swal("Recipient", "Invalid Recipient", "warning");
		}
		rms_reloaderOn('Searching...')
		$.post("./Modules/Warehouse_Management/includes/branch_order_receiving_data.php", { limit: limit, search: search, recipient: recipient, ord: ord },
		function(data) {
			$('#smnavdata').html(data);
			rms_reloaderOff();
		});

	});
	$(document).on('click', '.pagination-link:not(.disabled)', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        loadPage(page);
    });
	load_data(1);
	document.querySelectorAll('.pagination-btn').forEach(button => {
	    button.addEventListener('click', function () {
	        const page = this.getAttribute('data-page');
	        var limit = $('#limit').val();
	        $.post('./Modules/Warehouse_Management/includes/branch_order_receiving_data.php', { limit: limit, page: page }, function (data) {
	            $('#your-table-container').html(data);
	        });
	    });
	});
});
function loadPage(page)
{
	var limit = $('#limit').val();
	var ord = $('#ord').val();
	rms_reloaderOn('Loading...');
    $.post('./Modules/Warehouse_Management/includes/branch_order_receiving_data.php', { page: page, ord: ord, limit: limit }, function(data) {	     
        $('#smnavdata').html(data);       
    });
}
function clearSearch()
{
	$('#search').val('');
	reload_data();
}
function reload_data()
{
	$('#' + sessionStorage.navwms).trigger('click');
}
function load_data()
{
	rms_reloaderOn('Loading...');
	var limit = $('#limit').val();
	var recipient = $('#recipient').val();
	var ord = $('#ord').val();
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/includes/branch_order_receiving_data.php", { limit: limit, recipient: recipient, ord: ord },
		function(data) {
			$('#smnavdata').html(data);
			rms_reloaderOff();
		});
	},500);
}
function orderProcess(control_no)
{
	$.post("./Modules/Warehouse_Management/includes/branch_order_process.php", { control_no: control_no },
	function(data) {		
		$('#smnavdata').html(data);
	});
}
</script>