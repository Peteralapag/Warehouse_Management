<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
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
$rowid = $_POST['rowid'];
$branch = $_POST['branch'];
$control_no = $_POST['control_no'];
$remarks = $_POST['remarks'];
if($mode == 'saveremarks')
{
	
	$query = "SELECT * FROM wms_branch_order_remarks WHERE branch='$branch' AND control_no='$control_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`branch`,`control_no`,`remarks`";
    	$insert = "'$branch','$control_no','$remarks'";
		$queryInsert = "INSERT INTO wms_branch_order_remarks ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			print_r('
				<script>
					swal("Success", "Remarks has been successfully added", "success");
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
				swal("Warning", "Remarks is already exists.", "warning");
			</script>
		');
	}
}
if($mode == 'updateremarks')
{
	$update = "remarks='$remarks'";
	$queryDataUpdate = "UPDATE wms_branch_order_remarks SET $update WHERE branch='$branch' AND control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				swal("Success", "Remarks has been successfully updated", "success");
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
