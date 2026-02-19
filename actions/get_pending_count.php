<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

$status = $_POST['rtvstatus'];

$sqlQuery = "SELECT COUNT(id) as count FROM wms_return_to_vendor WHERE status='$status'";
$results = mysqli_query($db, $sqlQuery);    

if ($results) {
	 $row = mysqli_fetch_assoc($results);
    $count = $row['count'];
    echo $count;
} else {
    echo "Error: " . mysqli_error($db);
}