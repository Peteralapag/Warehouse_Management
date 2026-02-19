<div class="smnav-header">
	WMS SETTINGS
</div>
<div id="smnavdata" class="settings"><i class="fa fa-spinner fa-spin"></i></div>
<script>
$(function()
{
	$.post("./Modules/Warehouse_Management/wms_settings/wms_settings.php", {  },
	function(data) {		
		$('#smnavdata').html(data);
	});

});
</script>