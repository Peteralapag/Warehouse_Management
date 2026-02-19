<?php
include $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/con_init.php";
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);

require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.store_functions.php";
$storeFunction = new WMSStoreFunctions;

echo $storeFunction->GetRawmatsSumData('actual_count',$year,$month,$branch,$conn);