<?php
session_start();
define("MODULE_NAME", "Warehouse Management");
if(!isset($_SESSION['wms_username'])) { ?>
<script>
$(function()
{
	$('#modaltitle').html("Warehouse Management Login");
	$('#modalicon').html('<i class="fa-solid fa-user color-dodger"></i>');	
	$.post("../Modules/Warehouse_Management/apps/login.php", { },
	function(data) {
		$('#formmodal_page').html(data);		
		$('#formmodal').show();		
	});
});
</script>	
<?php exit(); } ?>
<link rel="stylesheet" href="../Modules/Warehouse_Management/styles/styles.css">
<script src="../Modules/Warehouse_Management/scripts/script.js"></script>
<!-- @@@@@@@@@@ ################### @@@@@@@@@@@ -->
<div class="sidebar">
	<div class="logo-title" id="logotitle"></div>
	<div class="navigation" id="navigation"></div>
</div>
<div class="content-wrapper">
	<div class="contents" id="contents"></div>
</div>
<script>
$(function()
{
	var module = '<?php echo MODULE_NAME; ?>';
	$('#logotitle').load('../Modules/Warehouse_Management/apps/logo_title.php');
	$('#navigation').load("../Modules/Warehouse_Management/pages/sidebar_navigation.php");
});
</script>