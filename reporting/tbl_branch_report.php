<?php
session_start();
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_TABLE'] = $_POST['table'];
echo "asaaaa ". $_POST['table'];
?>
