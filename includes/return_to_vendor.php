<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_SESSION['WMS_RTV_STATUS']))
{
	$filters = $_SESSION['WMS_RTV_STATUS'];
} else {
	$filters = "Pending";
}
?>
<style>
.pages-wrapper {display: flex;flex-direction: column;}
.pages-header {display: flex;justify-content: space-between;width: 100%;padding: 10px;border-radius: 7px 7px 0px 0px;border: 1px solid #aeaeae;
border-bottom: 5px solid #aeaeae;background: #fff;}
.pages-data {flex: 1;width: 100%;position:; background:#fff}
.select-control {display: flex;align-items: center;gap: 10px;width: auto;max-width: 100%;}
.select-control select {width: auto !important}
.reload-data {display: flex;gap: 15px;margin-left: auto;right:0;}
.branch-search {position:absolute;width: 100%;;max-height:250px;z-index:3;margin-top: 5px;border: 1px solid #f1f1f1;background: #fff;border-radius: 0px 0px 5px 5px;
box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.5);overflow: hidden;overflow-y: auto;}
.input-label {position: absolute;top:-12px;font-size:10px;left:5px;color: dodgerblue;letter-spacing:7px;font-weight: 600}
</style>
<div class="pages-wrapper">
    <div class="pages-header">
   		
    	<div class="select-control">
	    	<span style="position:relative">
    			<div class="input-label">Search</div>
    			<input id="searchrtv" type="text" class="form-control form-control-sm" placeholder="Search PO / Supplier">
    		</span>
    		<span>
    			<button class="btn btn-primary btn-sm" onclick="requestRTV()">Request R.T.V.</button>
    		</span>	
    		<span>
    			<select class="form-control form-control-sm" id="rtvstatus" onchange="loadRTVData()">
    				<?php echo $function->getRTVData($filters)?>
    			</select>
    		</span>
    		<span>(<span id="pendingstatus"></span>)</span>
    	</div>
    </div>
    <div class="pages-data" id="pagesdata"></div>
</div>
<script>
$(function()
{
	loadRTVData();
	getRTVStatus();	
	
});
function getRTVStatus()
{
	var rtvstatus = $('#rtvstatus').val();
	$.post("./Modules/Warehouse_Management/actions/get_pending_count.php", { rtvstatus: rtvstatus },
	function(data) {		
		$('#pendingstatus').html(data);
	});
}
function loadRTVData()
{
	var rtvstatus = $('#rtvstatus').val();
	rms_reloaderOn('Loading...');
	$.post("./Modules/Warehouse_Management/includes/return_to_vendor_data.php", { rtvstatus: rtvstatus },
	function(data) {		
		$('#pagesdata').html(data);
		getRTVStatus();
		rms_reloaderOff();
	});
}
function requestRTV()
{
	var supplier = $('#searchrtv').val();
	$('.modaltitle').text("RETURN TO VENDOR REQUEST");
	$.post("./Modules/Warehouse_Management/apps/rtv_form.php", { },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>