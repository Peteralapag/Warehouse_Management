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
			$supplier_id = $ROW['supplier_id'];
			$item_code = $ROW['item_code'];
			$qr_code = $ROW['qr_code'];
			$category = $ROW['category'];
			$recipient = $ROW['recipient'];
			$item_location = $ROW['item_location'];
			$class = $ROW['class'];
			$item_description = $ROW['item_description'];
			$unit_price = $ROW['unit_price'];
			$uom = $ROW['uom'];
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
				$date_added = date("F m, Y @h:i A");
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
	$supplier_id = "";
	$item_code = "";
	$qr_code =  "";
	$category =  "";
	$recipient =  "";
	$item_location = "";
	$class =  "";
	$item_description =  "";
	$uom =  "";
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
			<th>Supplier</th>
			<td>
				<input type="hidden" value="<?php echo $rowid; ?>">
				<input id="supplier" type="text" list="supplierss" class="form-control">
				<datalist id="supplierss">
					<?php echo $function->GetSupplier($supplier_id,$db)?>
				</datalist>
			</td>		
		</tr>
		<tr>
			<th>Recipient</th>
			<td>
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
				<input id="item_code" type="text" class="form-control" value="<?php echo $item_code; ?>">				
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
			<th>Categorysss</th>
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
			<td><input id="item_description" type="text" class="form-control" value="<?php echo $item_description; ?>"></td>
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
			<th>Unit Price</th>
			<td><input id="unit_price" type="number" class="form-control" value="<?php echo $unit_price; ?>" placeholder="<?php echo $unit_price; ?>"></td>
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
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Save Supplier</button>
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
	var supplier = $('#supplier').val(); 
	var recipient = $('#recipient').val(); 
	var classification = $('#classification').val(); 
	var item_location = $('#item_location').val();
	var item_code = $('#item_code').val();
	var category = $('#categories').val();
	var item_description = $('#item_description').val();
	var uom = $('#uom').val();
	var conv_uom_to = $.trim($('#conv_uom_to').val());
	var conv_factor = $.trim($('#conv_factor').val());
	var unit_price = $('#unit_price').val();
	var active = $('#active').val();
/*	if(supplier === '')
	{
		app_alert("Supplier","Please select Supplier","warning","Ok","supplier","focus");
		return false;
	} */
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
		app_alert("Category","Please select Categorya","warning","Ok","category","focus");
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
	if(unit_price < 0 || unit_price === '')
	{
		app_alert("Unit Price","Please enter Unit Price","warning","Ok","unit_price","focus");
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
			conv_uom_to: conv_uom_to,
			conv_factor: conv_factor,
			unit_price: unit_price,
			active : active, 
			limit: limit,
			supplier: supplier
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

