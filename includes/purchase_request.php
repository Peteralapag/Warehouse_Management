<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$date_from = date("Y-m-01");
$function = new WMSFunctions;
$category = '';

if(isset($_SESSION['WMS_SHOW_LIMIT']))
{
	$show_limit = $_SESSION['WMS_SHOW_LIMIT'];
} else {
	$show_limit = '50';
}

$loadStatus = [
    'pending',
    'approved',
    'rejected',
    'for_canvassing',
    'canvassing_reviewed',
    'canvassing_approved',
    'partial_conversion',
    'converted',
    'convert_rejected'
];

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

		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search purchase request">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
	</span>
	
	<span style="margin-left:10px;margin-top:4px;">Status</span>
	<select id="statusFilter" class="form-control form-control-sm" onchange="load_data()">
	    <option value="">All</option>
	    <?php foreach ($loadStatus as $status): ?>
	        <option value="<?php echo $status; ?>">
	            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
	        </option>
	    <?php endforeach; ?>
	</select>
	
	<button class="btn btn-success btn-sm" onclick="addpurchaserequest()"><i class="fa fa-plus"></i> Add Items</button>
	<span class="reload-data">
		<span style="margin-left:20px;margin-top:4px;">Show</span>
		<select id="limit" style="width:70px" class="form-control form-control-sm" onchange="load_data()">
			<?php echo $function->GetRowLimit($show_limit); ?>
		</select>
	</span>
</div>
<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>


<script>

function addpurchaserequest()
{
	$.post("./Modules/Warehouse_Management/includes/purchase_request_add.php", { },
	function(data) {		
		$('#contents').html(data);

	});
}
$(function()
{
	$('#search').keyup(function()
	{
		
		let filter = this.value.toLowerCase();
	    $('#purchaserequesttable tbody tr').each(function() {
	        let text = $(this).find('td:nth-child(2), td:nth-child(1)').text().toLowerCase();
	        $(this).toggle(text.includes(filter));
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
    var status = $('#statusFilter').val();
    
	$.post("./Modules/Warehouse_Management/includes/purchase_request_data.php", { limit: limit, status: status },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>
