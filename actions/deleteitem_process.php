<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$rowid = $_POST['rowid'];
$limit = $_POST['limit'];
$conversion_table = 'wms_itemlist_conversion';
$checkConvTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_conversion'");
if(!$checkConvTable || $checkConvTable->num_rows == 0)
{
	$checkConvTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_converssion'");
	if($checkConvTable2 && $checkConvTable2->num_rows > 0)
	{
		$conversion_table = 'wms_itemlist_converssion';
	} else {
		$conversion_table = '';
	}
}
$queryBarcodeDelete = "DELETE FROM wms_itemlist_barcodes WHERE item_id='$rowid' ";
$db->query($queryBarcodeDelete);
if($conversion_table != '')
{
	$queryConversionDelete = "DELETE FROM $conversion_table WHERE item_id='$rowid' ";
	$db->query($queryConversionDelete);
}
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
