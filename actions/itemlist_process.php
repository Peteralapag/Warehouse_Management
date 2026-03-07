<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mode = $_POST['mode'];
$limit = $_POST['limit'];
$rowid = $_POST['rowid'];
$supplier_id = isset($_POST['supplier']) ? $_POST['supplier'] : '';
$recipient = $_POST['recipient'];
$item_location = $_POST['item_location'];
$item_code = $_POST['item_code'];
$qr_code = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';
$category = $_POST['category'];
$class = $_POST['classification'];
$item_description = $_POST['item_description'];
$uom = $_POST['uom'];
$base_uom = isset($_POST['base_uom']) ? trim($_POST['base_uom']) : $uom;
$item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : 'stock';
$item_status = isset($_POST['item_status']) ? trim($_POST['item_status']) : ((isset($_POST['active']) && (int)$_POST['active'] === 1) ? 'active' : 'inactive');
$brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
$model = isset($_POST['model']) ? trim($_POST['model']) : '';
$yieldperbatch = isset($_POST['yieldperbatch']) ? trim($_POST['yieldperbatch']) : '';
$conv_uom_from = $uom;
$conv_uom_to = isset($_POST['conv_uom_to']) ? trim($_POST['conv_uom_to']) : '';
$conv_factor = isset($_POST['conv_factor']) ? trim($_POST['conv_factor']) : '';
$active = $_POST['active'];
$unit_price = $_POST['unit_price'];
$selling_price = isset($_POST['selling_price']) ? trim($_POST['selling_price']) : '';
$standard_cost = isset($_POST['standard_cost']) ? trim($_POST['standard_cost']) : '0';
$last_purchase_cost = isset($_POST['last_purchase_cost']) ? trim($_POST['last_purchase_cost']) : '0';
$moving_average_cost = isset($_POST['moving_average_cost']) ? trim($_POST['moving_average_cost']) : '0';
$cost_method = isset($_POST['cost_method']) ? trim($_POST['cost_method']) : 'MOVING_AVG';
$reorder_point = isset($_POST['reorder_point']) ? trim($_POST['reorder_point']) : '0';
$reorder_qty = isset($_POST['reorder_qty']) ? trim($_POST['reorder_qty']) : '0';
$safety_stock = isset($_POST['safety_stock']) ? trim($_POST['safety_stock']) : '0';
$lead_time_days = isset($_POST['lead_time_days']) ? trim($_POST['lead_time_days']) : '';
$average_leadtime = isset($_POST['average_leadtime']) ? trim($_POST['average_leadtime']) : '0';
$max_leadtime = isset($_POST['max_leadtime']) ? trim($_POST['max_leadtime']) : '0';
$shelf_life_days = isset($_POST['shelf_life_days']) ? trim($_POST['shelf_life_days']) : '';
$is_lot_tracked = isset($_POST['is_lot_tracked']) ? (int)$_POST['is_lot_tracked'] : 0;
$is_serial_tracked = isset($_POST['is_serial_tracked']) ? (int)$_POST['is_serial_tracked'] : 0;
$barcodes = isset($_POST['barcodes']) && is_array($_POST['barcodes']) ? $_POST['barcodes'] : array();
$primary_barcode = isset($_POST['primary_barcode']) ? trim($_POST['primary_barcode']) : '';
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");

function hasWmsColumn($db, $columnName)
{
	$columnName = mysqli_real_escape_string($db, $columnName);
	$query = "SHOW COLUMNS FROM wms_itemlist LIKE '$columnName'";
	$result = mysqli_query($db, $query);
	return ($result && $result->num_rows > 0);
}

function hasTable($db, $tableName)
{
	$tableName = mysqli_real_escape_string($db, $tableName);
	$result = mysqli_query($db, "SHOW TABLES LIKE '$tableName'");
	return ($result && $result->num_rows > 0);
}

function getModulesByRecipient($recipient)
{
	$recipient = strtoupper(trim((string)$recipient));
	if($recipient === 'WAREHOUSE')
	{
		return array('Warehouse_Management');
	}
	if($recipient === 'PROPERTY CUSTODIAN')
	{
		return array('Property_Custodian_System');
	}
	if($recipient === 'BRANCH')
	{
		return array('Branch_Ordering_System','FD_Branch_Ordering_System','DBC_Branch_Ordering_System','DBC_Seasonal_Branch_Ordering_System');
	}
	if($recipient === 'DAVAO BAKING CENTER' || $recipient === 'DBC')
	{
		return array('DBC_Management');
	}
	return array();
}

