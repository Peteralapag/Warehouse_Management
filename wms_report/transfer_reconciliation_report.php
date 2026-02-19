<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_REPORT_PAGE'] = $_POST['reports'];
if(isset($_SESSION['WMS_DATE_FROM']))
{
	$df = $_SESSION['WMS_DATE_FROM'];
} else {
	$df = date("Y-m-d");
}
if(isset($_SESSION['WMS_DATE_TO']))
{
	$dt = $_SESSION['WMS_DATE_TO'];
} else {
	$dt = date("Y-m-d");
}
if(isset($_SESSION['WMS_RECON_CLUSTER']))
{
	$rec_cluster = $_SESSION['WMS_RECON_CLUSTER'];
} else {
	$rec_cluster = '';
}
$_SESSION['WMS_RECIPIENT_CONSUMPTION'] = $_POST['recipient'];
?>
<style>
.recon-wrapper {margin-top: 5px;display: flex;height: calc(100vh - 180px);flex-direction: column;width: 100%;gap: 10px;}
.recon-header input{width: 150px}
.recon-header {display: flex;border: 1px solid red;position: relative;padding: 10px;background: #fff;border: 1px solid #aeaeae;flex-direction: row;gap: 5px}
.recon-data {display: block;flex: 1;border: 1px solid #f1f1f1;padding:2px;background: #fff;overflow: auto;}
.recon-date-select {display: flex;	gap :5px;width: 550px;align-items: center}
.recon-right {margin-left: auto;}
.recon-right button {width: 100px}
</style>

<div class="recon-wrapper">
	<div class="recon-header">
		<div class="recon-date-select">
			<select id="b_cluster" class="form-control form-control-sm">
				<?php echo $function->GetReportCluster($rec_cluster,$db)?>
			</select>
			<input id="date_from" type="date" class="form-control form-control-sm" value="<?php echo $df?>">
			<span><i class="fa-sharp-duotone fa-regular fa-chevrons-left color-orange"></i></span>
			<span> <i class="fa-sharp-duotone fa-regular fa-chevrons-right color-orange"></i></span>
			<input id="date_to" type="date" class="form-control form-control-sm" value="<?php echo $dt?>">			
		</div>
		<button class="btn btn-info btn-sm color-white" onclick="getReconData()"><i class="fa-solid fa-circle-down color-orange"></i>&nbsp; Reconcile Data</button>
		<span class="recon-right">
			<button  id="copyButton" class="btn btn-success btn-sm"><i class="fa-solid fa-clipboard"></i>&nbsp; Copy</button>
		</span>
	</div>
	<div class="recon-data" id="recondata"></div>
</div>
<script>
$(document).ready(function() {
    if ($('#recondata').is(':empty')) {
        $('#copyButton').prop('disabled', true);
    } else {
        $('#copyButton').prop('disabled', false);
    }
});
function getReconData()
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
	$.post("./Modules/Warehouse_Management/wms_report/transfer_reconciliation_report_data.php", { recipient: recipient, cluster: cluster, date_from: date_from, date_to: date_to },
	function(data) {
		$('#recondata').html(data);	
		rms_reloaderOff();
	});
}
document.getElementById("copyButton").addEventListener("click", function() {
    rms_reloaderOn('Copying...');
    var reconData = document.getElementById("recondata");

    if (reconData.textContent.trim() === "") {
        swal({title: "Error!", text: "No content to copy.", icon: "error", button: "OK"});
        rms_reloaderOff();
        return;
    }

    var range = document.createRange();
    range.selectNode(reconData);
    window.getSelection().addRange(range);

    try {
        var success = document.execCommand('copy');
        if (success) {        	
            swal({title: "Success!", text: "Content copied to clipboard!", icon: "success", button: "OK"});
        } else {
            swal({title: "Failed!", text: "Failed to copy content.", icon: "error", button: "Try Again"});
        }
                rms_reloaderOff();
    } catch (error) {
        swal({title: "Error!", text: "Error copying content.", icon: "error", button: "Try Again"});
        console.error(error);
    } finally {
        window.getSelection().removeAllRanges();
    }
});
</script>
