<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}
?>
<style>
.smnav-header input[type=text] {width:100%;padding-left: 25px;padding-right:27px}
.smnav-header select {margin-left: 10px;width:270px;}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.tableFixHead {margin-top:15px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 222px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="smnav-header">
	<span style="display:flex;gap:10px">
		<button class="btn btn-primary btn-sm" onclick="ReceiveForm('add')">Receive Order</button>
		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Supplier">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
	</span>
	<span class="reload-data">
		<span>
			<button class="btn btn-secondary btn-sm" onclick="returnToVendor('p_view','Return To Vendor')">Return to Vendor</button>
		</span>
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>

<script>
function returnToVendor(permission, access) {
    GetAccess(permission, access).then(hasAccess => {
        if (hasAccess) {
            showReturnToVendor();
        } else {
            swal("Access Denied", "You have insufficient access. Please contact System Administrator", "warning");
        }
    }).catch(error => {
        swal("Error", "An error occurred while checking permissions. Please try again.", "error");
    });
}
function showReturnToVendor()
{
	$.post("./Modules/Warehouse_Management/includes/return_to_vendor.php", {  },
	function(data) {		
		$('#smnavdata').html(data);
	});
}
function ReceiveForm(params)
{
	$('.modaltitle').html("ADD RECEIVING");
	$.post("./Modules/Warehouse_Management/apps/receiving_form.php", { params: params },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
$(function()
{
	$('#search').keyup(function()
	{
		if($('#search').val() != '')
		{
			var limit = '';
			var search = $('#search').val();
		} else {
			var limit = $('#limit').val();
			var search = $('#search').val();
		}		
		$.post("./Modules/Warehouse_Management/includes/receiving_data.php", { limit: limit, search: search },
		function(data) {
			$('#smnavdata').html(data);
		});

	});
	load_data();
});
function clearSearch()
{
	$('#search').val('');
	load_data();
}
function load_data()
{
	var limit = $('#limit').val();
	$.post("./Modules/Warehouse_Management/includes/receiving_data.php", { limit: limit },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>