function syncItemModuleVisibility($db, $itemId, $recipient, $app_user)
{
	$itemId = (int)$itemId;
	if($itemId <= 0)
	{
		return array('status' => false, 'message' => 'Invalid item id for module mapping.');
	}
	if(!hasTable($db, 'wms_item_module_visibility'))
	{
		return array('status' => true, 'message' => '');
	}

	$targetModules = getModulesByRecipient($recipient);
	if(count($targetModules) === 0)
	{
		return array('status' => true, 'message' => '');
	}

	$knownModules = array('Warehouse_Management','Property_Custodian_System','Branch_Ordering_System','FD_Branch_Ordering_System','DBC_Branch_Ordering_System','DBC_Seasonal_Branch_Ordering_System','DBC_Management');
	$knownEsc = array();
	foreach($knownModules as $module)
	{
		$knownEsc[] = "'".mysqli_real_escape_string($db, $module)."'";
	}
	$inKnown = implode(',', $knownEsc);
	if($db->query("UPDATE wms_item_module_visibility SET active=0,updated_at=NOW() WHERE item_id='$itemId' AND module_code IN ($inKnown)") !== TRUE)
	{
		return array('status' => false, 'message' => $db->error);
	}

	foreach($targetModules as $module)
	{
		$moduleEsc = mysqli_real_escape_string($db, $module);
		$userEsc = mysqli_real_escape_string($db, $app_user);
		$upsert = "INSERT INTO wms_item_module_visibility (`item_id`,`module_code`,`active`,`created_by`) VALUES ('$itemId','$moduleEsc',1,'$userEsc') ";
		$upsert .= "ON DUPLICATE KEY UPDATE active=1,updated_at=NOW()";
		if($db->query($upsert) !== TRUE)
		{
			return array('status' => false, 'message' => $db->error);
		}
	}

	return array('status' => true, 'message' => '');
}

$cleanBarcodes = array();
foreach($barcodes as $barcodeValue)
{
	$barcodeValue = trim($barcodeValue);
	if($barcodeValue == '')
	{
		continue;
	}
	if(!in_array($barcodeValue, $cleanBarcodes))
	{
		$cleanBarcodes[] = $barcodeValue;
	}
}
if($primary_barcode == '' && count($cleanBarcodes) > 0)
{
	$primary_barcode = $cleanBarcodes[0];
}
if($primary_barcode != '' && !in_array($primary_barcode, $cleanBarcodes))
{
	array_unshift($cleanBarcodes, $primary_barcode);
}
if($primary_barcode != '')
{
	$qr_code = $primary_barcode;
}

function getBarcodeConflict($db, $itemId, $barcodes)
{
	$itemId = (int)$itemId;
	foreach($barcodes as $barcode)
	{
		$barcode = trim($barcode);
		if($barcode == '')
		{
			continue;
		}
		$barcodeEsc = mysqli_real_escape_string($db, $barcode);
		$query = "SELECT item_id FROM wms_itemlist_barcodes WHERE barcode='$barcodeEsc' LIMIT 1";
		$result = mysqli_query($db, $query);
		if($result && $result->num_rows > 0)
		{
			$row = mysqli_fetch_assoc($result);
			if((int)$row['item_id'] !== $itemId)
			{
				return $barcode;
			}
		}
	}
	return '';
}

function saveItemBarcodes($db, $itemId, $barcodes, $primary_barcode, $app_user, $date_user)
{
	$itemId = (int)$itemId;
	if($itemId <= 0)
	{
		return array('status' => false, 'message' => 'Invalid item id.');
	}
	if($db->query("UPDATE wms_itemlist_barcodes SET active=0,is_primary=0 WHERE item_id='$itemId'") !== TRUE)
	{
		return array('status' => false, 'message' => $db->error);
	}
	foreach($barcodes as $barcode)
	{
		$barcode = trim($barcode);
		if($barcode == '')
		{
			continue;
		}
		$is_primary = ($barcode == $primary_barcode) ? 1 : 0;
		$barcodeEsc = mysqli_real_escape_string($db, $barcode);
		$checkExisting = "SELECT id,item_id FROM wms_itemlist_barcodes WHERE barcode='$barcodeEsc' LIMIT 1";
		$existingResult = mysqli_query($db, $checkExisting);

		if($existingResult && $existingResult->num_rows > 0)
		{
			$existingRow = mysqli_fetch_assoc($existingResult);
			if((int)$existingRow['item_id'] !== $itemId)
			{
				return array('status' => false, 'message' => 'Barcode already assigned to another item: '.$barcode);
			}
			$barcodeId = (int)$existingRow['id'];
			$updateBarcode = "UPDATE wms_itemlist_barcodes SET is_primary='$is_primary',active=1 WHERE id='$barcodeId'";
			if($db->query($updateBarcode) !== TRUE)
			{
				return array('status' => false, 'message' => $db->error);
			}
		}
		else
		{
			$insertBarcode = "INSERT INTO wms_itemlist_barcodes (`item_id`,`barcode`,`is_primary`,`added_by`,`date_added`,`active`) ";
			$insertBarcode .= "VALUES('$itemId','$barcode','$is_primary','$app_user','$date_user',1)";
			if($db->query($insertBarcode) !== TRUE)
			{
				return array('status' => false, 'message' => $db->error);
			}
		}
	}
	return array('status' => true, 'message' => '');
}

