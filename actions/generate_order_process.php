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
$form_type = $_POST['form_type'];
$control_no = $_POST['mrs_no'];
$trans_date = $_POST['trans_date'];
$branch = $_POST['branch'];
$recipient = $_POST['recipient'];
$created_by = $_POST['created_by'];
$priority = $_POST['priority'];
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");

if($mode == 'newrequest')
{
	$controlno = str_replace($year, "", $control_no);	
	$queryCN = "SELECT * FROM wms_form_numbering WHERE mrs_number='$controlno'";
	$checkCN = mysqli_query($db, $queryCN);    
    if ( $checkCN->num_rows > 0 ) 
    {
		$number  = intval($controlno);
		$number += 1;
		$new_control_no = str_pad($number, strlen($controlno), '0', STR_PAD_LEFT);
    } else {
    	$control_no_ = str_replace($year, "", $function->GetMRSNumber($db));
    	$number  = intval($control_no_);
		$number += 1;
		$new_control_no = str_pad($number, strlen($control_no_), '0', STR_PAD_LEFT);
    }
	$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`form_type`,`control_no`,`trans_date`,`branch`,`recipient`,`created_by`,`priority`,`date_created`";
    	$insert = "'$form_type','$control_no','$trans_date','$branch','$recipient','$created_by','$priority','$date_user'";
		$queryInsert = "INSERT INTO wms_order_request ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			$queryDataUpdate = "UPDATE wms_form_numbering SET mrs_number='$new_control_no' WHERE id=1";
			if ($db->query($queryDataUpdate) === TRUE)
			{
				print_r('
					<script>
						reload_data();
						swal("Success", "Request has been successfully added", "success");
						closeModal("formmodal");
					</script>
				');
			} else {
				print_r('
					<script>
						swal("Numbering Error:", "'.$db->error.'", "warning");
					</script>
				');
			}
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
				swal("Warning", "Order with '.$control_no.' number is already exists.", "warning");
			</script>
		');
	}
}
if($mode == 'updaterequest')
{
	$request_id = $_POST['rowid'];
	$status = $_POST['status'];
	$update = "branch='$branch',form_type='$form_type',recipient='$recipient',trans_date='$trans_date',updated_by='$app_user',date_updated='$date_user',priority='$priority',status='$status'";
	$queryDataUpdate = "UPDATE wms_order_request SET $update WHERE request_id='$request_id'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				swal("Success", "Request has been successfully updated", "success");
				reload_data();
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