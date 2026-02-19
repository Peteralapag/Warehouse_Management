<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;
$year = date("Y-");
if(isset($_POST['mode']))
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
$editid = $_POST['editid'];
$rowid = $_POST['rowid'];
$branch = $_POST['branch'];
$control_no = $_POST['control_no'];
$item_code = $_POST['item_code'];
$item_description = $_POST['item_description'];
$uom = $_POST['uom'];
// $unit_price = $_POST['unit_price'];
$quantity = $_POST['quantity'];
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");
$trans_date = date("Y-m-d");

if($mode == 'saveorder')
{
	$query = "SELECT * FROM wms_branch_order WHERE item_code='$item_code' AND control_no='$control_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`branch`,`control_no`,`item_code`,`item_description`,`uom`,`quantity`,`created_by`,`trans_date`,`date_created`";
    	$insert = "'$branch','$control_no','$item_code','$item_description','$uom','$quantity','$app_user','$trans_date','$date_user'";
		$queryInsert = "INSERT INTO wms_branch_order ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			echo $function->DoAuditLogs($date_user,'Item has been successfully added',$app_user,$db);
			print_r('
				<script>
					swal("Success", "Item has been successfully added", "success");
					orderDetails('.$rowid.');
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
				swal("Warning", "Item name '.$item_description.' is already exists.", "warning");
			</script>
		');
	}	
}
if($mode == 'updateorder')
{
	$update = "item_code='$item_code',item_description='$item_description',uom='$uom',quantity='$quantity',updated_by='$app_user',date_updated='$date_user'";
	$queryDataUpdate = "UPDATE wms_branch_order SET $update WHERE id='$editid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo $function->DoAuditLogs($date_user,'Order has been successfully updated',$app_user,$db)
		print_r('
			<script>
				swal("Success", "Order has been successfully updated", "success");
				orderDetails('.$rowid.');
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
?>