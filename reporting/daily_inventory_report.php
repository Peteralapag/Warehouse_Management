<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$_SESSION['WMS_INVSUBMENU'] = $_POST['invpage'];
$_SESSION['WMS_ITEMCATEGORY'] = $_POST['category'];
?>
<style>
.tableFixHeadBody {margin-top:10px;background:#fff;border:1px solid red}
.tableFixHeadBody { overflow: auto; height: calc(100vh - 285px); width:100% }
.tableFixHeadBody thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHeadBody table  { border-collapse: collapse;}
.tableFixHeadBody th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="subnav-header">
	<select id="subinvstatus" style="width:180px" class="form-control form-control-sm" onchange="loadSubInvStats(this.value)">
		<?php echo $function->getWMSSubReport($tableName,$inv_submenu,$db); ?>
	</select>
</div>
<div class="tableFixHeadBody"></div>
