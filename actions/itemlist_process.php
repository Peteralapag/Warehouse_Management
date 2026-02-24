<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mode = $_POST['mode'];
$limit = $_POST['limit'];
$rowid = $_POST['rowid'];
$supplier_id = $_POST['supplier'];
$recipient = $_POST['recipient'];
$item_location = $_POST['item_location'];
$item_code = $_POST['item_code'];
$qr_code = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';
$category = $_POST['category'];
$class = $_POST['classification'];
$item_description = $_POST['item_description'];
$uom = $_POST['uom'];
$conv_uom_from = $uom;
$conv_uom_to = isset($_POST['conv_uom_to']) ? trim($_POST['conv_uom_to']) : '';
$conv_factor = isset($_POST['conv_factor']) ? trim($_POST['conv_factor']) : '';
$active = $_POST['active'];
$unit_price = $_POST['unit_price'];
$barcodes = isset($_POST['barcodes']) && is_array($_POST['barcodes']) ? $_POST['barcodes'] : array();
$primary_barcode = isset($_POST['primary_barcode']) ? trim($_POST['primary_barcode']) : '';
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");

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
	    	$column = "`recipient`,`item_location`,`item_code`,`qr_code`,`category`,`class`,`item_description`,`uom`,`added_by`,`date_added`,`active`,`unit_price`,`supplier_id`";	    	
	    	$insert = "'$recipient','$item_location','$item_code','$qr_code','$category','$class','$item_description','$uom','$app_user','$date_user','$active','$unit_price','$supplier_id'";
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

	$update = "recipient='$recipient',item_location='$item_location',item_code='$item_code',qr_code='$qr_code',category='$category',class='$class',item_description='$item_description',uom='$uom',updated_by='$app_user',date_updated='$date_user',active='$active',unit_price='$unit_price',supplier_id='$supplier_id'";
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

