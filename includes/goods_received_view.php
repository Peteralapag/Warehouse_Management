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
.smnav-header input[type=text] {width:100%;padding-left: 25px;padding-right:27px}
.smnav-header select {margin-left: 10px;width:270px;}
.smnav-header{
    display:flex;
    align-items:center;
    gap:10px;
}

.smnav-header .right-actions{
    margin-left:auto; /* <-- mao ni magtulod sa button paingon sa pinakatuo */
}

.pr-info{
	display:flex;
	gap:20px;
	align-items:center;
	font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}

.pr-number,
.pr-destination{
	display:flex;
	align-items:center;
	gap:6px;
	font-size:13px;
	color:#6c757d;
}

.pr-number strong,
.pr-destination strong{
	font-size:15px;
	font-weight:600;
	color:#212529;
	letter-spacing:0.3px;
}

.pr-number i,
.pr-destination i{
	color:#0d6efd;
	font-size:14px;
}



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
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search goods received items">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
	</span>
	
	<div class="pr-info">
		<div class="pr-number">
			<i class="fa-solid fa-file-lines"></i>
			<span>PR #</span>
			<strong><?= $prnumber ?></strong>
		</div>

		<div class="pr-destination">
			<i class="fa-solid fa-file-invoice"></i>
			<span>PO #</span>
			<strong><?= $ponumber ?></strong>
		</div>
		
	</div>
	
	
	<div class="right-actions">
		<button class="btn btn-primary btn-sm" onclick="bactomain()">
			<i class="fa fa-arrow-left"></i> Back to Main
		</button>
	</div>

	
	
</div>

<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>



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
	var rowid = '<?= $rowid?>';
	var ponumber = '<?= $ponumber?>';
	var status = '<?= $status?>';
	var suppliername = '<?= $suppliername?>';

	var limit = $('#limit').val();
	$.post("./Modules/Warehouse_Management/apps/goods_received_view_form.php", { limit: limit, rowid: rowid, ponumber: ponumber, suppliername: suppliername, status: status },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>