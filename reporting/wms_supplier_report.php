<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$table = $_SESSION['WMS_TABLE'];
$check = $inventory->getColumns($table,$db);
if($check == "")
{
	$string =  "*";
	print_r('
		<script>
			sessionStorage.removeItem("stringKo");
		</script>		
	');
	exit();
} 
else 
{
	$string = $mabaitakoString = implode(',', $check);
	$columns = $inventory->removeStringFromArray($check, 'id');
}
?>
<style>
.table-columns th {padding: 5px;font-size:11px !important;text-align:center;border:1px solid #fff;white-space:nowrap;background:#aeaeae;color:#fff;width:80px;white-space:nowrap}
.table-columns td {text-align:center;padding:0px !important;border:1px solid #aeaeae;background:#fff}
.table-columns input[type=checkbox]{cursor:pointer;width:15px;height:15px;}
.column-manager {display: flex; width: calc(100vw - 365px);overflow:hidden}
.tableFixHead {margin-top:10px;background:#fff;}
.tableFixHead  { overflow: auto; height: calc(100vh - 275px); width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap } 
</style>
<div class="column-manager">
	<table style="width: 100%" class="table-columns">
		<tr>
			<?php
	            foreach ($columns as $column) {
	            	$col = str_replace("_"," ", $column);
	            	$col = strtoupper($col);
                	echo "<th style='width:100px'>$col</th>";
	            }
	        ?>
		</tr>
		<tr>
			<?php
				$chk=0;
	            foreach ($columns as $column)
	            {
	            	$chk++;
                	echo '<td><input id="'.$column.'" checked="checked" type="checkbox" onchange="removedColumn()"></td>';
	            }
	        ?>
        </tr>
	</table>
</div>
<div class="tableFixHead" id="tableData">&nbsp;&nbsp;Loading... <i class="fa fa-spinner fa-spin"></i></div>
<script>
function loadDatas()
{
	loadTableData(sessionStorage.stringKo);
}
function removedColumn()
{
	var limit = $('#limit').val();
	let uncheckedColumns = [];	
	$("input[type=checkbox]:checked").each(function()
	{
		uncheckedColumns.push($(this).attr("id"));
	});
	const chkcolumns = uncheckedColumns.toString();
	$.post("./Modules/Warehouse_Management/reporting/wms_report_data.php", { limit: limit, columns: chkcolumns },
	function(data) {		
		$('#tableData').html(data);
	});  
}
function loadTableData(columns)
{
	var limit = $('#limit').val();
	$.post("./Modules/Warehouse_Management/reporting/wms_report_data.php", { limit: limit, columns: columns },
	function(data) {
		$('#tableData').html(data);
	});
}
$(function()
{
	var stringko = removeIdFromString('<?php echo $string; ?>');
	loadTableData(stringko);
});
function removeIdFromString(str) {
	return str.replace(/\bid\b,?/, '');
}
</script>