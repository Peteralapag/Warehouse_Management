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
$conversion = $_POST['conversion'];
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
	$db->query("UPDATE wms_itemlist_barcodes SET active=0,is_primary=0 WHERE item_id='$itemId'");
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
			$db->query($updateBarcode);
		}
		else
		{
			$insertBarcode = "INSERT INTO wms_itemlist_barcodes (`item_id`,`barcode`,`is_primary`,`added_by`,`date_added`,`active`) ";
			$insertBarcode .= "VALUES('$itemId','$barcode','$is_primary','$app_user','$date_user',1)";
			$db->query($insertBarcode);
		}
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
	    	$column = "`recipient`,`item_location`,`item_code`,`qr_code`,`category`,`class`,`item_description`,`uom`,`conversion`,`added_by`,`date_added`,`active`,`unit_price`,`supplier_id`";	    	
	    	$insert = "'$recipient','$item_location','$item_code','$qr_code','$category','$class','$item_description','$uom','$conversion','$app_user','$date_user','$active','$unit_price','$supplier_id'";
		$queryInsert = "INSERT INTO wms_itemlist ($column)";

		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			$itemId = $db->insert_id;
			$barcodeSave = saveItemBarcodes($db, $itemId, $cleanBarcodes, $primary_barcode, $app_user, $date_user);
			if($barcodeSave['status'] !== true)
			{
				$db->query("DELETE FROM wms_itemlist WHERE id='$itemId'");
				print_r('
					<script>
						swal("Warning", "'.$barcodeSave['message'].'", "warning");
					</script>
				');
				exit();
			}
			print_r('
				<script>
					load_data("'.$limit.'");
					swal("Success", "Item has been successfully added", "success");
					closeModal("formmodal");
				</script>
			');
		} else {
			print_r('
				<script>
					swal("Warning", "'.$db->error.'", "warning");
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

	$update = "recipient='$recipient',item_location='$item_location',item_code='$item_code',qr_code='$qr_code',category='$category',class='$class',item_description='$item_description',uom='$uom',conversion='$conversion',updated_by='$app_user',date_updated='$date_user',active='$active',unit_price='$unit_price',supplier_id='$supplier_id'";
	$queryDataUpdate = "UPDATE wms_itemlist SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$barcodeSave = saveItemBarcodes($db, $rowid, $cleanBarcodes, $primary_barcode, $app_user, $date_user);
		if($barcodeSave['status'] !== true)
		{
			print_r('
				<script>
					swal("Warning", "'.$barcodeSave['message'].'", "warning");
				</script>
			');
			exit();
		}
		print_r('
			<script>
				load_data("'.$limit.'");
				swal("Success", "Item has been successfully updated", "success");
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
}

