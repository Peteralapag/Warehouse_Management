<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$control_no = $_POST['control_no'];
if($function->GetOrderStatus($control_no,'delivery_date',$db) == '')
{
	$date = date("Y-m-d");
} else {
	$date = $function->GetOrderStatus($control_no,'delivery_date',$db);
}
if($function->GetOrderStatus($control_no,'delivery_driver',$db) == NULL && $function->GetOrderStatus($control_no,'plate_number',$db) == NULL)
{
	if(isset($_SESSION['LOGISTIC_DRIVER']))
	{
		$driver = $_SESSION['LOGISTIC_DRIVER'];
		$plate = $_SESSION['LOGISTIC_PLATE'];
	} else {
		$driver = "";
		$plate = "";
	}
} else {
	$driver = $function->GetOrderStatus($control_no,'delivery_driver',$db);
	$plate = $function->GetOrderStatus($control_no,'plate_number',$db);
}
echo $driver;
?>
<style>
.lamesa td {
	border: 0 !important;
}
</style>
<div style="width:450px">
	<table style="width: 100%" class="table lamesa">
		<tr>
			<td>Delivery Date</td>
			<td><input id="delivery_date" type="date" class="form-control" placeholder="Delivery Date" value="<?php echo $date; ?>"></td>
		</tr>
		<tr>
			<td>Driver Name</td>
			<td><input id="delivery_driver" type="text" class="form-control" placeholder="Driver Name" value="<?php echo $driver; ?>"></td>
		</tr>
		<tr>
			<td>Plate Number</td>
			<td><input id="plate_number" type="text" class="form-control" placeholder="Plate Number" value="<?php echo $plate; ?>"></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align:right">
				<button class="btn btn-success" onclick="saveLogisticInfo('<?php echo $control_no; ?>')">Save</button>
				<button class="btn btn-danger" onclick="closeModal('formmodal')">Close</button>
			</td>
		</tr>
	</table>
</div>
<div class="logisticresults">
<script>
function saveLogisticInfo(controlno)
{
	var mode = 'savelogisticinfo';
	var delivery_date = $('#delivery_date').val();
	var delivery_driver = $('#delivery_driver').val();
	var plate_number = $('#plate_number').val();
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, delivery_date: delivery_date, delivery_driver: delivery_driver, plate_number: plate_number },
	function(data) {		
		$('.logisticresults').html(data);
	//	closeModal('formmodal');
	});
}
</script>