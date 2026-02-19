<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "Branch_Ordering_System");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
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
$item_description = $_POST['item_description'];
$item_code = $_POST['item_code'];
$uom = $_POST['uom'];
$quantity = $_POST['quantity'];
$unit_price = $_POST['unit_price'];

$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");
$trans_date = date("Y-m-d");

if($mode == 'updatedunlistedorder')
{
	$update = "item_description='$item_description',item_code='$item_code',uom='$uom',quantity='$quantity',unit_price='$unit_price',updated_by='$app_user',date_updated='$date_user'";
	$queryDataUpdate = "UPDATE wms_branch_order_unlisted SET $update WHERE id='$editid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				$(".padinginfo").html("Successfuly Updated");
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
?>