<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mode = $_POST['mode'];

$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");


if($mode == 'createreceivingupdatedetails')
{
	$rowid = $_POST['rowid'];
	$transaction_type = $_POST['transaction_type'];
	$receiving_id  = $_POST['receiving_id'];
	$supplier_id = $_POST['supplier_id'];
	$po_no = $_POST['po_no'];
	$si_no = $_POST['si_no'];
	$transaction_type = $_POST['transaction_type'];
	$item_code = $_POST['item_code'];
	$item_description = $_POST['item_description'];
	$category = $_POST['category'];
	$quantity_received = $_POST['quantity'];
	$uom = $_POST['uom'];
	$unit_price = $_POST['unit_price'];
	$received_date = $_POST['received_date'];
	$expiration_date = $_POST['expiration_date'];
	$total_cost = ($quantity_received * $unit_price);

	$update = "
		transaction_type='$transaction_type',item_description='$item_description',category='$category',quantity_received='$quantity_received',uom='$uom',unit_price='$unit_price',total_cost='$total_cost',received_date='$received_date',expiration_date='$expiration_date'";
		$queryDataUpdate = "UPDATE wms_receiving_details SET $update WHERE receiving_detail_id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
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
if($mode == 'createreceivingdetails')
{
	$transaction_type = $_POST['transaction_type'];
	$receiving_id  = $_POST['receiving_id'];
	$supplier_id = $_POST['supplier_id'];
	$po_no = $_POST['po_no'];
	$si_no = $_POST['si_no'];
	$transaction_type = $_POST['transaction_type'];
	$item_code = $_POST['item_code'];
	$item_description = $_POST['item_description'];
	$category = $_POST['category'];
	$quantity_received = $_POST['quantity'];
	$uom = $_POST['uom'];
	$unit_price = $_POST['unit_price'];
	$received_date = $_POST['received_date'];
	$expiration_date = $_POST['expiration_date'];
	$total_cost = ($quantity_received * $unit_price);
	$query = "SELECT * FROM wms_receiving_details WHERE receiving_id='$receiving_id' AND item_code='$item_code' AND po_no='$po_no' AND si_no='$si_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`receiving_id`,`po_no`,`si_no`,`transaction_type`,`supplier_id`,`item_code`,`item_description`,`category`,`quantity_received`,`uom`,`unit_price`,`total_cost`,`received_by`,`received_date`,`expiration_date`,`date_created`";    	
		$insert = "'$receiving_id','$po_no','$si_no','$transaction_type','$supplier_id','$item_code','$item_description','$category','$quantity_received','$uom','$unit_price','$total_cost','$app_user','$received_date','$expiration_date','$date_user'";
		$queryInsert = "INSERT INTO wms_receiving_details ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			print_r('
				<script>
					swal("Success", "Receiving Details has been successfully added", "success");
					load_dtls_contents('.$receiving_id.','.$supplier_id.');
					reloadForm('.$receiving_id.','.$supplier_id.');
				</script>
			');
		} else {
		echo $db->error;
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
if($mode == 'add')
{
	$limit = $_POST['limit'];
	$rowid = $_POST['rowid'];
	$supplier_id = $_POST['supplier'];
	$po_no = $_POST['po_no'];
	$si_no = $_POST['si_no'];
	//$total_cost = $_POST['total_cost'];
	//$delivery_status = $_POST['deliverystatus'];
	$status = $_POST['status'];

	$query = "SELECT * FROM wms_receiving WHERE po_no='$po_no' AND si_no='$si_no'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
		$column = "`supplier_id`,`po_no`,`si_no`,`created_by`,`date_created`,`receiving_status`";    	
		$insert = "'$supplier_id','$po_no','$si_no','$app_user','$date_user','$status'";
		$queryInsert = "INSERT INTO wms_receiving ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			print_r('
				<script>
					load_data("'.$limit.'");
					swal("Success", "Receiving has been successfully added", "success");
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
				swal("Warning", "Receiving with P.O. ('.$po_no.') is already exists.", "warning");
			</script>
		');
	}
}
if($mode == 'edit') {
	$update = "item_code='$item_code',category='$category',item_description='$item_description',uom='$uom',conversion='$conversion',updated_by='$app_user',date_updated='$date_user',active='$active',unit_price='$unit_price',supplier_id='$supplier_id'";
	$queryDataUpdate = "UPDATE wms_itemlist SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
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
