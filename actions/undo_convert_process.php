<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;

if($_POST['mode'] == 'deleteconvert')
{
	$rowid = $_POST['rowid'];	
	$delQuery = "SELECT * FROM wms_warehouse_transfer WHERE id='$rowid'";
	$delResults = mysqli_query($db, $delQuery);    
	if ( $delResults->num_rows > 0 ) 
	{
		while($DELROW = mysqli_fetch_array($delResults))  
		{
			$item_code = $DELROW['item_code'];
			$return_amount = $DELROW['takeout_amount'];
			$current_stock = $function->GetOnHand($item_code,$db);
			$total_undo = ($current_stock + $return_amount);
			proceedDelete($rowid,$db);
/*			
			$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$total_undo' WHERE item_code='$item_code'";
			if ($db->query($queryDataUpdate) === TRUE)
			{
				proceedDelete($rowid,$db);
			} else {
				echo "Undo Erroer: ".$db->error;
			}
*/			
		}
	} else {
	
		echo "The Item does not exists.";
	}
}
function proceedDelete($rowid,$db)
{
	$queryDataDelete = "DELETE FROM wms_warehouse_transfer WHERE id='$rowid'";
	if ($db->query($queryDataDelete) === TRUE)
	{ 
		print_r('
			<script>
				swal("Success","Successfuly deleting the Record","success");
				$("#" + sessionStorage.navwms).trigger("click");
			</script>
		');
	} else {
		echo $db->error;
	}
}
