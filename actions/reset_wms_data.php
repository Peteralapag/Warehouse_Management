<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

$mrs_no = "0001";
$dr_no = "000001";
$queryDataUpdate = "UPDATE wms_form_numbering SET mrs_number=?, dr_number=? WHERE id=1";
$stmt = $db->prepare($queryDataUpdate);
$stmt->bind_param("ss", $mrs_no, $dr_no);	   
if ($stmt->execute()) { } else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_inventory_records";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) { } else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_branch_order";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) { } else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_branch_order_remarks";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) {} else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_order_request";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) {} else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_inventory_stock";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) {} else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_receiving";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) {} else { echo $stmt->error; }
$queryDataDelete = "DELETE FROM wms_receiving_details";
$stmt = $db->prepare($queryDataDelete);
if ($stmt->execute()) {} else { echo $stmt->error; }
$stmt->close();
