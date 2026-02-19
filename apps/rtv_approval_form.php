<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_RTV_SEARCH'] = $_POST['rowid'];
$rowid = $_POST['rowid'];
$mode = $_POST['mode'];
if($mode == 'new')
{
	$sqlQueryRTV = "SELECT * FROM wms_receiving_details WHERE receiving_detail_id='$rowid'";
	$RTVresults = mysqli_query($db, $sqlQueryRTV);    
	while($RTVROW = mysqli_fetch_array($RTVresults))  
	{
		$details_id = $RTVROW['receiving_detail_id'];
		$supplier = $RTVROW['supplier_id'];
		$description = $RTVROW['item_description'];
		$quantity_received = $RTVROW['quantity_received'];
		$quantity_deduct = "";
		$remarks = "";
		$approved = 0;
		$status = "";
		$committed = 0;
	}
}
if($mode == 'edit')
{
	$sqlQueryRTV = "SELECT * FROM wms_return_to_vendor WHERE id='$rowid'";
	$RTVresults = mysqli_query($db, $sqlQueryRTV);    
	while($RTVROW = mysqli_fetch_array($RTVresults))  
	{
		$details_id = $RTVROW['details_id'];
		$supplier = $RTVROW['supplier_id'];
		$description = $RTVROW['description'];
		$quantity_received = $RTVROW['quantity_received'];
		$quantity_deduct = $RTVROW['return_quantity'];
		$remarks = $RTVROW['remarks'];
		$approved = $RTVROW['approved'];
		$status = $RTVROW['status'];
		$committed = $RTVROW['committed'];
	}
}
?>
<style>
.remarks {display: flex;height: 120px;border-radius: 5px;border:1px solid #aeaeae;background: #f6f6f6;padding: 5px;margin: 4px 0px 4px 0px}
.editable {min-height: 20px;border: 1px solid #ccc;padding: 5px;display: inline-block;width: 200px;position: relative;}
.editable:empty:before, .qty-deduct:empty:before{content: attr(data-placeholder);color: gray;position: absolute;}
.editable:focus:before, .qty-deduct:focus:before {content: "";}
.qty-deduct {background:#fcf9ec !important;}
.ulo-lamesa th {background: #0091d5;color: #fff;font-size: 14px; }
.ulo-lamesa td {
	white-space:nowrap;
}
</style>
<table style="width: 100%;white-space:nowrap" class="table table-bordered ulo-lamesa">
	<tr>
		<th>SUPPLIER</th>
		<th>DESCRIPTION</th>
		<th>QTY RECEIVED</th>
		<th>QTY TO RETURN</th>
	</tr>
	<tr>
		<td><?php echo $supplier?></td>				
		<td><?php echo $description?></td>
		<td style="text-align:right !important; padding-right:10px"><?php echo $quantity_received; ?></td>
		<td id="qtyDeduct" style="text-align:right;padding-right:10px;" class="qty-deduct" contenteditable="true" data-placeholderdeduct="0"><?php echo $quantity_deduct?></td>
	</tr>
	<tr>
		<td><strong>REMARKS</strong></td>
		<td colspan="3">
			<div id="editableDiv" contenteditable="true" class="remarks" data-placeholder="Enter your remarks"><?php echo $remarks?></div>
		</td>
	</tr>
	<?php 
		if($approved == 1 || $approved == 2)
		{
			if($committed == 1) 
			{
				$com_msg = "And a quantity of ".$quantity_deduct." has been deducted from the inventory.";
			} else {
				$com_msg = "";
			}
	?>	
	<tr>
		<td colspan="4" style="white-space:normal"><?php echo "This request has been " . $status . ". ".$com_msg;?></td>
	</tr>
	<?php }?>
</table>
<div style="text-align:right" id="btnnav">
	<?php 
	if($mode == 'new') { ?>
		<button style="margin-left: auto" class="btn btn-success btn-sm" onclick="submitRTVRequest('<?php echo $rowid?>')">Submit Request&nbsp;<i class="fa-solid fa-arrow-up"></i></button>
	<?php } else { 
		if($approved == 0)		
		{
	?>
		<button class="btn btn-info btn-sm color-white" onclick="updateRTVRequest('<?php echo $rowid?>')" onclick="updateRTVRequest('<?php echo $rowid?>')">Update&nbsp; <i class="fa-solid fa-arrow-up-from-bracket"></i></button>
		<button class="btn btn-primary btn-sm" onclick="approveRTVRequest('<?php echo $rowid?>')">Approve&nbsp; <i class="fa-solid fa-thumbs-up"></i></button>
		<button class="btn btn-danger btn-sm" onclick="denyRTVRequest('<?php echo $rowid?>')">Deny&nbsp; <i class="fa-solid fa-thumbs-down"></i></button>
		<button class="btn btn-warning btn-sm color-white" onclick="closeHeadlessModal()">Delete&nbsp; <i class="fa-solid fa-x"></i></button>
	<?php 
		} 
	}
	if($committed == 0 && $approved == 1)
	{
	?>
		<button class="btn btn-primary btn-sm" onclick="confirmReturn('<?php echo $rowid?>')">Confirm Return&nbsp; <i class="fa-solid fa-arrow-rotate-left"></i></button>
	<?php }?>
		<button class="btn btn-secondary btn-sm" onclick="closeHeadlessModal()">Close&nbsp; <i class="fa-solid fa-circle-xmark"></i></button>
</div>
<div id="rtvresults"></div>
<script>
function confirmReturn(rowid)
{
	rms_reloaderOn('Executing return to vendor...');
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/rtv_process.php", { rowid: rowid },
		function(data) {		
			$('#rtvresults').html(data);
			rms_reloaderOff();
		});
	},500);	
}
function approveRTVRequest(rowid) {
    GetAccess('p_approver', 'Return To Vendor').then((hasAccess) => {
        if (hasAccess) {
            var mode = 'approvertvrequest';	
            rms_reloaderOn('Approving, Please wait...');
            setTimeout(function() {
                $.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid },
                function(data) {		
                    $('#rtvresults').html(data);
                });
            }, 500);
        } else {
            swal('Permission Denied','You do not have permission to approve this request.','error');
        }
    }).catch((error) => {
        swal('Error','An error occurred while checking permissions.','error');
    });
}
function denyRTVRequest(rowid)
{
	disableButton();
	var mode = 'denyrtvrequest';	
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid },
	function(data) {		
		$('#rtvresults').html(data);
		enableButton();
	});
}
function updateRTVRequest(rowid)
{
	var mode = 'updatertvrequest';
	const editableDiv = document.getElementById("editableDiv");
	const qtyDeduct = document.getElementById("qtyDeduct");

	const remarks = editableDiv.innerText;
	const quantity = qtyDeduct.innerText;
	disableButton();
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid, quantity: quantity, remarks: remarks },
	function(data) {		
		$('#rtvresults').html(data);
		enableButton();
	});
}
function submitRTVRequest(rowid)
{
	var mode = 'submitrtvrequest';
	const editableDiv = document.getElementById("editableDiv");
	const qtyDeduct = document.getElementById("qtyDeduct");

	const remarks = editableDiv.innerText;
	const quantity = qtyDeduct.innerText;
	disableButton();
	rms_reloaderOn("Submitting...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid, quantity: quantity, remarks: remarks },
		function(data) {		
			$('#rtvresults').html(data);
			enableButton();
			rms_reloaderOff();
		});
	},1000);
}
function closeHeadlessModal()
{
	$('#formodalsm').hide();
	$('#formodalsm_page').html('');
}
$(document).ready(function() {
	var approved = '<?php echo $approved?>';
	if(approved == 1 || approved == 2)
	{
		document.getElementById("editableDiv").setAttribute("contenteditable", "false");
		document.getElementById("qtyDeduct").setAttribute("contenteditable", "false");
	}
    var editableDiv = $("#editableDiv");
    if (editableDiv.text().trim() === "") {
        editableDiv.text(editableDiv.attr("data-placeholder")).addClass("placeholder");
    }

    editableDiv.on("focus", function() {
        if ($(this).text().trim() === $(this).attr("data-placeholder")) {
            $(this).text("").removeClass("placeholder");
        }
    });

    editableDiv.on("blur", function() {
        if ($(this).text().trim() === "") {
            $(this).text($(this).attr("data-placeholder")).addClass("placeholder");
        }
    });
    // ########################################################
    $(".qty-deduct").on("focus", function() {
        if ($(this).text().trim() === $(this).attr("data-placeholderdeduct")) {
            $(this).text("");
        }
    }).on("blur", function() {
        if ($(this).text().trim() === "") {
            $(this).text($(this).attr("data-placeholderdeduct"));
        }
    });

    $("#getValue").on("click", function() {
        var value = $(".qty-deduct").text().trim();
        if (value === $(".qty-deduct").attr("data-placeholderdeduct")) {
            value = ""; 
        }
        $("#displayValues").text(value);
    });

    if ($(".qty-deduct").text().trim() === "") {
        $(".qty-deduct").text($(".qty-deduct").attr("data-placeholderdeduct"));
    }
});
function disableButton()
{
	$("#btnnav button").prop("disabled", true);
}
function enableButton()
{
	$("#btnnav button").prop("disabled", false);
}
</script>