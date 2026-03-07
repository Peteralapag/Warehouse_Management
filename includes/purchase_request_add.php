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


$prnumber = $_POST['prnumber'] ?? '';
$status = $_POST['status'] ?? '';


$isAddMode = empty($prnumber);   // add items
$isReviseMode = !$isAddMode;     // revise existing PR

$isLocked = false;

if ($isReviseMode) {
    // get real status from DB
    $real_status = '';
    $stmt = $db->prepare("SELECT status FROM purchase_request WHERE pr_number=?");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $stmt->bind_result($real_status);
    $stmt->fetch();
    $stmt->close();

    if ($real_status !== 'pending') {
        $isLocked = true; // lock revise
    }
}



$branches = [];
$res = $db->query("SELECT branch FROM tbl_branch ORDER BY branch");
while($r = $res->fetch_assoc()){
    $branches[] = $r['branch'];
}

$existing_destination = '';
if(!empty($prnumber)){
    $stmt = $db->prepare("SELECT destination_branch FROM purchase_request WHERE pr_number=?");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $stmt->bind_result($existing_destination);
    $stmt->fetch();
    $stmt->close();
}

?>

<style>
.pr-add-page {
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
.smnav-header select {
	height:32px;
	min-width:220px;
	width:auto;
}
#destination_branch {
	flex:1 1 auto;
	min-width:220px;
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
<div class="pr-add-page">
	<div class="smnav-header">
		<div class="search-shell">
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search item">
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
		
		<select id="destination_branch" name="destination_branch" class="form-control form-control-sm" required>
		    <option value="">-- Select Branch Destination --</option>
		    <?php foreach($branches as $b): ?>
		        <option value="<?= htmlspecialchars($b) ?>"
		            <?= ($existing_destination === $b) ? 'selected' : '' ?>>
		            <?= htmlspecialchars($b) ?>
		        </option>
		    <?php endforeach; ?>
		</select>
		
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

	$('#contents').load('./Modules/Warehouse_Management/includes/purchase_request.php');
}

function addpurchaserequest()
{
	$('.modaltitle').html("ADD PURCHASE REQUEST");
	$.post("./Modules/Warehouse_Management/apps/purchase_request_form.php", { },
	function(data) {		
		$('#smnavdata').html(data);

	});
}
$(function()
{
	$('#search').keyup(function()
	{
		
		let filter = this.value.toLowerCase();
	    $('#itemsTable tbody tr').each(function() {
	        let text = $(this).find('td:nth-child(2), td:nth-child(1), td:nth-child(3), td:nth-child(4)').text().toLowerCase();
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
	var limit = $('#limit').length ? $('#limit').val() : '';
	var prnumber = '<?= $prnumber?>';
	var status = '<?= $status?>';
	
	$.post("./Modules/Warehouse_Management/apps/purchase_request_form.php", { limit: limit, prnumber: prnumber, status: status },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>