<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$rowid = '';
if($_POST['mode'] == 'new')
{
	$theMode = 'new';
	if(isset($_POST['itemcode']))
	{
		$itemcode = $_POST['itemcode'];
		$sqlQuery = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
		$results = mysqli_query($db, $sqlQuery);    
		if ( $results->num_rows > 0 ) 
		{
			while($RECVROW = mysqli_fetch_array($results))  
			{
				$item_code = $RECVROW['item_code'];
				$item_description = $RECVROW['item_description'];
				$category = $RECVROW['category'];
				$stock = $RECVROW['stock_in_hand'];
				$on_hand_uom = $RECVROW['uom'];
			}
		} else {
			echo "No records we're found";
		}
	}
	$rowid = '';
	$searchItem = '';
	$convert_amount = 0;
	$convert_uom = '-|-';
	$takeout_amount = 0;
	$takeout_uom = '';
	$on_hand_before = 0;
	$amount_per_uom = 0;
	$amoun_uom = '';
	$status = '';
}
if($_POST['mode'] == 'edit')
{
	$theMode = 'edit';
	$row_id = $_POST['rowid'];
	$sqlQuery = "SELECT * FROM wms_warehouse_transfer WHERE id='$row_id'";
	$results = mysqli_query($db, $sqlQuery);    
	if ( $results->num_rows > 0 ) 
	{
		while($RECVROW = mysqli_fetch_array($results))  
		{
			$rowid = $RECVROW['id'];
			$item_code = $RECVROW['item_code'];
			$category = $RECVROW['category'];
			$item_description = $function->GetItemName($item_code,$db);
			$searchItem = $RECVROW['item_description'];
			$stock = $RECVROW['on_hand_before'];
			$on_hand_uom = $RECVROW['on_hand_uom'];
			$takeout_amount = $RECVROW['takeout_amount'];
			$takeout_uom = $RECVROW['takeout_uom'];
			$transfer_amount = $RECVROW['transfer_amount'];
			$transfer_uom = $RECVROW['transfer_uom'];
			$amount_per_uom = $RECVROW['amount_per_uom'];
			$amount_uom = $RECVROW['amount_uom'];
			$transfer_uom = $RECVROW['transfer_uom'];
			$convert_amount = $RECVROW['convert_amount'];
			$convert_uom = $RECVROW['convert_uom'];
			$status = $RECVROW['status'];
		}
	} else {
		echo "No records we're found";
	}
	if($status == 'Open')
	{
		$btn_attr = '';
	}
	else if($status == 'Closed')
	{
		$btn_attr = 'disabled';
	}
}
$mode = $_POST['mode'];
?>
<style>
.conversionform {margin: 0 auto;width:450px;}
.itemform { background:#0cccae; color:#fff}
.conversionform th {text-align:left;color:#fff;width:200px;}
.conversionform td {border: 1px solid #aeaeae;}
.conversionform td input,.conversionform td select {border:0;}
.search-item {position: absolute;display:none;border:1px solid #aeaeae;width:100%;height:250px;background:#fff;box-shadow: 10px 10px 5px 0px rgba(0,0,0,0.4);
-webkit-box-shadow: 10px 10px 5px 0px rgba(0,0,0,0.4);border-bottom-left-radius: 7px;border-bottom-right-radius: 7px;overflow:auto;}
.searchlist {list-style-type: none;margin:0;padding:0;}
.searchlist li {padding: 5px 5px 5px 15px;}
</style>
<div class="whtransferheader">
<table style="width: 100%" class="table table-striped itemform">
	<thead>
	<tr>
		<th style="text-align:center">TRANSFER AND CONVERT</th>
	</tr>
	</thead>
</table>
<div class="conversionform">	
	<div>
		<button class="btn btn-warning btn-sm color-white" onclick="openFile()"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;Open File</button>
	</div>
	<table style="width: 100%">
		<tr>
			<th class="bg-info">Transaction Type</th>
			<td colspan="2"><input id="transaction_type"  type="text" class="form-control form-control-sm" value="WH2WH" disabled></td>
		</tr>
		<tr>
			<th class="bg-info">Category</th>
			<td colspan="2"><input id="category" type="text" class="form-control form-control-sm" value="<?php echo $category; ?>" disabled></td>
		</tr>	
		<tr>
			<th class="bg-info">Item Code</th>
			<td colspan="2"><input id="itemcode" type="text" class="form-control form-control-sm" value="<?php echo $item_code; ?>" disabled></td>
		</tr>
		<tr>
			<th class="bg-info">From Item</th>
			<td colspan="2">
				<input id="item_description" type="text" class="form-control form-control-sm" value="<?php echo $item_description; ?>" disabled>				
			</td>
		</tr>
		<tr>
			<th class="bg-info">To Item</th>
			<td colspan="2" style="position:relative">
				<input id="seachitemcode" type="hidden">
				<input id="itemsearch" type="text" class="form-control form-control-sm" placeholder="Receiving Item" autocomplete="nothing" value="<?php echo $searchItem; ?>">
				<div class="search-item" id="itemdropdown"></div>
			</td>
		</tr>
		<tr>
			<th class="bg-info">Onhand Stock</th>
			<td><input id="onhand_stock" type="number" class="form-control form-control-sm" style="text-align:right" value="<?php echo $stock;?>" disabled></td>
			<td><input type="text" class="form-control form-control-sm" value="<?php echo $on_hand_uom; ?>" disabled></td>
		</tr>
		<tr>
			<th class="bg-info">Take Out</th>
			<td><input id="takeout_amount" type="number" class="form-control form-control-sm" style="text-align:right" value="<?php echo $takeout_amount; ?>"></td>
			<td><input id="takeout_uom" type="text" class="form-control form-control-sm" value="<?php echo $on_hand_uom; ?>" disabled></td>
		</tr>
		<tr>
			<th class="bg-info">Amount/<?php echo $on_hand_uom; ?></th>
			<td><input id="amount_per_uom" type="number" class="form-control form-control-sm" style="text-align:right" value="<?php echo $amount_per_uom; ?>"></td>
			<td>
				<select id="amount_uom" class="form-control form-control-sm" onchange="changeConverted(this.value)">
					<?php echo $function->GetUOM($amount_uom,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th class="bg-info">Convert Amt.</th>
			<td style="width:40%"><input id="convert_amount" type="number" class="form-control form-control-sm" style="text-align:right" placeholder="0" value="<?php echo $convert_amount; ?>" disabled></td>
			<td style="width:60%">
				<input id="convert_oum" type="text" class="form-control form-control-sm" value="<?php echo $convert_uom; ?>" disabled>
			</td>
		</tr>
	</table>
	<div style="margin-top:10px;display:flex; gap:10px">
	<?php if($_POST['mode'] == 'new') {?>
		<button class="btn btn-success btn-sm" onclick="saveTransfer('new')"><i class="fa-solid fa-floppy-disk"></i>&nbsp;&nbsp;Save</button>
	<?php } if($_POST['mode'] == 'edit') {?>
		<button <?php echo $btn_attr; ?> class="btn btn-primary btn-sm" onclick="saveTransfer('update')"><i class="fa-solid fa-cloud-arrow-up"></i>&nbsp;&nbsp;Update</button>
		<button id="tti" <?php echo $btn_attr; ?> class="btn btn-secondary btn-sm" onclick="transferToInventory('<?php echo $rowid; ?>')"><i class="fa-solid fa-warehouse-full"></i>&nbsp;&nbsp;Inventory In</button>
		<button <?php echo $btn_attr; ?> class="btn btn-danger btn-sm" style="margin-left:auto" onclick="deleteTransfer('<?php echo $rowid; ?>')"><i class="fa-solid fa-xmark"></i>&nbsp;&nbsp;Delete</button>
	<?php } ?>
	</div>
	<?php if($status == 'Closed') {?>
		<div style="margin-top:10px;background:#fbf8e2;border:1px solid orange;padding:5px;text-align:center;border-radius:5px;white-space:normal">		
			This item has already been included in the inventory details.
		</div>
	<?php } ?>
</div>
<div id="transres"></div>
<script>
function transferToInventory(rowid)
{
	rms_reloaderOn("Executing Inventory In");
	$('#tti').attr('disabled', true);
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/transfer_inventory_process.php", { rowid: rowid },
		function(data) {		
			$('#transres').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function deleteTransfer(rowid)
{
	dialogue_confirm("Delete Record","Are you sure to delete this Record?","warning","deleteTransferYes",rowid,"red");
	return false;
}
function deleteTransferYes(rowid)
{
	var mode = 'deleteconvert';
	rms_reloaderOn("Deleting Item...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/undo_convert_process.php", { mode: mode, rowid: rowid },
		function(data) {		
			$('#transres').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function saveTransfer(mode)
{
	var rowid = '<?php echo $rowid; ?>';
	var transaction_type = $('#transaction_type').val();
	var category = $('#category').val();
	var itemcode = $('#itemcode').val();
	var onhand_stock = $('#onhand_stock').val();	
	var item_description = $('#itemsearch').val();
	var itemsearch = $('#itemsearch').val();	
	var seachitemcode = $('#seachitemcode').val();	
	var takeout_amount = $('#takeout_amount').val();
	var takeout_uom= $('#takeout_uom').val();
	var convert_amount = $('#convert_amount').val();	
	var amount_per_uom = $('#amount_per_uom').val();	
	var amount_uom = $('#amount_uom').val();	
	var convert_oum = $('#convert_oum').val();	
	
	if(itemsearch === '')
	{
		app_alert("Item Description","Please select Item","warning","Ok","itemsearch","focus");
		return false;
	}
	else if(takeout_amount === '' || takeout_amount <= 0)
	{
		app_alert("Take Out Amount","Please enter Take out Amount","warning","Ok","takeout_amount","focus");
		return false;
	}
	else if(itemsearch === '')
	{
		app_alert("Item Description","Please select Item","warning","Ok","itemsearch","focus");
		return false;
	}
	else if(amount_per_uom === '' || amount_per_uom <= 0)
	{
		app_alert("Weight","Please enter Weight conversion","warning","Ok","amountper_uom","focus");
		return false;
	}
	else if(convert_oum === '')
	{
		app_alert("Unit of Measures","Please select Unit of measurements","warning","Ok","convert_oum","focus");
		return false;
	}
	if(mode == 'new')
	{
		rms_reloaderOn('Saving Warehouse Transfer...');
	}
	if(mode == 'update')
	{
		rms_reloaderOn('Saving Warehouse Transfer...');
	}
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/convert_process.php", 
		{
			mode: mode,
			rowid: rowid,
			transaction_type: transaction_type,
			category: category,
			itemcode: itemcode,
			onhand_stock: onhand_stock,
			item_description: item_description,
			itemsearch: itemsearch,
			seachitemcode: seachitemcode,
			takeout_amount: takeout_amount,
			takeout_uom: takeout_uom,
			amount_per_uom: amount_per_uom,	
			amount_uom: amount_uom,
			convert_amount: convert_amount,
			convert_oum: convert_oum
		},
		function(data) {		
			$('#transres').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function setSearchItem(item,itemcode)
{
	$('#itemsearch').val(item);
	$('#seachitemcode').val(itemcode);
	$('#itemdropdown').slideUp();
}
function changeConverted(uom)
{
	$('#convert_oum').val(uom);
}
$(document).ready(function() {
	$('#itemsearch').keyup(function()
	{
		var mode = 'transfersearch';
		var search = $('#itemsearch').val();
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, search: search },
		function(data) {		
			$('#itemdropdown').html(data);
			$('#itemdropdown').slideDown();
		})		
	});
    $('#takeout_amount,#amount_per_uom').keyup(function() {
        var takeout_amount = parseFloat($('#takeout_amount').val());
        var amount_per_uom = parseFloat($('#amount_per_uom').val());
        
        if (!isNaN(takeout_amount) && !isNaN(amount_per_uom)) {
            var convert_amount = (takeout_amount * amount_per_uom);
            $('#convert_amount').val(convert_amount);
        } else {
            $('#convert_amount').val('');
        }
    });
});</script>