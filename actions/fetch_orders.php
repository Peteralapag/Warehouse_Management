<?php
include_once '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

// Get parameters from the AJAX request
$show_limit = $_POST['limit'] ?? '25';
$last_seen_trans_date = $_POST['last_seen_trans_date'] ?? null;
$ord = $_POST['ord'] ?? '';
$recipient = $_POST['recipient'] ?? '';
$search = $_POST['search'] ?? '';

// Build the query
$qr = "";
$params = [];
if ($recipient) {
    if ($ord == 'Process Order') {
        $qr = "AND recipient=? AND (status='Submitted' OR status='In-Transit')";
        $params[] = $recipient;
    } elseif ($ord == 'Closed Order') {
        $qr = "AND recipient=? AND status='Closed'";
        $params[] = $recipient;
    }
}

if (!empty($search)) {
    $qr .= " AND (control_no LIKE ? OR branch LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Cursor-based pagination
if ($last_seen_trans_date) {
    $qr .= " AND trans_date < ?";
    $params[] = $last_seen_trans_date;
}

// Fetch data
$sqlQuery = "SELECT control_no, branch, order_type, recipient, trans_date, delivery_date, status 
             FROM wms_order_request 
             WHERE checked='Approved' AND approved='Approved' $qr 
             ORDER BY trans_date DESC 
             LIMIT ?";
$params[] = $show_limit;

$stmt = $db->prepare($sqlQuery);
$stmt->bind_param(str_repeat('s', count($params) - 1) . 'i', ...$params);
$stmt->execute();
$results = $stmt->get_result();

$data = [];
while ($row = $results->fetch_assoc()) {
    $data[] = $row;
}

// Get the last seen trans_date for the next page
$last_seen_trans_date = !empty($data) ? end($data)['trans_date'] : null;

// Return JSON response
echo json_encode([
    'data' => $data,
    'last_seen_trans_date' => $last_seen_trans_date
]);