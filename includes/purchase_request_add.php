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
			<input id="search" type="text" class="form-control form-control-sm" placeholder="Search Item">	
			<i class="fa-sharp fa-solid fa-magnifying-glass search-magnifying"></i>
			<i class="fa-solid fa-circle-xmark search-xmark" onclick="clearSearch()"></i>
		</div>
	</span>
	
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
	        <i class="fa fa-arrow-left"></i> Back to Main
	    </button>
	</div>
	
	
</div>

<div class="tableFixHead" id="smnavdata">Loading... <i class="fa fa-spinner fa-spin"></i></div>



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
	var limit = $('#limit').val();
	var prnumber = '<?= $prnumber?>';
	var status = '<?= $status?>';
	
	$.post("./Modules/Warehouse_Management/apps/purchase_request_form.php", { limit: limit, prnumber: prnumber, status: status },
	function(data) {
		$('#smnavdata').html(data);
	});
}
</script>