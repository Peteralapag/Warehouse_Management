<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mode = $_POST['mode'];
$limit = $_POST['limit'];
$rowid = $_POST['rowid'];
$name = $_POST['name'];
$tin = $_POST['tin'];
$address = $_POST['address'];
$telephone = $_POST['telephone'];
$cellphone = $_POST['cellphone'];
$email = $_POST['email'];
$contact_person = $_POST['contact_person'];
$person_contact = $_POST['person_contact'];
$active = $_POST['active'];
$app_user = $_SESSION['wms_username'];
$date_user = date("Y-m-d H:i:s");
if($mode == 'add')
{
	$query = "SELECT * FROM wms_supplier WHERE name='$name'";
	$checkRes = mysqli_query($db, $query);    
    if ( $checkRes->num_rows === 0 ) 
    {
    	$column = "`name`,`address`,`tin`,`telephone`,`cellphone`,`email`,`date_added`,`added_by`,`contact_person`,`person_contact`,`active`";
    	$insert = "'$name','$address','$tin','$telephone','$cellphone','$email','$date_user','$app_user','$contact_person','$person_contact','$active'";
		$queryInsert = "INSERT INTO wms_supplier ($column)";
		$queryInsert .= "VALUES($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			print_r('
				<script>
					load_data("'.$limit.'");
					swal("Success", "Supplier successfully added", "success");
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
				swal("Warning", "Supplier is already exists.", "warning");
			</script>
		');
	}
}
if($mode == 'edit') {
	$update = "active='$active',name='$name',address='$address',telephone='$telephone',cellphone='$cellphone',email='$email',date_updated='$date_user',updated_by='$app_user',contact_person='$contact_person',person_contact='$person_contact'";

	$queryDataUpdate = "UPDATE wms_supplier SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				load_data("'.$limit.'");
				swal("Success", "Supplier successfully updated", "success");
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
