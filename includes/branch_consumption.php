<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['WMS_CONDATE_FROM']))
{
	$df = $_SESSION['WMS_CONDATE_FROM'];
} else {
	$df = date("Y-m-d");
}
if(isset($_SESSION['WMS_CONDATE_TO']))
{
	$dt = $_SESSION['WMS_CONDATE_TO'];
} else {
	$dt = date("Y-m-d");
}
if(isset($_SESSION['WMS_CONSUMPTION_CLUSTER']))
{
	$con_cluster = $_SESSION['WMS_CONSUMPTION_CLUSTER'];
} else {
	$con_cluster = '';
}
if(isset($_SESSION['WMS_RECIPIENT_CONSUMPTION']))
{
	$recipient = $_SESSION['WMS_RECIPIENT_CONSUMPTION'];
} else {
	$recipient = 'WAREHOUSE';
}
?>
<style>
.recon-wrapper {margin-top: 5px;display: flex;height: calc(100vh - 158px);flex-direction: column;width: 100%;gap: 10px}
.recon-header input{width: 150px}
.recon-header {display: flex;border: 1px solid red;position: relative;padding: 10px;background: #fff;border: 1px solid #aeaeae;flex-direction: row;gap: 5px}
.recon-data {display: block;flex: 1;border: 1px solid #f1f1f1;padding:2px;background: #fff;overflow: auto;}
.recon-date-select {display: flex;	gap :5px;width: 700px;align-items: center}
.recon-right {margin-left: auto;}
.recon-right button {width: 100px}
</style>
<div class="recon-wrapper">
	<div class="recon-header">
		<div class="recon-date-select">
			<select id="recipient" class="form-control form-control-sm" style="width:180px">
				<?php echo $function->GetRecipient($recipient,$db); ?>
			</select>
			<select id="b_cluster" class="form-control form-control-sm">
				<?php echo $function->GetReportCluster($con_cluster,$db)?>
			</select>
			<input id="date_from" type="date" class="form-control form-control-sm" value="<?php echo $df?>">
			<span><i class="fa-sharp-duotone fa-regular fa-chevrons-left color-orange"></i></span>
			<span> <i class="fa-sharp-duotone fa-regular fa-chevrons-right color-orange"></i></span>
			<input id="date_to" type="date" class="form-control form-control-sm" value="<?php echo $dt?>">			
		</div>
		<button class="btn btn-info btn-sm color-white" onclick="getConsumptionData()"><i class="fa-solid fa-circle-down color-orange"></i>&nbsp; Consumption Data</button>
		<!-- span class="recon-right">
			<button  id="copyButton" class="btn btn-success btn-sm"><i class="fa-solid fa-clipboard"></i>&nbsp; Copy</button>
		</span -->
	</div>
	<div class="recon-data" id="recondata"></div>
</div>
<script>
function getConsumptionData()
{
	var cluster = $('#b_cluster').val();
	var date_from  = $('#date_from').val();
	var date_to = $('#date_to').val();
	var recipient = $('#recipient').val();
	$('#recondata').empty();
	if(cluster === '')
	{
		swal("Invalid Cluster", "A cluster needs to be selected. Processing without a cluster will cause the system to crash due to heavy data load.", "error");
		return false;
	}
	if(date_from === '' && date_to === '')	
	{
		swal("Invalid Range", "Invalid date range", "error");
		return false;
	}
	rms_reloaderOn('Loading...');
	$.post("./Modules/Warehouse_Management/includes/branch_consumption_data.php", { recipient: recipient, cluster: cluster, date_from: date_from, date_to: date_to },
	function(data) {
		$('#recondata').html(data);	
		rms_reloaderOff();
	});
}
</script>