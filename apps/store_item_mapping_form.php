<?php
include '../../../init.php';
require_once '../con_init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

$item_code = isset($_POST['item_code']) ? $_POST['item_code'] : '';
$current_store_item_id = isset($_POST['store_item_id']) ? $_POST['store_item_id'] : '';
$current_store_item_name = '';
if($current_store_item_id != '')
{
	$current_store_item_id = intval($current_store_item_id);
	$qCurrent = "SELECT product_name FROM store_items WHERE id='$current_store_item_id' LIMIT 1";
	$currentRes = mysqli_query($conn, $qCurrent);
	if($currentRes && $currentRes->num_rows > 0)
	{
		$currentRow = mysqli_fetch_assoc($currentRes);
		$current_store_item_name = $currentRow['product_name'];
	}
}
?>
<style>
.form-wrapper {width:600px;max-height:500px;overflow-y:auto;}
.store-select {width:100%;}
.store-search-results {
	position: relative;
	z-index: 99;
	max-height: 220px;
	overflow-y: auto;
	border: 1px solid #dcdcdc;
	border-top: none;
	background: #fff;
	display: none;
}
.store-search-item {
	padding: 6px 10px;
	cursor: pointer;
	font-size: 13px;
}
.store-search-item:hover {
	background: #f1f5f9;
}
</style>
<div class="form-wrapper">
	<table style="width:100%" class="table">
		<tr>
			<th style="width:170px">WMS ITEMCODE</th>
			<td>
				<input id="map_item_code" type="text" class="form-control" value="<?php echo $item_code; ?>" readonly>
			</td>
		</tr>
		<tr>
			<th>STORE ITEMS</th>
			<td>
				<input id="map_store_item_id" type="hidden" value="<?php echo $current_store_item_id; ?>">
				<input id="map_store_item_name" type="text" class="form-control" value="<?php echo htmlspecialchars($current_store_item_name, ENT_QUOTES); ?>" placeholder="Type product name or IDCODE..." autocomplete="off" onkeyup="searchStoreItems(this.value)">
				<div id="store_items_result" class="store-search-results"></div>
			</td>
		</tr>
	</table>
</div>
<div class="map-results" style="font-size:12px;"></div>
<div style="margin-top:10px;text-align:right">
	<button class="btn btn-primary btn-sm" onclick="saveStoreItemMapping()">Save Mapping</button>
	<button class="btn btn-danger btn-sm" onclick="closeModal('formmodal')">Close</button>
</div>

<script>
let storeSearchTimer = null;
function searchStoreItems(keyword)
{
	clearTimeout(storeSearchTimer);
	if(keyword.length < 2)
	{
		$('#store_items_result').hide().html('');
		return;
	}
	storeSearchTimer = setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/apps/store_item_search.php", { keyword: keyword },
		function(data) {
			$('#store_items_result').html(data).show();
		});
	}, 250);
}
function pickStoreItem(itemId, itemName)
{
	$('#map_store_item_id').val(itemId);
	$('#map_store_item_name').val(itemName);
	$('#store_items_result').hide().html('');
}
$(document).on('click', function(e)
{
	if(!$(e.target).closest('#map_store_item_name, #store_items_result').length)
	{
		$('#store_items_result').hide();
	}
});
function saveStoreItemMapping()
{
	var itemCode = $('#map_item_code').val();
	var storeItemId = $('#map_store_item_id').val();
	var storeItemName = $.trim($('#map_store_item_name').val());
	if(itemCode === '')
	{
		app_alert("Item Code","Invalid Item Code","warning","Ok","","no");
		return false;
	}
	if(storeItemName === '')
	{
		storeItemId = '';
		$('#map_store_item_id').val('');
	}
	rms_reloaderOn("Saving mapping...");
	$.post("./Modules/Warehouse_Management/actions/store_item_mapping_process.php", { item_code: itemCode, store_item_id: storeItemId },
	function(data) {
		$('.map-results').html(data);
		rms_reloaderOff();
	});
}
</script>
