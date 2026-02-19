<style>
.searchbar-search td {
	padding:0 !important;
}
.searchbar-search input {
	border: 0 !important;
}
.search-header th {
	background: #0cccae;
	color:#fff;
	font-size:14px;
	padding:3px;
}
.search-data td {
	padding:3px;
	font-size:13px;
	background:#f1f1f1;
}
.search-data:hover {
	background:#cecece;
	cursor: pointer;
}
.transferdatasearch {
	max-height:400px;
	overflow:auto;
	margin-bottom:10px;
}
</style>

<table style="width: 600px" class="table table-bordered">
	<tr class="searchbar-search">
		<td colspan="2" style="vertical-align:middle;text-align:center">Search</td>
		<td colspan="2"><input id="searchitem" type="text" class="form-control form-control-sm" placeholder="Search Item"></td>
	</tr>
</table>
<div id="transferdatasearch" class="transferdatasearch"></div>
<script>
$(document).ready(function()
{
	$('#searchitem').keyup(function()
	{
		var search = $('#searchitem').val();
		$.post("./Modules/Warehouse_Management/Includes/wh_transfer_data.php", { search: search  },
		function(data) {		
			$('#transferdatasearch').html(data);
		});
	});
	$.post("./Modules/Warehouse_Management/Includes/wh_transfer_data.php", {  },
	function(data) {		
		$('#transferdatasearch').html(data);
	});
});
</script>

