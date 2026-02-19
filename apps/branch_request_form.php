<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;
$year = date("Y");
$app_user = $_SESSION['wms_appnameuser'];
$request_type = $_POST['params'];
if(isset($_POST['rowid']))
{
	$rowid = $_POST['rowid'];
	$QUERY = "SELECT * FROM wms_order_request WHERE request_id='$rowid'";
	$result = mysqli_query($db, $QUERY );    
    if ( $result->num_rows > 0 ) 
    {
		while($ROW = mysqli_fetch_array($result))  
		{
			$rowid = $ROW['request_id'];
			$branch = $ROW['branch'];
			$form_type = $ROW['form_type'];
			$recipient = $ROW['recipient'];
			$control_no = $ROW['control_no'];
			$trans_date = $ROW['trans_date'];
			$status = $ROW['status'];
			$priority = $ROW['priority'];
		}
    }
} else {
	$rowid = "";
	$branch = "";
	$form_type = "";
	$recipient = "";
	$control_no = $year."-".$function->GetMRSNumber($db);
	$trans_date = date("Y-m-d");;
	$status = "";
	$priority = "";
}
?>
<style>
.item-dd-wrapper {position:absolute;display: none;width:100%;padding:5px;height:250px;font-size:11px;background:#fff;border-radius:5px;box-shadow: 0 0 10px rgba(0, 0, 0, .4);z-index:999999999}
.form-wrapper {width:400px;height:350px;overflow-y:auto; position:relative}
.table th {font-size:14px !important;}
.item-dd-wrapper {position:absolute;display: none;width:95%;padding:5px;height:200px;font-size:11px;background:#fff;border-radius:5px;box-shadow: 0 0 10px rgba(0, 0, 0, .4);		}
.search-data {max-height: 122px;overflow: auto;}
.searchbtn {position:absolute;text-align:right;padding:5px 0px 5px 0px;bottom:0;right:5px}
.searchlist {list-style-type: none;margin:0;padding:0;}
.searchlist li {padding:5px;border-bottom:1px solid #aeaeae;}
.generate-btn {
	position:absolute;
	width:100%;
	bottom:0;
	text-align:right;
}
</style>
<div class="form-wrapper">
	<table style="width: 100%" class="table">
		<tr>
			<th>MRS Number:</th>
			<td>
				<input id="rowid" type="hidden" value="<?php echo $rowid; ?>">
				<input id="form_type" type="hidden" value="MRS">
				<input id="mrs_no" type="text" class="form-control" value="<?php echo $control_no; ?>" disabled>
			</td>
		</tr>
		<tr>
			<th>Date:</th>
			<td><input id="trans_date" type="date" class="form-control" value="<?php echo $trans_date; ?>"></td>
		</tr>
		<tr>
			<th>Branch:</th>
			<td style="position:relative">
				<input id="branch" type="text" class="form-control form-control-sm searchinput" value="<?php echo $branch; ?>" autocomplete="offers" readonly>
				<div class="item-dd-wrapper" id="itemddwrapper">
					<input id="searchinput" type="text" class="form-control form-control-sm" placeholder="Search Item" autocomplete="offduty">
					<div class="search-data" id="searchdata"></div>
					<div class="searchbtn"><button class="btn btn-danger btn-sm" onclick="closeItemSearch()"><i class="fa-solid fa-xmark"></i> Close</button></div>
				</div>
			</td>
		</tr>
		<tr>
			<th>Recipient:</th>
			<td>
				<select id="recipient" class="form-control">
					<?php echo $function->GetRecipient($recipient,$db); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Created By:</th>
			<td><input id="created_by" type="text" class="form-control" value="<?php echo ucwords(strtolower($app_user)); ?>" disabled></td>
		</tr>
		<tr>
			<th>Priority:</th>
			<td>
				<select id="priority" class="form-control">
					<?php echo $function->GetPriority(""); ?>
				</select>
			</td>
		</tr>
		<tr>
		<th>Request Status:</th>
			<td>
				<select id="status" class="form-control">
					<?php echo $function->GetRequestStatus($status); ?>
				</select>
			</td>
		</tr>
	</table>
	<div class="generate-btn">
	<?php if($request_type == 'new') { ?>
		<button class="btn btn-success" onclick="generateMRS('new')">Generate Request</button>
	<?php } if($request_type == 'edit') { ?>
		<button class="btn btn-info" onclick="generateMRS('edit')">Update Request</button>
	<?php } ?>
		<button class="btn btn-danger" onclick="closeModal('formmodal')">Cancel</button>
	</div>
<div class="resultas"></div>
</div>
<script>
function generateMRS(params)
{
	var rowid = $('#rowid').val();
	var form_type = $('#form_type').val();
	var mrs_no = $('#mrs_no').val();
	var	trans_date = $('#trans_date').val();
	var branch = $('#branch').val();
	var recipient = $('#recipient').val();
	var created_by = $('#created_by').val();
	var priority = $('#priority').val();
	var status = $('#status').val();
	
	if(branch === '')
	{
		app_alert("Branch name","Please select Branch name","warning","Ok","branch","focus");
		return false;
	}
	if(recipient === '')
	{
		app_alert("Recipient","Please select Recipient","warning","Ok","recipient","focus");
		return false;
	}
	if(params == 'new')
	{
		var mode = 'newrequest';
		rms_reloaderOn("Generating...");
	}
	if(params == 'edit')
	{
		var mode = 'updaterequest';
		rms_reloaderOn("Updating...");
	}
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/generate_order_process.php",
		{ 
			mode: mode,
			rowid: rowid,
			form_type: form_type,
			mrs_no: mrs_no, 
			trans_date: trans_date,
			branch: branch,
			recipient: recipient,
			created_by: created_by,
			priority: priority,
			status: status
		},
			function(data) {		
				$('.resultas').html(data);
				rms_reloaderOff();
		});
	},300);
}
$(function()
{
	$('#searchinput').keyup(function()
	{
		var mode = "displaybranch";
		var search = $('#searchinput').val();
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, search: search },
		function(data) {		
			$('#searchdata').html(data);
		});
	});
	$('#branch').focus(function()
	{
		var mode = "displaybranch";		
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode },
		function(data) {		
			$('#searchdata').html(data);
			$('#itemddwrapper').slideDown();
			document.getElementById('searchinput').focus();
		});
	});
});
function setSearch(branch)
{
	$('#branch').val(branch);
	$('#itemddwrapper').slideUp();
}
function closeItemSearch()
{
	$('#itemddwrapper').slideUp();
}

</script>
