<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$mode = $_POST['params'];
$function = new WMSFunctions;
if($_POST['params'] == 'edit')
{
	$rowid = $_POST['rowid'];
	$QUERY = "SELECT * FROM wms_receiving WHERE receiving_id='$rowid'";
	$result = mysqli_query($db, $QUERY );    
    if ( $result->num_rows > 0 ) 
    {
		while($ROW = mysqli_fetch_array($result))  
		{
			$receiving_id = $ROW['receiving_id'];
			$supplier_id = $ROW['supplier_id'];
			$po_no = $ROW['po_no'];
			$si_no = $ROW['si_no'];
			$total_cost = $ROW['total_cost'];
			$created_by = $ROW['created_by'];
			$date_created = $ROW['date_created'];
			$delivery_status = $ROW['delivery_status'];
			$status = $ROW['status'];
		}
    }
}
if($_POST['params'] == 'add')
{
	$receiving_id = "";
	$supplier_id = "";
	$po_no = "";
	$si_no = "";
	$total_cost = "";
	$delivery_status = "Full";
	$status = "Open";
}
echo $q;
?>
<style>
.form-wrapper {width:500px;max-height:500px;overflow-y:auto;}
.table th {font-size:14px !important;}
.item-dd-wrapper {
	position:absolute;
	display: none;
	width:95%;
	padding:5px;
	height:200px;
	font-size:11px;
	background:#fff;
	border-radius:5px;
	box-shadow: 0 0 10px rgba(0, 0, 0, .4);		
}
.search-data {
	max-height: 122px;
	overflow: auto;
}
.searchbtn {
	position:absolute;
	text-align:right;
	padding:5px 0px 5px 0px;
	bottom:0;
	right:5px
}
.searchlist {
	list-style-type: none;
	margin:0;
	padding:0;
}
.searchlist li {
	padding:5px;
	border-bottom:1px solid #aeaeae;
}
</style>
<div class="form-wrapper">	
	<table style="width: 100%" class="table">
		<tr>
			<th>Supplier</th>
			<td>
				<input id="rowid" type="hidden" value="<?php echo $receiving_id; ?>">
				<select id="supplier" class="form-control">
					<?php echo $function->GetSupplier($supplier_id,$db)?>
				</select>
			</td>		
		</tr>
		<tr>
			<th>Purchase Order No.</th>
			<td style="position:relative">
				<input id="po_no" type="text" class="form-control" value="<?php echo $po_no; ?>">
			</td>
		</tr>
		<tr>
			<th>Sales Invoice No.</th>
			<td>
				<input id="sales_invoice" type="text" class="form-control" value="<?php echo $si_no; ?>">
			</td>
		</tr>
		<tr>
			<th>Total Cost</th>
			<td><input id="total_cost" type="number" class="form-control w-100" value="<?php echo $total_cost; ?>"></td>
		</tr>
		<tr>
			<th>Delivery Status</th>
			<td>
				<select id="deliverystatus" class="form-control">
					<?php echo $function->GetDeliverytatust($delivery_status); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Status</th>
			<td>
				<select id="status" class="form-control">
					<?php echo $function->GetOrderStatust($status); ?>
				</select>
			</td>
		</tr>
	</table>
</div>
<div class="results" style="font-size:12px;"></div>
<div style="margin-top:10px;text-align:right">
	<?php if($mode == 'add') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Save Receiving</button>
	<?php } if($mode == 'edit') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Update Receiving</button>
	<button class="btn btn-warning btn-sm" onclick="deleteReceiving('<?php echo $rowid; ?>')">Delete Receiving</button>
	<?php } ?>
	<button class="btn btn-danger btn-sm" onclick="closeModal('formmodal')">Close</button>
</div>
<script>
$(function()
{
	$('#quantity').keyup(function()
	{
		var quantity = parseFloat($('#quantity').val());
		var unitprice = parseFloat($('#unit_price').val());
		var total_cost = quantity * unitprice;
		$('#total_cost').val(total_cost);
	});
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
function setSearch(item,itemcode,unitprice,uom)
{
	$('#item_description').val(item);
	$('#item_code').val(itemcode);
	$('#unit_price').val(unitprice);
	$('#itemddwrapper').slideUp();
	$('#quantity').prop("disabled", false);
	$("#uom option[value='" + uom +"']").attr('selected', 'selected');
}
function deleteItem(rowid)
{
	app_confirm("Delete","Are you sure to delete this Item?","warning","deleteitem",rowid,"red");
	return false;
}
function deleteItemYes(params)
{
	rms_reloaderOn("Deleting...");
	setTimeout(function()
	{
		var limit = $('#limit').val();
		$.post("./Modules/Warehouse_Management/actions/deleteitem_process.php", { rowid: params,limit: limit },
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function validateForm()
{
	var mode = '<?php echo $mode; ?>';
	var limit = $('#limit').val(); 
	var rowid = $('#rowid').val();
	var supplier = $('#supplier').val();
	var po_no = $('#po_no').val(); 
	var sales_invoice = $('#sales_invoice').val(); 
	var total_cost = $('#total_cost').val();
	var deliverystatus = $('#deliverystatus').val();
	var status = $('#status').val();

	if(supplier === '')
	{
		app_alert("Supplier","Please select Supplier","warning","Ok","supplier","focus");
		return false;
	}
	if(po_no === '')
	{
		app_alert("Purchase Request","Please enter PO No.","warning","Ok","po_no","focus");
		return false;
	}
	if(sales_invoice === '')
	{
		app_alert("Sales Invoice","Please enter Sales Invoice No.","warning","Ok","sales_invoice","focus");
		return false;
	}	
	if(total_cost < 0 || total_cost === '')
	{
		app_alert("Total Cost","Please enter Total Cost","warning","Ok","total_cost","focus");
		return false;
	}	
	if(mode == 'add')
	{
		rms_reloaderOn("Saving to Receiving...");
	} 
	if(mode == 'edit')
	{
		rms_reloaderOn("Updating Receiving...");
	} 
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/receiving_process.php", 
		{  
			mode: mode,
			rowid: rowid, 
			limit: limit, 
			supplier: supplier, 
			po_no: po_no, 
			si_no: sales_invoice, 
			total_cost: total_cost, 
			deliverystatus: deliverystatus, 
			status: status 
		},
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function closeItemSearch()
{
	$('#itemddwrapper').slideUp();
}
</script>

