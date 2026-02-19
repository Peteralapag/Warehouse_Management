<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
?>
<style>
.search-wrapper {
	min-height:30px;
	max-height:200px;
	width:100%;
	overflow:auto;
}
.search-wrapper ul {
	list-style-type: none;
	width:100%;
	padding: 0;
	margin:0;
}
.search-wrapper li {
	padding: 5px 5px 5px 10px;
	width:100%;
	font-size:14px;
	border-bottom:1px solid #aeaeae;
	cursor: pointer;
}
.search-wrapper li:last-child {
	border: 0;
}
.search-wrapper li:hover {
	background:#d1d1d1;
}
</style>
<div style="width:280px;margin-bottom:20px">
	<input id="branchsearch" type="text" class="form-control w-100" placeholder="Search Branch" autocomplete="sarado">
	<div class="search-wrapper">
		<ul id="branchdata"></ul>
	</div>
</div>
<div id="widres"></div>
<script>
function setBranch(branch)
{
	$('#branch').val(branch);
	$('#formmodal').hide();
	var mode = 'setreportbranch';
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, branch: branch },
	function(data) {		
	//	$('#widres').html(data);	
	});
}
$(function()
{
	loadReportBranch();
	$('#branchsearch').keyup(function()
	{
		var search = $('#branchsearch').val();
		$.post("./Modules/Warehouse_Management/wms_report/inventory_branch_search.php", { search: search },
		function(data) {		
			$('#branchdata').html(data);	
		});
	});
});
function loadReportBranch()
{
	var search = $('#branchsearch').val();
	$.post("./Modules/Warehouse_Management/wms_report/inventory_branch_search.php", { search: search },
	function(data) {		
		$('#branchdata').html(data);	
	});
}
</script>