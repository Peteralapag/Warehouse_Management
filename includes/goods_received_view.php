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

$rowid = $_POST['rowid'] ?? '';
$ponumber = $_POST['ponumber'] ?? '';
$prnumber = $_POST['prnumber'] ?? '';
$suppliername = $_POST['suppliername'] ?? '';
$status = $_POST['status'] ?? '';

?>
<style>
.gr-view-page {
	background:#ffffff;
	border:1px solid #e5e7eb;
	border-radius:10px;
	padding:12px;
	box-shadow:0 2px 8px rgba(15, 23, 42, 0.04);
}
.smnav-header{
	display:flex;
	align-items:center;
	gap:8px;
	flex-wrap:nowrap;
	margin-bottom:10px;
}
.search-shell {
	position:relative;
	width:280px;
	flex:0 0 280px;
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

.pr-info{
	display:flex;
	gap:12px;
	align-items:center;
	padding:4px 10px;
	background:#f8fafc;
	border:1px solid #e2e8f0;
	border-radius:8px;
	flex:1 1 auto;
	min-width:0;
}

.pr-number,
.pr-destination{
	display:flex;
	align-items:center;
	gap:6px;
	font-size:12px;
	color:#64748b;
}

.pr-number strong,
.pr-destination strong{
	font-size:14px;
	font-weight:700;
	color:#0f172a;
}

.pr-number i,
.pr-destination i{
	color:#0d6efd;
	font-size:13px;
}

.right-actions{
	margin-left:auto;
	flex:0 0 auto;
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
<div class="gr-view-page">
	<div class="smnav-header">
		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search goods received items">
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
		
		<div class="pr-info">
			<div class="pr-number">
				<i class="fa-solid fa-file-lines"></i>
				<span>PR #</span>
				<strong><?= htmlspecialchars($prnumber) ?></strong>
			</div>

			<div class="pr-destination">
				<i class="fa-solid fa-file-invoice"></i>
				<span>PO #</span>
				<strong><?= htmlspecialchars($ponumber) ?></strong>
			</div>
		</div>
		
		<div class="right-actions">
			<button class="btn btn-primary btn-sm" onclick="bactomain()">
				<i class="fa fa-arrow-left"></i>&nbsp;Back to Main
			</button>
		</div>
	</div>

	<div class="tableFixHead" id="smnavdata">
		<div class="loading-shell">Loading data... <i class="fa fa-spinner fa-spin"></i></div>
	</div>
</div>



<script>
function bactomain(){

	$('#contents').load('./Modules/Warehouse_Management/includes/goods_received.php');
}


$(function()
{
	$('#search').keyup(function()
	{
		
		let filter = this.value.toLowerCase();
	    $('#itemsTable tbody tr').each(function() {
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
	const rowid = <?= json_encode((string)$rowid) ?>;
	const prnumber = <?= json_encode((string)$prnumber) ?>;
	const ponumber = <?= json_encode((string)$ponumber) ?>;
	const status = <?= json_encode((string)$status) ?>;
	const suppliername = <?= json_encode((string)$suppliername) ?>;

	const limit = $('#limit').length ? $('#limit').val() : '';
	$.post("./Modules/Warehouse_Management/apps/goods_received_view_form.php", { limit, rowid, prnumber, ponumber, suppliername, status },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>