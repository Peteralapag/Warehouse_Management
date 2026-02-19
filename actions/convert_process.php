<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;

$rowid = $_POST['rowid'];
$transaction_type = $_POST['transaction_type'];
$item_code = $_POST['itemcode'];
$category = $_POST['category'];
$item_description = $_POST['item_description'];
$on_hand_before = $_POST['onhand_stock'];
$on_hand_after = $_POST['onhand_stock'];
$on_hand_uom = $_POST['takeout_uom'];
$itemcode = $_POST['seachitemcode'];
$takeout_amount = $_POST['takeout_amount'];
$takeout_uom = $_POST['takeout_uom'];
$transfer_amount = $_POST['takeout_amount'];
$transfer_uom = $_POST['takeout_uom'];
$amount_per_uom = $_POST['amount_per_uom'];
$amount_uom = $_POST['amount_uom'];
$convert_amount = $_POST['convert_amount'];
$convert_uom = $_POST['convert_oum'];
$transaction_date = date("Y-m-d");
$created_by = $_SESSION['wms_appnameuser'];
$created_date = date("Y-m-d H:i:s");
$on_hand_after = ($on_hand_before - $takeout_amount);
if($_POST['mode'] == 'new')
{
	$column = "`transaction_type`,`item_code`,`category`,`itemcode`,`item_description`,`on_hand_before`,`on_hand_after`,`on_hand_uom`,`takeout_amount`,`takeout_uom`,`amount_per_uom`,`amount_uom`,`transfer_amount`,`transfer_uom`,`transaction_date`,`convert_amount`,`convert_uom`,`created_by`,`created_date`";
	$insert = "'$transaction_type','$item_code','$category','$itemcode','$item_description','$on_hand_before','$on_hand_after','$on_hand_uom','$takeout_amount','$takeout_uom','$amount_per_uom','$amount_uom','$transfer_amount','$transfer_uom','$transaction_date','$convert_amount','$convert_uom','$created_by','$created_date'";
	$queryInsert = "INSERT INTO wms_warehouse_transfer ($column)";
	$queryInsert .= "VALUES($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		$lastInsertId = $db->insert_id;
		print_r('
			<script>
				var rowid = "'.$lastInsertId.'";
				var item = "'.$item_description.'";
				getItemToFormID("edit",rowid);
				swal("Success", "Item successfully Converted: " + item, "success");
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
if($_POST['mode'] == 'update')
{
	$update = "
		on_hand_after='$on_hand_after',on_hand_uom='$on_hand_uom',takeout_amount='$takeout_amount',takeout_uom='$takeout_uom',item_description='$item_description',
		amount_per_uom='$amount_per_uom',amount_uom='$amount_uom',transfer_amount='$transfer_amount',transfer_uom='$transfer_uom'
	";
	$queryDataUpdate = "UPDATE wms_warehouse_transfer SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				var rowid = "'.$rowid.'";
				var item = "'.$item_description.'";
				getItemToFormID("edit",rowid);
				swal("Success", "Record successfully Updated: " + item, "success");
			</script>
		');
	} else {
		echo $db->error;
	}
}
