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
.pr-page {
	background:#ffffff;
	border:1px solid #e5e7eb;
	border-radius:10px;
	padding:12px;
	box-shadow:0 2px 8px rgba(15, 23, 42, 0.04);
}
.smnav-header {
	display:flex;
	align-items:center;
	gap:8px;
	flex-wrap:wrap;
	margin-bottom:10px;
}
.search-shell {
	position:relative;
	width:320px;
}
.smnav-header input[type=text] {
	width:100%;
	padding-left:28px;
	padding-right:30px;
	height:32px;
}
.search-magnifying {
	position:absolute;
	left:9px;
	top:8px;
	font-size:13px;
	color:#64748b;
}
.search-xmark {
	position:absolute;
	top:5px;
	right:8px;
	font-size:17px;
	color:#94a3b8;
	cursor:pointer;
}
.search-xmark:hover {color:#ef4444;}
.smnav-header select {
	height:32px;
	width:230px;
}
.status-label,
.reload-label {
	font-size:12px;
	font-weight:600;
	color:#475569;
	margin:0 2px;
}
.toolbar-right {
	display:flex;
	align-items:center;
	gap:10px;
	margin-left:auto;
	flex-wrap:wrap;
}
.reload-data {
	display:flex;
	align-items:center;
	gap:8px;
}
.btn-soft {
	border:1px solid #cbd5e1;
	background:#f8fafc;
	color:#334155;
}
.btn-soft:hover {
	background:#eef2f7;
}
.tableFixHead {
	margin-top:10px;
	background:#fff;
	border:1px solid #e5e7eb;
	border-radius:8px;
	overflow:auto;
	height:calc(100vh - 255px);
	width:100%;
}
.loading-shell {
	padding:18px;
	font-size:13px;
	color:#475569;
}
</style>
<div class="pr-page">
	<div class="smnav-header">
		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search purchase request">
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>

		<span class="status-label">Status</span>
		<select id="statusFilter" class="form-control form-control-sm" onchange="load_data()">
		    <option value="">All</option>
		    <?php foreach ($loadStatus as $status): ?>
		        <option value="<?php echo $status; ?>">
		            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
		        </option>
		    <?php endforeach; ?>
		</select>

		<div class="toolbar-right">
			<button class="btn btn-success btn-sm" onclick="addpurchaserequest()"><i class="fa fa-plus"></i>&nbsp;Add Items</button>
			<button class="btn btn-soft btn-sm" onclick="load_data()"><i class="fa fa-rotate"></i>&nbsp;Reload</button>
			<span class="reload-data">
				<span class="reload-label">Show</span>
				<select id="limit" style="width:80px" class="form-control form-control-sm" onchange="load_data()">
					<?php echo $function->GetRowLimit($show_limit); ?>
				</select>
			</span>
		</div>
	</div>
	<div class="tableFixHead" id="smnavdata">
		<div class="loading-shell">Loading data... <i class="fa fa-spinner fa-spin"></i></div>
	</div>
</div>


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
