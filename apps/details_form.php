<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;
$receiving_id = $_POST['rid'];
$supplier_id = $_POST['sid'];
if(isset($_POST['mode']) && $_POST['mode'] != '')
{
	$rowid = $_POST['rowid'];
	$receiving_id = $_POST['rid'];
	$supplier_id = $_POST['sid'];
	if($_POST['mode'] == 'edit')
	{
		$sqlQuery = "SELECT * FROM wms_receiving_details WHERE receiving_detail_id='$rowid'";
		$results = mysqli_query($db, $sqlQuery);    
		if ( $results->num_rows > 0 ) 
		{
			while($RECVROW = mysqli_fetch_array($results))  
			{
				$transtype = $RECVROW['transaction_type'];
				$receiving_id = $RECVROW['receiving_id'];
				$supplier_id = $RECVROW['supplier_id'];
				$po_no = $RECVROW['po_no'];
				$si_no = $RECVROW['si_no'];
				$item_code = $RECVROW['item_code'];
				$item_description = $RECVROW['item_description'];
				$category = $RECVROW['category'];
				$quantity = $RECVROW['quantity_received'];
				$uom = $RECVROW['uom'];
				$unit_price = $RECVROW['unit_price'];
				$received_date = $RECVROW['received_date'];
				$expiration_date = $RECVROW['expiration_date'];
			}
		} else {
			echo "No records we're found";
		}
	}
	$mode = "edit"; 
} else {
	$mode = "add";
	$sqlQuery = "SELECT * FROM wms_receiving WHERE receiving_id='$receiving_id'";
	$results = mysqli_query($db, $sqlQuery);    
	if ( $results->num_rows > 0 ) 
	{
		while($RECVROW = mysqli_fetch_array($results))  
		{
			$receiving_id = $RECVROW['receiving_id'];
			$supplier_id = $RECVROW['supplier_id'];
			$po_no = $RECVROW['po_no'];
			$si_no = $RECVROW['si_no'];
		}
	} else {
		echo "No records we're found";
	}
	$rowid = "";
	$transtype = "Receiving";
	$item_code = "";
	$item_description = "";
	$quantity = "0";
	$uom = "";
	$unit_price = "0.00";
	$received_date = date("Y-m-d");
	$expiration_date = "";
	$category = '';
}
$trans_close = $function->closeReceiving($receiving_id,$db);
?>
<style>
.item-dd-wrapper {position:absolute;display: none;width:100%;padding:5px;height:300px;font-size:11px;background:#fff;border-radius:5px;box-shadow: 0 0 10px rgba(0, 0, 0, .4);}
.search-data {max-height: 222px;overflow: auto;}
.searchbtn {position:absolute;text-align:right;padding:5px 0px 5px 0px;bottom:0;right:5px;width:100%;}
.searchlist {list-style-type: none;margin:0;padding:0;}
.searchlist li {padding:5px;border-bottom:1px solid #aeaeae;}
.searchinput {
	background: orange !important;
	color:#fff !important;
}
</style>
<div style="padding:10px" id="transaction_wrapper">
	<table style="width: 100%">
		<tr>
			<th>Transaction Type</th>
		</tr>
		<tr>
			<td>
				<select id="transaction_type" class="form-control form-control-sm" onchange="returnOrder(this.value)">
					<?php echo $function->GetTransType($transtype); ?>
				</select>
			</td>
		</tr>
		<tr>
			<td style="height:20px;"></td>
		</tr>
		<tr>
			<th>Item Code</th>
		</tr>
		<tr>
			<td>
				<input id="item_code" type="text" class="form-control form-control-sm" value="<?php echo $item_code; ?>" disabled>
			</td>
		</tr>
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th>Item Description</th>
		</tr>
		<tr>
			<td style="position:relative">
				<input id="item_description" type="tex" class="form-control form-control-sm searchinput" value="<?php echo $item_description; ?>" autocomplete="offers" readonly>
				<div class="item-dd-wrapper" id="itemddwrapper">
					<input id="searchinput" type="text" class="form-control form-control-sm" placeholder="Search Item" autocomplete="offduty">
					<div class="search-data" id="searchdata"></div>
					<div class="searchbtn"><button class="btn btn-danger btn-sm" onclick="closeItemSearch()"><i class="fa-solid fa-xmark"></i> Close</button></div>
				</div>
			</td>
		</tr>		
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th>Category</th>
		</tr>
		<tr>
			<td>
				<input id="category" type="text" class="form-control form-control-sm" value="<?php echo $category; ?>" disabled>	
			</td>
		</tr>
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th id="transactiontype">Received Quantity</th>
		</tr>
		<tr>
			<td>
				<input id="quantity" type="number" class="form-control form-control-sm" value="<?php echo $quantity; ?>" autocomplete="offduty">
			</td>
		</tr>		
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th>Units of Measure</th>
		</tr>
		<tr>
			<td>
				<select id="uom" class="form-control form-control-sm" disabled>
					<?php echo $function->GetUOM($uom,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th>Unit Price</th>
		</tr>
		<tr>
			<td>
				<input id="unit_price" type="number" class="form-control form-control-sm" value="<?php echo $unit_price; ?>" autocomplete="offduty">
			</td>
		</tr>
		<tr>
			<td style="height:20px"></td>
		</tr>
		<tr>
			<td style="height:5px;border-top:1px solid #aeaeae;"></td>
		</tr>
		<tr>
			<th>Received Date</th>
		</tr>
		<tr>
			<td>
				<input id="received_date" type="date" class="form-control form-control-sm" value="<?php echo $received_date; ?>" autocomplete="offduty">
			</td>
		</tr>
		<tr>
			<td style="height:5px;"></td>
		</tr>
		<tr>
			<th>Expiration Date</th>
		</tr>
		<tr>
			<td>
				<input id="expiration_date" type="date" class="form-control form-control-sm" value="<?php echo $expiration_date; ?>" autocomplete="offduty">
			</td>
		</tr>
		<tr>
			<td style="height:15px;"></td>
		</tr>
		<tr>
			<td style="text-align:center">
			<?php if($mode == 'edit') { ?>
				<button class="btn btn-success w-30" onclick="validateForm('edit')">Update</button>
				<button class="btn btn-warning w-30" onclick="reloadForm('<?php echo $receiving_id; ?>','<?php echo $supplier_id; ?>')">Cancel</button>
				<button class="btn btn-danger w-30" onclick="deleteItem('<?php echo $receiving_id; ?>')">Delete</button>
			<?php } else { ?>	
				<button class="btn btn-success w-100" onclick="validateForm('add')">Save Receiving</button>
			<?php } ?>
			</td>
		</tr>
	</table>
</div>
<div class="validateresults"></div>
<script>
function deleteItem(receiving_id)
{
	console.log(receiving_id);
}
function validateForm(params)
{
	var mode = 'createreceivingdetails';
	var rowid = '<?php echo $rowid; ?>';
	var transaction_type = $('#transaction_type').val();
	var receiving_id = '<?php echo $receiving_id; ?>';
	var supplier_id = '<?php echo $supplier_id; ?>';
	var po_no = '<?php echo $po_no; ?>';
	var si_no = '<?php echo $si_no; ?>';	
	var transaction_type = $('#transaction_type').val();
	var item_code = $('#item_code').val();
	var item_description = $('#item_description').val();
	var category = $('#category').val();
	var quantity = $('#quantity').val();
	var uom = $('#uom').val();
	var unit_price = $('#unit_price').val();
	var received_date = $('#received_date').val();
	var expiration_date = $('#expiration_date').val();
	
	if(item_description === '')
	{
		app_alert("Item Description","Please select Item","warning","Ok","item_description","focus");
		return false;
	}

	if(quantity <= 0 || quantity === '')
	{
		app_alert("Received Quantity","Please enter Quantity","warning","Ok","quantity","focus");
		return false;
	}
	if(unit_price < 0 && unit_price === 0)
	{
		app_alert("Unit Price","Please enter Quantity","warning","Ok","unit_price","focus");
		return false;
	}
	if(expiration_date === '')
	{
		app_alert("Expiration Date","Please select Date","warning","Ok","expiration_date","focus");
		return false;
	}
	if(received_date === '')
	{
		app_alert("Received Date","Please select Date","warning","Ok","received_date","focus");
		return false;
	}
	if(params == 'edit')
	{
		var mode = "createreceivingupdatedetails";
		rms_reloaderOn("Updating...");
	}
	if(params == 'add')
	{
		var mode = "createreceivingdetails";
		rms_reloaderOn("Saving...");
	}
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/receiving_process.php",
		{
			mode: mode,
			rowid: rowid,
			transaction_type: transaction_type,
			receiving_id: receiving_id,
			supplier_id: supplier_id,
			po_no: po_no,
			si_no: si_no,
			transaction_type: transaction_type,
			item_code: item_code,
			item_description: item_description,
			category: category,
			quantity: quantity,
			uom: uom,
			unit_price: unit_price,
			received_date: received_date,
			expiration_date: expiration_date
		},
		function(data) {		
			$('.validateresults').html(data);
			rms_reloaderOff();
		});
	},500);
}
$(function()
{
	if('<?php echo $trans_close; ?>' == 1) 
	{
		$("#transaction_wrapper *").prop("disabled", true);
	} else {
		$("#transaction_wrapper *").prop("disabled", false);
	}
	$('#searchinput').keyup(function()
	{
		var mode = "recevingstocks";
		var search = $('#searchinput').val();
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, search: search },
		function(data) {		
			$('#searchdata').html(data);
		});
	});
	$('#item_description').focus(function()
	{
		var mode = "recevingstocks";		
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode },
		function(data) {		
			$('#searchdata').html(data);
			$('#itemddwrapper').slideDown();
			document.getElementById('searchinput').focus();
		});
	});
});
function returnOrder(value)
{
	if(value == 'Return')
	{
		$('#transactiontype').html("Return Quantity")
	} else if(value = "Receiving")
	{
		$('#transactiontype').html("Received Quantity")
	}
}
function setSearch(item,itemcode,unitprice,uom,category)
{
	$('#item_description').val(item);
	$('#category').val(category);
	$('#item_code').val(itemcode);
	$('#unit_price').val(unitprice);
	$('#itemddwrapper').slideUp();
	$('#quantity').prop("disabled", false);
	$("#uom option[value='" + uom +"']").attr('selected', 'selected');
}
function closeItemSearch()
{
	$('#itemddwrapper').slideUp();
}
function reloadForm(rid,sid)
{
	load_dtls_sideform(rid,sid)
}
</script>

