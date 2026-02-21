<?php
include '../../../init.php';
require_once '../con_init.php';
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
if($keyword == '' || strlen($keyword) < 2)
{
	exit();
}
$keyword = mysqli_real_escape_string($conn, $keyword);
$query = "SELECT id, product_name FROM store_items WHERE product_name LIKE '%$keyword%' OR CAST(id AS CHAR) LIKE '%$keyword%' ORDER BY product_name ASC LIMIT 30";
$results = mysqli_query($conn, $query);

if($results && $results->num_rows > 0)
{
	while($row = mysqli_fetch_assoc($results))
	{
		$itemId = $row['id'];
		$itemName = $row['product_name'];
		echo "<div class=\"store-search-item\" onclick='pickStoreItem(".json_encode((string)$itemId).", ".json_encode($itemName).")'>[".$itemId."] ".htmlspecialchars($itemName, ENT_QUOTES)."</div>";
	}
}
else
{
	echo '<div class="store-search-item" style="color:#888;cursor:default">No items found</div>';
}
