<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$rowid = $_POST['rowid'];
$limit = $_POST['limit'];
$queryDataDelete = "DELETE FROM wms_itemlist WHERE id='$rowid' ";
if ($db->query($queryDataDelete) === TRUE)
{ 
	print_r('
		<script>
			load_data("'.$limit.'");
			swal("Success","Item has been removed", "success");
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
