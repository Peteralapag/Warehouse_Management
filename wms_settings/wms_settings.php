 <style>
.settings-wrapper {
	display: flex;
	margin-top:10px;
	height: calc(100vh - 210px);
	gap: 10px;
	flex-wrap: wrap;
	overflow:hidden;
	overflow-y:auto
}
.settings-box {
	max-height: 300px;
	width:400px;
	overflow:auto;
	background: #fff;
	border:5px solid #232323;
	border-radius:10px;
	overflow:auto;
}
.box-title {
	padding:5px;
	text-align:center;
	background: #232323;
	color:#fff;
}
.box-data {
	padding:10px;
}
</style>
<div class="settings-wrapper">
	<div class="settings-box" style="width:100%">
		<div class="box-title">WMS NAVIGATION BAR</div>
		<div class="box-data" id="mainnavbox"></div>
	</div>
	<div class="settings-box" style="width: 100%">
		<div class="box-title">BRANCH NAVIGATION BAR</div>
		<div class="box-data" id="navbox"></div>
	</div>
	<div class="settings-box">
		<div class="box-title">ORDERING LEAD TIME SETTINGS</div>
		<div class="box-data" id="lead_time_navbox"></div>
	</div>
	<div class="settings-box">
		<div class="box-title">NAVIGATION BARS</div>
		<div class="box-data" id="navbox"></div>
	</div>
	<div class="settings-box">
		<div class="box-title">NAVIGATION BAR</div>
		<div class="box-data" id="navbox"></div>
	</div>	
</div>
<script>
$(function()
{
	$.post("./Modules/Warehouse_Management/wms_settings/main_navigation.php", {  },
	function(data) {		
		$('#mainnavbox').html(data);
	});
	$.post("./Modules/Warehouse_Management/wms_settings/lead_time.php", {  },
	function(data) {		
		$('#lead_time_navbox').html(data);
	});

});	
</script>