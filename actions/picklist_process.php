<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");
$control_no = $_POST['control_no'];

$query = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
$result = mysqli_query($db, $query);
if (mysqli_num_rows($result) > 0) {
	$data = array();
    while ($row = mysqli_fetch_assoc($result))
    {			    
       $control_no = $row['control_no'];
       $item_code = $row['item_code'];
       $item_description = $row['item_description'];
       $quantity = $row['quantity'];
       $uom = $row['uom'];
       $trans_date = $row['trans_date'];
       $branch = $row['branch'];

		$queryChk = "SELECT * FROM wms_picklist WHERE control_no='$control_no' AND item_code='$item_code'";
		$chkResult = mysqli_query($db, $queryChk);	
		if (mysqli_num_rows($chkResult) > 0)
		{
			$save = 0;
		} else {
			array_push($data, ['branch'=>$branch,'control_no'=>$control_no,'item_code'=>$item_code,'item_description'=>$item_description,'quantity'=>$quantity,'uom'=>$uom,'trans_date'=>$trans_date]);
			$save = 1;		       			
		}
    }
    if($save == 1)
    {
    	saveToPickList($data,$function,$date_user,$app_user,$db);
    }
} else {
    echo 'No rows found.';
}

function saveToPickList($data,$function,$date_user,$app_user,$db)
{
	for($i = 0; $i < count($data); $i++)
	{
		$branch = $data[$i]['branch'];
		$control_no = $data[$i]['control_no'];
		$item_code = $data[$i]['item_code'];
		$itemname = $data[$i]['item_description'];
		$quantity = $data[$i]['quantity'];
		$uom = $data[$i]['uom'];
		$trans_date = $data[$i]['trans_date'];
		$column = "`branch`,`control_no`,`item_code`,`item_description`,`quantity`,`uom`,`trans_date`";
		$insert = "'$branch','$control_no','$item_code','$itemname','$quantity','$uom','$trans_date'";
		$queryInsert = "INSERT INTO wms_picklist ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{	
			$log_msg = 'Pick lists saved';
			print_r('
				<script>
					swal("Success","'.$log_msg.'", "success");
					rms_reloaderOff();
				</script>
			');
		} else {
			$log_msg = "Pick Lists Error: ".$db->error;
			echo $db->error;
		}		
	}
	echo $function->DoAuditLogs($date_user,$log_msg,$app_user,$db);	
}	
mysqli_close($db);
?>