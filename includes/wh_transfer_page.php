<style>
.wh-trasfer td{
	white-space:nowrap;
	vertical-align:top;
}
.wh-trasfer th {
	text-align:center;
	padding:5px;
	border:1px solid #aeaeae;
	background:#cecece;
}
.center-btn {
	position: absolute;
	top: 52%;
	left: 50%;
	-webkit-transform: translate(-50%, -50%);
	transform: translate(-50%, -50%);
}
</style>
<table style="width: 100%;border-collapse:collapse" cellpadding="0" cellspacing="0" class="wh-trasfer">
	<tr>
		<th style="width:550px;">ITEM LIST</th>
		<th>TRANSFER</th>
	</tr>
	<tr>
		<td id="stocklist">Loading... <i class="fa fa-spinner fa-spin"></i></td>
		<td id="conversionform" style="position:relative">
			<div class="center-btn">
				<button class="btn btn-warning btn-sm color-white" onclick="openFile()"><i class="fa-regular fa-folder-open"></i>&nbsp;&nbsp;Open File</button>
			</div>
		</td>
	</tr>
</table>
<script>
function getItemToFormID(mode,rowid)
{
	$.post("./Modules/Warehouse_Management/includes/wh_transfer_form.php", { mode: mode, rowid: rowid },
	function(data) {
		$('#conversionform').html(data);
		$('#formmodal').hide();
	});
}
$(function()
{
	$.post("./Modules/Warehouse_Management/includes/wh_stock_list.php", {  },
	function(data) {
		$('#stocklist').html(data);
	});
});	
function stockList()
{
	$.post("./Modules/Warehouse_Management/includes/wh_stock_list.php", {  },
	function(data) {
		$('#stocklist').html(data);
	});
}
function openFile()
{
	$('#modaltitle').html("File");
	$('#modalicon').html('<i class="fa-solid fa-folder color-yellow"></i>');	
	$.post("./Modules/Warehouse_Management/Includes/wh_transfer_search.php", {  },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>