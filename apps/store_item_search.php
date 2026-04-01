<?php
include '../../../init.php';
require_once '../con_init.php';
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
if($keyword == '')
{
	exit();
}
$keyword = mysqli_real_escape_string($conn, $keyword);
$query = "SELECT id, product_name, itemcode, sku FROM store_items WHERE (";
$query .= "product_name LIKE '%$keyword%' OR CAST(id AS CHAR) LIKE '%$keyword%' OR itemcode LIKE '%$keyword%' OR sku LIKE '%$keyword%') ";
$query .= "ORDER BY (itemcode='$keyword' OR sku='$keyword' OR CAST(id AS CHAR)='$keyword') DESC, product_name ASC LIMIT 30";
$results = mysqli_query($conn, $query);

if($results && $results->num_rows > 0)
{
	while($row = mysqli_fetch_assoc($results))
	{
		$itemId = $row['id'];
		$itemName = $row['product_name'];
		$itemCode = trim((string)($row['itemcode'] ?? ''));
		$sku = trim((string)($row['sku'] ?? ''));
		$meta = array();
		if($itemCode !== '') { $meta[] = 'Code: '.$itemCode; }
		if($sku !== '') { $meta[] = 'SKU: '.$sku; }
		$metaText = count($meta) > 0 ? ' <span style="color:#64748b;font-size:11px;">('.htmlspecialchars(implode(' | ', $meta), ENT_QUOTES).')</span>' : '';
		echo "<div class=\"store-search-item\" onclick='pickStoreItem(".json_encode((string)$itemId).", ".json_encode($itemName).")'>[".$itemId."] ".htmlspecialchars($itemName, ENT_QUOTES).$metaText."</div>";
	}
}
else
{
	echo '<div class="store-search-item" style="color:#888;cursor:default">No items found</div>';
}
