<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

if(isset($_SESSION['wms_appnameuser']))
{
	$app_user = strtolower($_SESSION['wms_appnameuser']);
	$app_user = ucwords($app_user);
} else {
	echo "Please Relogin";
	exit();
}

$date = date("Y-m-d");
$date_time = date("Y-m-d H:i:s");

	$rowid = $_POST['rowid'];	
	$sqlQuery = "SELECT * FROM wms_return_to_vendor WHERE id='$rowid'";
	$results = mysqli_query($db, $sqlQuery);    
	while($DATA = mysqli_fetch_array($results))  
	{
		$details_id = $DATA['details_id'];
		$item_code = $DATA['item_code'];
		$return_quantity = $DATA['return_quantity'];
		$quantity_received = $DATA['quantity_received'];
	}
	
	$curr_stock = $function->GetItemStock($item_code,$db);
	
	if($return_quantity > $curr_stock)
	{
		echo '
			<script>
				swal("Inventory", "The request cannot be processed because the Return Quantity exceeds the Current Inventory Stock.", "error");
			</script>
		';
		exit();	
	}

	$new_qty_received = $quantity_received - $return_quantity;
	$new_stock = $curr_stock - $return_quantity;
	
	$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$new_stock' WHERE item_code='$item_code'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$queryRTVUpdate = "UPDATE wms_return_to_vendor SET committed_by='$app_user',committed=1,date_committed='$date_time',stock_after_return='$new_stock' WHERE id='$rowid'";
		if ($db->query($queryRTVUpdate) === TRUE)
		{
			$queryDetailsUpdate = "UPDATE wms_receiving_details SET `old_qty_received`=quantity_received, quantity_received='$new_qty_received' WHERE receiving_detail_id='$details_id'";
			if ($db->query($queryDetailsUpdate) === TRUE)
			{
				echo '
					<script>
						swal("Success","Request has been updated successfully", "success");
						requestRTVApproveEdit("'.$rowid.'");
						loadRTVData();
						$("#formodalsm").fadeOut();
					</script>
				';
			} else {
				echo "OLD QUANTITY RECEIVED - RTV: ".$db->error;
			}
		} else {
			echo "RTV: ".$db->error;
		}
		
	} else {
		echo "INV STOCK: ".$db->error;
	}
	