function getItemConversionTable($db)
{
	$conversion_table = 'wms_itemlist_conversion';
	$checkConvTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_conversion'");
	if(!$checkConvTable || $checkConvTable->num_rows == 0)
	{
		$checkConvTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_converssion'");
		if($checkConvTable2 && $checkConvTable2->num_rows > 0)
		{
			$conversion_table = 'wms_itemlist_converssion';
		} else {
			$conversion_table = '';
		}
	}
	return $conversion_table;
}

function saveItemConversion($db, $itemId, $uom_from, $uom_to, $factor)
{
	$itemId = (int)$itemId;
	if($itemId <= 0)
	{
		return array('status' => false, 'message' => 'Invalid item id.');
	}

	$uom_from = trim($uom_from);
	$uom_to = trim($uom_to);
	$factor = trim((string)$factor);
	$conversion_table = getItemConversionTable($db);
	if($conversion_table == '')
	{
		return array('status' => false, 'message' => 'Conversion table not found.');
	}

	if($db->query("DELETE FROM $conversion_table WHERE item_id='$itemId'") !== TRUE)
	{
		return array('status' => false, 'message' => $db->error);
	}

	if($uom_from == '' || $uom_to == '' || $factor == '')
	{
		return array('status' => true, 'message' => '');
	}

	$factorValue = (float)$factor;
	if($factorValue <= 0)
	{
		return array('status' => false, 'message' => 'Invalid conversion factor.');
	}

	$uomFromEsc = mysqli_real_escape_string($db, $uom_from);
	$uomToEsc = mysqli_real_escape_string($db, $uom_to);
	$insertConv = "INSERT INTO $conversion_table (`item_id`,`uom_from`,`uom_to`,`factor`) VALUES ('$itemId','$uomFromEsc','$uomToEsc','$factorValue')";
	if($db->query($insertConv) !== TRUE)
	{
		return array('status' => false, 'message' => $db->error);
	}

	return array('status' => true, 'message' => '');
}

