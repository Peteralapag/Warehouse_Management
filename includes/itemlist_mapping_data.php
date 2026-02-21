<?php
include '../../../init.php';
require_once '../con_init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

$mapping_table = 'wms_item_mapping';
$checkMapTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_item_mapping'");
if(!$checkMapTable || $checkMapTable->num_rows == 0)
{
	$checkMapTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_item_maaping'");
	if($checkMapTable2 && $checkMapTable2->num_rows > 0)
	{
		$mapping_table = 'wms_item_maaping';
	}
}

$store_desc_col = 'product_name';

if(isset($_POST['search']) && $_POST['search'] != '')
{
	$search = $_POST['search'];
	if(isset($_POST['category']) && $_POST['category'] != '')
	{
		$category = $_POST['category'];
		$q = "WHERE (il.item_description LIKE '%$search%' OR il.item_code LIKE '%$search%' OR il.qr_code LIKE '%$search%' OR mp.store_item_id LIKE '%$search%') AND il.category='$category'";
	} else {
		$q = "WHERE il.item_description LIKE '%$search%' OR il.item_code LIKE '%$search%' OR il.qr_code LIKE '%$search%' OR mp.store_item_id LIKE '%$search%'";
	}
} else {
	if(isset($_POST['category']) && $_POST['category'] != '')
	{
		$category = $_POST['category'];
		$q = "WHERE il.category='$category'";
	} else {
		$q = "WHERE il.active=1";
	}
}
?>

<style>
.table td {
	padding:2px 5px 2px 5px !important;
}
.store-desc-link {
	color: #0d6efd;
	cursor: pointer;
	text-decoration: underline;
}
</style>

<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th>ITEM CODE</th>
			<th>CATEGORY</th>
			<th>ITEM DESCRIPTION</th>
			<th>UOM</th>
			<th>RECIPIENT</th>
			<th>STORE DESCRIPTION</th>
			<th>STOREAPP ITEM CODE</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$sqlQuery = "SELECT il.*, mp.store_item_id
				FROM wms_itemlist il
				LEFT JOIN (
					SELECT m1.wms_item_code, m1.store_item_id
					FROM $mapping_table m1
					INNER JOIN (
						SELECT wms_item_code, MAX(id) AS max_id
						FROM $mapping_table
						WHERE status=1
						GROUP BY wms_item_code
					) m2 ON m1.id = m2.max_id
				) mp ON mp.wms_item_code = il.item_code
				$q
				ORDER BY il.item_description ASC";
	$results = mysqli_query($db, $sqlQuery);
	$store_map = array();
	if($results && $results->num_rows > 0 && $store_desc_col != '')
	{
		$store_ids = array();
		while($tmp = mysqli_fetch_assoc($results))
		{
			if(isset($tmp['store_item_id']) && $tmp['store_item_id'] != '')
			{
				$store_ids[] = intval($tmp['store_item_id']);
			}
		}
		mysqli_data_seek($results, 0);
		$store_ids = array_unique($store_ids);
		if(count($store_ids) > 0)
		{
			$id_list = implode(',', $store_ids);
			$qStore = "SELECT id, $store_desc_col AS store_description FROM store_items WHERE id IN ($id_list)";
			$store_res = mysqli_query($conn, $qStore);
			if($store_res)
			{
				while($srow = mysqli_fetch_assoc($store_res))
				{
					$store_map[$srow['id']] = $srow['store_description'];
				}
			}
		}
	}
	if ($results && $results->num_rows > 0)
	{
		$sp=0;
		while($row = mysqli_fetch_array($results))
		{
			$sp++;
			$store_desc = '--';
			if($row['store_item_id'] != '' && isset($store_map[$row['store_item_id']]))
			{
				$store_desc = $store_map[$row['store_item_id']];
			}
			$store_desc_click = ($store_desc != '' && $store_desc != '--') ? $store_desc : 'Click Here';
	?>
		<tr>
			<td style="text-align:center"><?php echo $sp; ?></td>
			<td style="text-align:center"><?php echo $row['item_code']; ?></td>
			<td><?php echo $row['category']; ?></td>
			<td><?php echo $row['item_description']; ?></td>
			<td><?php echo $row['uom']; ?></td>
			<td><?php echo $row['recipient']; ?></td>
			<td style="text-align:center"><span class="store-desc-link" onclick="clickStoreDescription('<?php echo $row['item_code']; ?>','<?php echo $row['store_item_id']; ?>')"><?php echo $store_desc_click; ?></span></td>
			<td style="text-align:center"><?php echo ($row['store_item_id'] != '') ? $row['store_item_id'] : '--'; ?></td>
		</tr>
	<?php
		}
	} else {
	?>
		<tr>
			<td colspan="8" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
	<?php } ?>
	</tbody>
</table>

<script>
function clickStoreDescription(itemCode,storeItemId)
{
	$('#modaltitle').html("Store Item Mapping");
	$.post("./Modules/Warehouse_Management/apps/store_item_mapping_form.php", { item_code: itemCode, store_item_id: storeItemId },
	function(data) {
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
