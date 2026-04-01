<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php") ;
$conversion_table = 'wms_itemlist_conversion';
$checkConvTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_conversion'");
if(!$checkConvTable || $checkConvTable->num_rows == 0)
{
	$checkConvTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_converssion'");
	if($checkConvTable2 && $checkConvTable2->num_rows > 0)
	{
		$conversion_table = 'wms_itemlist_converssion';
	}
}
$mode = $_POST['params'];
$function = new WMSFunctions;
$barcode_list = array();
$primary_barcode = '';
$conv_uom_to = '';
$conv_factor = '';
$item_type = 'stock';
$item_status = 'active';
$base_uom = '';
$reorder_point = '0.0000';
$reorder_qty = '0.0000';
$safety_stock = '0.0000';
$lead_time_days = '';
$average_leadtime = '0';
$max_leadtime = '0';
$shelf_life_days = '';
$is_lot_tracked = 0;
$is_serial_tracked = 0;
$selling_price = '0.0000';
$standard_cost = '0.0000';
$last_purchase_cost = '0.0000';
$moving_average_cost = '0.0000';
$cost_method = 'MOVING_AVG';
$brand = '';
$model = '';
if($_POST['params'] == 'edit')
{
	$rowid = $_POST['rowid'];
	$QUERY = "SELECT * FROM wms_itemlist WHERE id='$rowid'";
	$result = mysqli_query($db, $QUERY );    
    if ( $result->num_rows > 0 ) 
    {
		while($ROW = mysqli_fetch_array($result))  
		{
			$rowid = $ROW['id'];
			$item_code = $ROW['item_code'];
			$qr_code = $ROW['qr_code'];
			$category = $ROW['category'];
			$recipient = $ROW['recipient'];
			$item_location = $ROW['item_location'];
			$class = $ROW['class'];
			$item_description = $ROW['item_description'];
			$unit_price = $ROW['unit_price'];
			$uom = $ROW['uom'];
			$yieldperbatch = isset($ROW['yield_perbatch']) ? $ROW['yield_perbatch'] : '';
			$item_type = isset($ROW['item_type']) ? $ROW['item_type'] : 'stock';
			$item_status = isset($ROW['item_status']) ? $ROW['item_status'] : (($ROW['active'] == 1) ? 'active' : 'inactive');
			$base_uom = isset($ROW['base_uom']) && trim((string)$ROW['base_uom']) != '' ? $ROW['base_uom'] : $uom;
			$reorder_point = isset($ROW['reorder_point']) ? $ROW['reorder_point'] : '0.0000';
			$reorder_qty = isset($ROW['reorder_qty']) ? $ROW['reorder_qty'] : '0.0000';
			$safety_stock = isset($ROW['safety_stock']) ? $ROW['safety_stock'] : '0.0000';
			$lead_time_days = isset($ROW['lead_time_days']) ? $ROW['lead_time_days'] : '';
			$average_leadtime = isset($ROW['average_leadtime']) ? $ROW['average_leadtime'] : '0';
			$max_leadtime = isset($ROW['max_leadtime']) ? $ROW['max_leadtime'] : '0';
			$shelf_life_days = isset($ROW['shelf_life_days']) ? $ROW['shelf_life_days'] : '';
			$is_lot_tracked = isset($ROW['is_lot_tracked']) ? (int)$ROW['is_lot_tracked'] : 0;
			$is_serial_tracked = isset($ROW['is_serial_tracked']) ? (int)$ROW['is_serial_tracked'] : 0;
			$selling_price = isset($ROW['selling_price']) ? $ROW['selling_price'] : $unit_price;
			$standard_cost = isset($ROW['standard_cost']) ? $ROW['standard_cost'] : '0.0000';
			$last_purchase_cost = isset($ROW['last_purchase_cost']) ? $ROW['last_purchase_cost'] : '0.0000';
			$moving_average_cost = isset($ROW['moving_average_cost']) ? $ROW['moving_average_cost'] : '0.0000';
			$cost_method = isset($ROW['cost_method']) ? $ROW['cost_method'] : 'MOVING_AVG';
			$brand = isset($ROW['brand']) ? $ROW['brand'] : '';
			$model = isset($ROW['model']) ? $ROW['model'] : '';
			$added_by = $ROW['added_by'];
			$date_added = $ROW['date_added'];
			$active = $ROW['active'];
			
			if($ROW['active'] == 1)
			{
				$checked = 'checked="checked"';
			} 
			if($ROW['active'] == 0)
			{
				$checked = '';
			}
			if($ROW['date_added'] != '')
			{
				$date_added = date("F d, Y @h:i A", strtotime($ROW['date_added']));
			} else {
				$date_added = "--|--";
			}
			
			$item_class_ph = 'Ex: WIP';
		}
    }

	$barcode_query = "SELECT barcode,is_primary FROM wms_itemlist_barcodes WHERE item_id='$rowid' AND active=1 ORDER BY is_primary DESC,id ASC";
	$barcode_result = mysqli_query($db, $barcode_query);
	if ($barcode_result && $barcode_result->num_rows > 0)
	{
		while($BARCODEROW = mysqli_fetch_array($barcode_result))
		{
			$barcode_value = trim($BARCODEROW['barcode']);
			if($barcode_value == '')
			{
				continue;
			}
			$barcode_list[] = $barcode_value;
			if($BARCODEROW['is_primary'] == 1)
			{
				$primary_barcode = $barcode_value;
			}
		}
	}
	if(count($barcode_list) === 0 && trim($qr_code) != '')
	{
		$barcode_list[] = trim($qr_code);
		$primary_barcode = trim($qr_code);
	}

	$convQuery = "SELECT uom_from,uom_to,factor FROM $conversion_table WHERE item_id='$rowid' ORDER BY id DESC LIMIT 1";
	$convResult = mysqli_query($db, $convQuery);
	if($convResult && $convResult->num_rows > 0)
	{
		$convRow = mysqli_fetch_assoc($convResult);
		$conv_uom_to = trim($convRow['uom_to']);
		$conv_factor = $convRow['factor'];
	}
}
if($_POST['params'] == 'add')
{
	$rowid = "";
	$item_code = "";
	$qr_code =  "";
	$category =  "";
	$recipient =  "";
	$item_location = "";
	$class =  "";
	$item_description =  "";
	$uom =  "";
	$yieldperbatch =  "";
	$item_type = 'stock';
	$item_status = 'active';
	$base_uom = '';
	$reorder_point = '0.0000';
	$reorder_qty = '0.0000';
	$safety_stock = '0.0000';
	$lead_time_days = '';
	$average_leadtime = '0';
	$max_leadtime = '0';
	$shelf_life_days = '';
	$is_lot_tracked = 0;
	$is_serial_tracked = 0;
	$selling_price = '0.0000';
	$standard_cost = '0.0000';
	$last_purchase_cost = '0.0000';
	$moving_average_cost = '0.0000';
	$cost_method = 'MOVING_AVG';
	$brand = '';
	$model = '';
	$added_by =  "";
	$date_added =  "";
	$active =  "";
	$checked = "";
	$unit_price = "0.00";
	$item_class_ph = 'Ex: WIP';
	$barcode_list = array();
	$primary_barcode = '';
	$conv_uom_to = '';
	$conv_factor = '';
}
?>
<style>
.form-wrapper {width:560px;max-height:65vh;overflow-y:auto;padding-right:4px;}
.item-form-table {margin-bottom:0;}
.item-form-table th {
	font-size:14px !important;
	font-weight:600;
	width:145px;
	padding:6px 8px;
	vertical-align:middle;
	line-height:1.2;
}
.item-form-table td {
	padding:4px 6px;
	vertical-align:middle;
}
.item-form-table .form-control,
.item-form-table .form-control-sm,
.item-form-table .input-group-text {
	border-radius:4px;
}
.barcode-row .input-group-text {
	padding:0 8px;
}
.barcode-row .btn {
	min-width:34px;
	padding:0 10px;
}
.barcode-actions {
	margin-top:4px;
}
.results {
	min-height:20px;
	font-size:12px;
	margin-top:6px;
}
.form-actions {
	margin-top:10px;
	padding-top:8px;
	border-top:1px solid #dee2e6;
	text-align:right;
}
</style>
<div class="form-wrapper">	
	<table style="width: 100%" class="table table-borderless item-form-table">
		<tr>
			<th>Recipient</th>
			<td>
				<input type="hidden" value="<?php echo $rowid; ?>">
				<select id="recipient" class="form-control">
					<?php echo $function->GetRecipient($recipient,$db); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Loc.</th>
			<td>
				<select id="item_location" class="form-control">
					<?php echo $function->GetLocation($item_location,$db); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Code</th>
			<td>
				<input id="item_code" type="text" class="form-control" value="<?php echo $item_code; ?>" <?php echo ($mode == 'edit' ? 'readonly' : ''); ?>>				
			</td>
		</tr>
		<tr>
			<th>Barcodes</th>
			<td>
				<div id="barcode_list">
					<?php
					$barcode_rendered = false;
					foreach($barcode_list as $barcode_value)
					{
						$barcode_value = trim($barcode_value);
						if($barcode_value == '') { continue; }
						$is_primary = ($primary_barcode != '' && $primary_barcode == $barcode_value) ? 'checked="checked"' : '';
						$barcode_rendered = true;
					?>
					<div class="input-group input-group-sm mb-1 barcode-row">
						<div class="input-group-prepend">
							<span class="input-group-text"><input type="radio" class="primary-barcode" name="primary_barcode" <?php echo $is_primary; ?>></span>
						</div>
						<input type="text" class="form-control form-control-sm barcode-input" value="<?php echo htmlspecialchars($barcode_value); ?>" placeholder="Enter barcode">
						<div class="input-group-append">
							<button class="btn btn-outline-danger btn-sm" type="button" onclick="removeBarcodeRow(this)">X</button>
						</div>
					</div>
					<?php }
					if($barcode_rendered === false) { ?>
					<div class="input-group input-group-sm mb-1 barcode-row">
						<div class="input-group-prepend">
							<span class="input-group-text"><input type="radio" class="primary-barcode" name="primary_barcode" checked="checked"></span>
						</div>
						<input type="text" class="form-control form-control-sm barcode-input" value="" placeholder="Enter barcode">
						<div class="input-group-append">
							<button class="btn btn-outline-danger btn-sm" type="button" onclick="removeBarcodeRow(this)">X</button>
						</div>
					</div>
					<?php } ?>
				</div>
				<div class="barcode-actions">
					<button class="btn btn-secondary btn-sm" type="button" onclick="addBarcodeRow('', false)">Add Barcode</button>
				</div>
			</td>
		</tr>
		<tr>
			<th>Category</th>
			<td>
				<select id="categories" class="form-control">
					<?php echo $function->GetItemCategory($category,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Classfication</th>
			<td><input id="classification" type="text" class="form-control" value="<?php echo $class; ?>" placeholder="<?php echo $item_class_ph?>"></td>
		</tr>
		<tr>
			<th>Item Description</th>
			<td><textarea id="item_description" class="form-control" rows="2"><?php echo htmlspecialchars($item_description, ENT_QUOTES); ?></textarea></td>
		</tr>
		<tr>
			<th>Units of Measure</th>
			<td>
				<select id="uom" class="form-control">
					<?php echo $function->GetUOM($uom,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Base UOM</th>
			<td>
				<select id="base_uom" class="form-control">
					<?php echo $function->GetUOM($base_uom,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Type</th>
			<td>
				<select id="item_type" class="form-control">
					<option value="stock" <?php echo ($item_type == 'stock' ? 'selected' : ''); ?>>Stock</option>
					<option value="non_stock" <?php echo ($item_type == 'non_stock' ? 'selected' : ''); ?>>Non-Stock</option>
					<option value="service" <?php echo ($item_type == 'service' ? 'selected' : ''); ?>>Service</option>
					<option value="asset" <?php echo ($item_type == 'asset' ? 'selected' : ''); ?>>Asset</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Status</th>
			<td>
				<select id="item_status" class="form-control">
					<option value="active" <?php echo ($item_status == 'active' ? 'selected' : ''); ?>>Active</option>
					<option value="inactive" <?php echo ($item_status == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
					<option value="obsolete" <?php echo ($item_status == 'obsolete' ? 'selected' : ''); ?>>Obsolete</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Brand</th>
			<td><input id="brand" type="text" class="form-control" value="<?php echo htmlspecialchars($brand, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Model</th>
			<td><input id="model" type="text" class="form-control" value="<?php echo htmlspecialchars($model, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Conv. UOM To</th>
			<td>
				<select id="conv_uom_to" class="form-control">
					<?php echo $function->GetUOM($conv_uom_to,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Conv. Factor/Value</th>
			<td><input id="conv_factor" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($conv_factor, ENT_QUOTES); ?>" placeholder="Ex: 12"></td>
		</tr>
		<tr>
			<th>Yield per Batch</th>
			<td><input id="yieldperbatch" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($yieldperbatch, ENT_QUOTES); ?>" placeholder="0"></td>
		</tr>
		<tr>
			<th>Unit Price</th>
			<td><input id="unit_price" type="number" class="form-control" value="<?php echo $unit_price; ?>" placeholder="<?php echo $unit_price; ?>"></td>
		</tr>
		<tr>
			<th>Selling Price</th>
			<td><input id="selling_price" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($selling_price, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Standard Cost</th>
			<td><input id="standard_cost" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($standard_cost, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Last Purchase Cost</th>
			<td><input id="last_purchase_cost" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($last_purchase_cost, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Moving Avg Cost</th>
			<td><input id="moving_average_cost" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($moving_average_cost, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Cost Method</th>
			<td>
				<select id="cost_method" class="form-control">
					<option value="MOVING_AVG" <?php echo ($cost_method == 'MOVING_AVG' ? 'selected' : ''); ?>>Moving Average</option>
					<option value="FIFO" <?php echo ($cost_method == 'FIFO' ? 'selected' : ''); ?>>FIFO</option>
					<option value="STANDARD" <?php echo ($cost_method == 'STANDARD' ? 'selected' : ''); ?>>Standard</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>Reorder Point</th>
			<td><input id="reorder_point" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($reorder_point, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Reorder Qty</th>
			<td><input id="reorder_qty" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($reorder_qty, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Safety Stock</th>
			<td><input id="safety_stock" type="number" step="0.0001" min="0" class="form-control" value="<?php echo htmlspecialchars($safety_stock, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Lead Time Days</th>
			<td><input id="lead_time_days" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($lead_time_days, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Min. Leadtime</th>
			<td><input id="average_leadtime" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($average_leadtime, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Max. Leadtime</th>
			<td><input id="max_leadtime" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($max_leadtime, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Shelf Life (Days)</th>
			<td><input id="shelf_life_days" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($shelf_life_days, ENT_QUOTES); ?>"></td>
		</tr>
		<tr>
			<th>Lot Tracked</th>
			<td><input id="is_lot_tracked" type="checkbox" <?php echo ($is_lot_tracked == 1 ? 'checked="checked"' : ''); ?>></td>
		</tr>
		<tr>
			<th>Serial Tracked</th>
			<td><input id="is_serial_tracked" type="checkbox" <?php echo ($is_serial_tracked == 1 ? 'checked="checked"' : ''); ?>></td>
		</tr>
	<?php if($mode == 'edit') { ?>
		<tr>
			<th>Added By</th>
			<td><input id="added_by" type="text" class="form-control" value="<?php echo $added_by; ?>" disabled></td>
		</tr>
		<tr>
			<th>Date Added</th>
			<td><input id="date_added" type="text" class="form-control" value="<?php echo $date_added; ?>" disabled></td>
		</tr>
	<?php } ?>
		<tr>
			<th>Active</th>
			<td>
				<label class="switch">
					<input id="active" type="checkbox" <?php echo $checked; ?>>
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
	</table>
</div>
<div class="results"></div>
<div class="form-actions">
	<?php if($mode == 'add') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Save Item</button>
	<?php } if($mode == 'edit') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Update Itemlist</button>
	<button class="btn btn-warning btn-sm" onclick="deleteItem('<?php echo $rowid; ?>')">Delete Item</button>
	<?php } ?>
	<button class="btn btn-danger btn-sm" onclick="closeModal('formmodal')">Close</button>
</div>
<script>
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
function addBarcodeRow(value, isPrimary)
{
	var safeValue = $('<div>').text(value).html();
	var checked = isPrimary === true ? 'checked="checked"' : '';
	var html = ''+
		'<div class="input-group input-group-sm mb-1 barcode-row">' +
			'<div class="input-group-prepend">' +
				'<span class="input-group-text"><input type="radio" class="primary-barcode" name="primary_barcode" '+checked+'></span>' +
			'</div>' +
			'<input type="text" class="form-control form-control-sm barcode-input" value="'+safeValue+'" placeholder="Enter barcode">' +
			'<div class="input-group-append">' +
				'<button class="btn btn-outline-danger btn-sm" type="button" onclick="removeBarcodeRow(this)">X</button>' +
			'</div>' +
		'</div>';
	$('#barcode_list').append(html);
	ensurePrimaryBarcode();
}
function removeBarcodeRow(button)
{
	var rows = $('#barcode_list .barcode-row');
	if(rows.length <= 1)
	{
		rows.find('.barcode-input').val('');
		rows.find('.primary-barcode').prop('checked', true);
		return false;
	}
	$(button).closest('.barcode-row').remove();
	ensurePrimaryBarcode();
}
function ensurePrimaryBarcode()
{
	var rows = $('#barcode_list .barcode-row');
	if(rows.length === 0)
	{
		addBarcodeRow('', true);
		return;
	}
	if(rows.find('.primary-barcode:checked').length === 0)
	{
		rows.first().find('.primary-barcode').prop('checked', true);
	}
}
function validateForm()
{
	var mode = '<?php echo $mode; ?>';
	var rowid = '<?php echo $rowid; ?>';
	var limit = $('#limit').val(); 
	var recipient = $('#recipient').val(); 
	var classification = $('#classification').val(); 
	var item_location = $('#item_location').val();
	var item_code = $('#item_code').val();
	var category = $('#categories').val();
	var item_description = $('#item_description').val();
	var uom = $('#uom').val();
	var base_uom = $('#base_uom').val();
	var item_type = $('#item_type').val();
	var item_status = $('#item_status').val();
	var brand = $('#brand').val();
	var model = $('#model').val();
	var yieldperbatch = $('#yieldperbatch').val();
	var conv_uom_to = $.trim($('#conv_uom_to').val());
	var conv_factor = $.trim($('#conv_factor').val());
	var unit_price = $('#unit_price').val();
	var selling_price = $('#selling_price').val();
	var standard_cost = $('#standard_cost').val();
	var last_purchase_cost = $('#last_purchase_cost').val();
	var moving_average_cost = $('#moving_average_cost').val();
	var cost_method = $('#cost_method').val();
	var reorder_point = $('#reorder_point').val();
	var reorder_qty = $('#reorder_qty').val();
	var safety_stock = $('#safety_stock').val();
	var lead_time_days = $('#lead_time_days').val();
	var average_leadtime = $('#average_leadtime').val();
	var max_leadtime = $('#max_leadtime').val();
	var shelf_life_days = $('#shelf_life_days').val();
	var is_lot_tracked = $('#is_lot_tracked').is(":checked") ? 1 : 0;
	var is_serial_tracked = $('#is_serial_tracked').is(":checked") ? 1 : 0;
	if(recipient === '')
	{
		app_alert("Recipient","Please select Recipient","warning","Ok","recipient","focus");
		return false;
	}
	if(item_location === '')
	{
		app_alert("Item Location","Please select Item Location","warning","Ok","item_location","focus");
		return false;
	}

	if(item_code === '')
	{
		app_alert("Item Code","Please enter Item Code","warning","Ok","item_code","focus");
		return false;
	}
	if(category == '')
	{
		app_alert("Category","Please select Category","warning","Ok","categories","focus");
		return false;
	}
	if(classification == '')
	{
		app_alert("Category","Please Enter item Classification","warning","Ok","classification","focus");
		return false;
	}
	if(item_description === '')
	{
		app_alert("item Description","Please enter the Description","warning","Ok","item_description","focus");
		return false;
	}
	if(uom === '')
	{
		app_alert("Units of Measures","Please select units of Measurements","warning","Ok","uom","focus");
		return false;
	}	
	if(base_uom === '')
	{
		app_alert("Base UOM","Please select Base UOM","warning","Ok","base_uom","focus");
		return false;
	}
	if(item_type === '')
	{
		app_alert("Item Type","Please select Item Type","warning","Ok","item_type","focus");
		return false;
	}
	if(item_status === '')
	{
		app_alert("Item Status","Please select Item Status","warning","Ok","item_status","focus");
		return false;
	}
	if(yieldperbatch !== '' && parseFloat(yieldperbatch) < 0)
	{
		app_alert("Yield per Batch","Please enter valid Yield per Batch","warning","Ok","yieldperbatch","focus");
		return false;
	}
	if(unit_price < 0 || unit_price === '')
	{
		app_alert("Unit Price","Please enter Unit Price","warning","Ok","unit_price","focus");
		return false;
	}
	if((selling_price !== '' && parseFloat(selling_price) < 0) || (standard_cost !== '' && parseFloat(standard_cost) < 0) || (last_purchase_cost !== '' && parseFloat(last_purchase_cost) < 0) || (moving_average_cost !== '' && parseFloat(moving_average_cost) < 0))
	{
		app_alert("Costing","Please enter valid cost/selling values","warning","Ok","selling_price","focus");
		return false;
	}
	if((reorder_point !== '' && parseFloat(reorder_point) < 0) || (reorder_qty !== '' && parseFloat(reorder_qty) < 0) || (safety_stock !== '' && parseFloat(safety_stock) < 0))
	{
		app_alert("Reorder Settings","Please enter valid reorder/safety values","warning","Ok","reorder_point","focus");
		return false;
	}
	if((lead_time_days !== '' && parseInt(lead_time_days) < 0) || (average_leadtime !== '' && parseInt(average_leadtime) < 0) || (max_leadtime !== '' && parseInt(max_leadtime) < 0) || (shelf_life_days !== '' && parseInt(shelf_life_days) < 0))
	{
		app_alert("Leadtime/Shelf Life","Please enter non-negative values","warning","Ok","lead_time_days","focus");
		return false;
	}
	
	var convHasAnyValue = (conv_uom_to !== '' || conv_factor !== '');
	if(convHasAnyValue)
	{
		if(conv_uom_to === '')
		{
			app_alert("Conversion","Please enter Conversion UOM To","warning","Ok","conv_uom_to","focus");
			return false;
		}
		if(conv_factor === '' || parseFloat(conv_factor) <= 0)
		{
			app_alert("Conversion","Please enter valid Conversion Factor","warning","Ok","conv_factor","focus");
			return false;
		}
	}
	var barcodes = [];
	var primary_barcode = '';
	$('#barcode_list .barcode-row').each(function()
	{
		var barcode_value = $(this).find('.barcode-input').val().trim();
		if(barcode_value !== '')
		{
			barcodes.push(barcode_value);
			if($(this).find('.primary-barcode').is(":checked"))
			{
				primary_barcode = barcode_value;
			}
		}
	});
	if(primary_barcode === '')
	{
		primary_barcode = barcodes[0];
	}
	var qr_code = primary_barcode;
	if($('#active').is(":checked") == true)
	{
		var active = 1;
	} else {
		var active = 0;
	}
	if(mode == 'add')
	{
		rms_reloaderOn("Saving to Itemlist...");
	} 
	if(mode == 'edit')
	{
		rms_reloaderOn("Updating Itemlist...");
	} 
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/itemlist_process.php",
		{ 
			mode: mode,
			rowid: rowid,
			active: active, 
			recipient: recipient, 
			item_location: item_location,
			item_code: item_code,
			qr_code: qr_code,
			barcodes: barcodes,
			primary_barcode: primary_barcode,
			category: category, 
			classification: classification,
			item_description: item_description,
			uom: uom,
			base_uom: base_uom,
			item_type: item_type,
			item_status: item_status,
			brand: brand,
			model: model,
			yieldperbatch: yieldperbatch,
			conv_uom_to: conv_uom_to,
			conv_factor: conv_factor,
			unit_price: unit_price,
			selling_price: selling_price,
			standard_cost: standard_cost,
			last_purchase_cost: last_purchase_cost,
			moving_average_cost: moving_average_cost,
			cost_method: cost_method,
			reorder_point: reorder_point,
			reorder_qty: reorder_qty,
			safety_stock: safety_stock,
			lead_time_days: lead_time_days,
			average_leadtime: average_leadtime,
			max_leadtime: max_leadtime,
			shelf_life_days: shelf_life_days,
			is_lot_tracked: is_lot_tracked,
			is_serial_tracked: is_serial_tracked,
			active : active, 
			limit: limit
		},
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
$(function()
{
	ensurePrimaryBarcode();
});
</script>

