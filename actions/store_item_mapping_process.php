<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$item_code = isset($_POST['item_code']) ? trim($_POST['item_code']) : '';
$store_item_id = isset($_POST['store_item_id']) ? intval($_POST['store_item_id']) : 0;

if(isset($_SESSION['wms_appnameuser']))
{
	$app_user = strtolower($_SESSION['wms_appnameuser']);
	$app_user = ucwords($app_user);
} else {
	$app_user = 'System';
}
$date_time = date("Y-m-d H:i:s");

if($item_code == '')
{
	echo '
		<script>
			swal("Warning","Invalid mapping data","warning");
		</script>
	';
	exit();
}

$mapping_table = 'wms_item_mapping';
$checkMapTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_item_mapping'");
if(!$checkMapTable || $checkMapTable->num_rows == 0)
{
	$checkMapTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_item_maaping'");
	if($checkMapTable2 && $checkMapTable2->num_rows > 0)
	{
		$mapping_table = 'wms_item_maaping';
	}
}

if($store_item_id <= 0)
{
	$queryDelete = "DELETE FROM $mapping_table WHERE wms_item_code='$item_code'";
	if($db->query($queryDelete) === TRUE)
	{
		echo '
			<script>
				swal("Success","Item mapping removed successfully","success");
				closeModal("formmodal");
				if(typeof load_data === "function")
				{
					load_data();
				}
			</script>
		';
	} else {
		echo '
			<script>
				swal("Error","Unable to remove mapping. Please try again.","error");
			</script>
		';
	}
	exit();
}

$existingCols = array();
$colRes = mysqli_query($db, "SHOW COLUMNS FROM $mapping_table");
if($colRes)
{
	while($col = mysqli_fetch_assoc($colRes))
	{
		$existingCols[] = $col['Field'];
	}
}

$deletePrevious = "DELETE FROM $mapping_table WHERE wms_item_code='$item_code'";
$db->query($deletePrevious);

$insertCols = array();
$insertVals = array();
if(in_array('store_item_id', $existingCols)) { $insertCols[] = 'store_item_id'; $insertVals[] = "'$store_item_id'"; }
if(in_array('wms_item_code', $existingCols)) { $insertCols[] = 'wms_item_code'; $insertVals[] = "'$item_code'"; }
if(in_array('date_mapped', $existingCols)) { $insertCols[] = 'date_mapped'; $insertVals[] = "'$date_time'"; }
if(in_array('mapped_by', $existingCols)) { $insertCols[] = 'mapped_by'; $insertVals[] = "'$app_user'"; }
if(in_array('status', $existingCols)) { $insertCols[] = 'status'; $insertVals[] = "'1'"; }
if(in_array('notes', $existingCols)) { $insertCols[] = 'notes'; $insertVals[] = "NULL"; }

if(count($insertCols) == 0)
{
	echo '
		<script>
			swal("Warning","Unable to save. No compatible columns found.","warning");
		</script>
	';
	exit();
}

$checkAssigned = "SELECT wms_item_code FROM $mapping_table WHERE store_item_id='$store_item_id' AND status=1 LIMIT 1";
$assignedRes = $db->query($checkAssigned);
if($assignedRes && $assignedRes->num_rows > 0)
{
	$assignedRow = mysqli_fetch_assoc($assignedRes);
	$assignedItemCode = $assignedRow['wms_item_code'];
	if($assignedItemCode != $item_code)
	{
		echo '
			<script>
				swal("Warning","This Store Item is already assigned to WMS Item Code: '.$assignedItemCode.'","warning");
			</script>
		';
		exit();
	}
}

$cleanupInactive = "DELETE FROM $mapping_table WHERE store_item_id='$store_item_id' AND status=0";
$db->query($cleanupInactive);

$queryInsert = "INSERT INTO $mapping_table (".implode(',', $insertCols).") VALUES (".implode(',', $insertVals).")";
try
{
	if ($db->query($queryInsert) === TRUE)
	{
		echo '
			<script>
				swal("Success","Item mapping saved successfully","success");
				closeModal("formmodal");
				if(typeof load_data === "function")
				{
					load_data();
				}
			</script>
		';
	}
}
catch (Throwable $e)
{
	$msg = $e->getMessage();
	if(stripos($msg, 'Duplicate entry') !== false)
	{
		echo '
			<script>
				swal("Warning","Duplicate entry not allowed. This Store Item is already assigned by another item.","warning");
			</script>
		';
	} else {
		echo '
			<script>
				swal("Error","Unable to save mapping. Please try again.","error");
			</script>
		';
	}
}