if($mode == 'add')
{
	$barcodeConflict = getBarcodeConflict($db, 0, $cleanBarcodes);
	if($barcodeConflict != '')
	{
		print_r('
			<script>
				swal("Warning", "Barcode already exists on another item: '.$barcodeConflict.'", "warning");
			</script>
		');
		exit();
	}

	$query = "SELECT * FROM wms_itemlist WHERE item_description='$item_description'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
	    	$column = "`recipient`,`item_location`,`item_code`,`qr_code`,`category`,`class`,`item_description`,`uom`,`added_by`,`date_added`,`active`,`unit_price`";	    	
	    	$insert = "'$recipient','$item_location','$item_code','$qr_code','$category','$class','$item_description','$uom','$app_user','$date_user','$active','$unit_price'";
	    	if(hasWmsColumn($db, 'supplier_id'))
	    	{
	    		$column .= ",`supplier_id`";
	    		$insert .= ",'$supplier_id'";
	    	}
	    	if(hasWmsColumn($db, 'yield_perbatch'))
	    	{
	    		$column .= ",`yield_perbatch`";
	    		$insert .= ",'$yieldperbatch'";
	    	}
	    	if(hasWmsColumn($db, 'base_uom'))
	    	{
	    		$column .= ",`base_uom`";
	    		$insert .= ",'$base_uom'";
	    	}
	    	if(hasWmsColumn($db, 'item_type'))
	    	{
	    		$column .= ",`item_type`";
	    		$insert .= ",'$item_type'";
	    	}
	    	if(hasWmsColumn($db, 'item_status'))
	    	{
	    		$column .= ",`item_status`";
	    		$insert .= ",'$item_status'";
	    	}
	    	if(hasWmsColumn($db, 'brand'))
	    	{
	    		$column .= ",`brand`";
	    		$insert .= ",'$brand'";
	    	}
	    	if(hasWmsColumn($db, 'model'))
	    	{
	    		$column .= ",`model`";
	    		$insert .= ",'$model'";
	    	}
	    	if(hasWmsColumn($db, 'selling_price'))
	    	{
	    		$column .= ",`selling_price`";
	    		$insert .= ",'$selling_price'";
	    	}
	    	if(hasWmsColumn($db, 'standard_cost'))
	    	{
	    		$column .= ",`standard_cost`";
	    		$insert .= ",'$standard_cost'";
	    	}
	    	if(hasWmsColumn($db, 'last_purchase_cost'))
	    	{
	    		$column .= ",`last_purchase_cost`";
	    		$insert .= ",'$last_purchase_cost'";
	    	}
	    	if(hasWmsColumn($db, 'moving_average_cost'))
	    	{
	    		$column .= ",`moving_average_cost`";
	    		$insert .= ",'$moving_average_cost'";
	    	}
	    	if(hasWmsColumn($db, 'cost_method'))
	    	{
	    		$column .= ",`cost_method`";
	    		$insert .= ",'$cost_method'";
	    	}
	    	if(hasWmsColumn($db, 'reorder_point'))
	    	{
	    		$column .= ",`reorder_point`";
	    		$insert .= ",'$reorder_point'";
	    	}
	    	if(hasWmsColumn($db, 'reorder_qty'))
	    	{
	    		$column .= ",`reorder_qty`";
	    		$insert .= ",'$reorder_qty'";
	    	}
	    	if(hasWmsColumn($db, 'safety_stock'))
	    	{
	    		$column .= ",`safety_stock`";
	    		$insert .= ",'$safety_stock'";
	    	}
	    	if(hasWmsColumn($db, 'lead_time_days'))
	    	{
	    		$column .= ",`lead_time_days`";
	    		$insert .= ",'$lead_time_days'";
	    	}
	    	if(hasWmsColumn($db, 'average_leadtime'))
	    	{
	    		$column .= ",`average_leadtime`";
	    		$insert .= ",'$average_leadtime'";
	    	}
	    	if(hasWmsColumn($db, 'max_leadtime'))
	    	{
	    		$column .= ",`max_leadtime`";
	    		$insert .= ",'$max_leadtime'";
	    	}
	    	if(hasWmsColumn($db, 'shelf_life_days'))
	    	{
	    		$column .= ",`shelf_life_days`";
	    		$insert .= ",'$shelf_life_days'";
	    	}
	    	if(hasWmsColumn($db, 'is_lot_tracked'))
	    	{
	    		$column .= ",`is_lot_tracked`";
	    		$insert .= ",'$is_lot_tracked'";
	    	}
	    	if(hasWmsColumn($db, 'is_serial_tracked'))
	    	{
	    		$column .= ",`is_serial_tracked`";
	    		$insert .= ",'$is_serial_tracked'";
	    	}
		$queryInsert = "INSERT INTO wms_itemlist ($column)";

		$queryInsert .= "VALUES($insert)";
		$db->begin_transaction();
		try
		{
			if ($db->query($queryInsert) !== TRUE)
			{
				throw new Exception($db->error);
			}
			$itemId = $db->insert_id;
			$barcodeSave = saveItemBarcodes($db, $itemId, $cleanBarcodes, $primary_barcode, $app_user, $date_user);
			if($barcodeSave['status'] !== true)
			{
				throw new Exception($barcodeSave['message']);
			}
			$conversionSave = saveItemConversion($db, $itemId, $conv_uom_from, $conv_uom_to, $conv_factor);
			if($conversionSave['status'] !== true)
			{
				throw new Exception($conversionSave['message']);
			}
			$mappingSave = syncItemModuleVisibility($db, $itemId, $recipient, $app_user);
			if($mappingSave['status'] !== true)
			{
				throw new Exception($mappingSave['message']);
			}
			$db->commit();

			print_r('
				<script>
					load_data("'.$limit.'");
					swal("Success", "Item has been successfully added", "success");
					closeModal("formmodal");
				</script>
			');
		}
		catch (Throwable $e)
		{
			$db->rollback();
			print_r('
				<script>
					swal("Warning", "'.$e->getMessage().'", "warning");
				</script>
			');
		}
	} else {
		print_r('
			<script>
				swal("Warning", "Item is already exists.", "warning");
			</script>
		');
	}
}
if($mode == 'edit') {
	$barcodeConflict = getBarcodeConflict($db, $rowid, $cleanBarcodes);
	if($barcodeConflict != '')
	{
		print_r('
			<script>
				swal("Warning", "Barcode already exists on another item: '.$barcodeConflict.'", "warning");
			</script>
		');
		exit();
	}

	$update = "recipient='$recipient',item_location='$item_location',item_code='$item_code',qr_code='$qr_code',category='$category',class='$class',item_description='$item_description',uom='$uom',updated_by='$app_user',date_updated='$date_user',active='$active',unit_price='$unit_price'";
	if(hasWmsColumn($db, 'supplier_id'))
	{
		$update .= ",supplier_id='$supplier_id'";
	}
	if(hasWmsColumn($db, 'yield_perbatch'))
	{
		$update .= ",yield_perbatch='$yieldperbatch'";
	}
	if(hasWmsColumn($db, 'base_uom'))
	{
		$update .= ",base_uom='$base_uom'";
	}
	if(hasWmsColumn($db, 'item_type'))
	{
		$update .= ",item_type='$item_type'";
	}
	if(hasWmsColumn($db, 'item_status'))
	{
		$update .= ",item_status='$item_status'";
	}
	if(hasWmsColumn($db, 'brand'))
	{
		$update .= ",brand='$brand'";
	}
	if(hasWmsColumn($db, 'model'))
	{
		$update .= ",model='$model'";
	}
	if(hasWmsColumn($db, 'selling_price'))
	{
		$update .= ",selling_price='$selling_price'";
	}
	if(hasWmsColumn($db, 'standard_cost'))
	{
		$update .= ",standard_cost='$standard_cost'";
	}
	if(hasWmsColumn($db, 'last_purchase_cost'))
	{
		$update .= ",last_purchase_cost='$last_purchase_cost'";
	}
	if(hasWmsColumn($db, 'moving_average_cost'))
	{
		$update .= ",moving_average_cost='$moving_average_cost'";
	}
	if(hasWmsColumn($db, 'cost_method'))
	{
		$update .= ",cost_method='$cost_method'";
	}
	if(hasWmsColumn($db, 'reorder_point'))
	{
		$update .= ",reorder_point='$reorder_point'";
	}
	if(hasWmsColumn($db, 'reorder_qty'))
	{
		$update .= ",reorder_qty='$reorder_qty'";
	}
	if(hasWmsColumn($db, 'safety_stock'))
	{
		$update .= ",safety_stock='$safety_stock'";
	}
	if(hasWmsColumn($db, 'lead_time_days'))
	{
		$update .= ",lead_time_days='$lead_time_days'";
	}
	if(hasWmsColumn($db, 'average_leadtime'))
	{
		$update .= ",average_leadtime='$average_leadtime'";
	}
	if(hasWmsColumn($db, 'max_leadtime'))
	{
		$update .= ",max_leadtime='$max_leadtime'";
	}
	if(hasWmsColumn($db, 'shelf_life_days'))
	{
		$update .= ",shelf_life_days='$shelf_life_days'";
	}
	if(hasWmsColumn($db, 'is_lot_tracked'))
	{
		$update .= ",is_lot_tracked='$is_lot_tracked'";
	}
	if(hasWmsColumn($db, 'is_serial_tracked'))
	{
		$update .= ",is_serial_tracked='$is_serial_tracked'";
	}
	$queryDataUpdate = "UPDATE wms_itemlist SET $update WHERE id='$rowid'";
	$db->begin_transaction();
	try
	{
		if ($db->query($queryDataUpdate) !== TRUE)
		{
			throw new Exception($db->error);
		}
		$barcodeSave = saveItemBarcodes($db, $rowid, $cleanBarcodes, $primary_barcode, $app_user, $date_user);
		if($barcodeSave['status'] !== true)
		{
			throw new Exception($barcodeSave['message']);
		}
		$conversionSave = saveItemConversion($db, $rowid, $conv_uom_from, $conv_uom_to, $conv_factor);
		if($conversionSave['status'] !== true)
		{
			throw new Exception($conversionSave['message']);
		}
		$mappingSave = syncItemModuleVisibility($db, $rowid, $recipient, $app_user);
		if($mappingSave['status'] !== true)
		{
			throw new Exception($mappingSave['message']);
		}
		$db->commit();

		print_r('
			<script>
				load_data("'.$limit.'");
				swal("Success", "Item has been successfully updated", "success");
			</script>
		');		
 	}
	catch (Throwable $e)
	{
		$db->rollback();
		print_r('
			<script>
				swal("Warning", "'.$e->getMessage().'", "warning");
			</script>
		');
	}
}

