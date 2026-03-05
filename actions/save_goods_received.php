<?php
include '../../../init.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$db->set_charset('utf8mb4');

if ($db->connect_errno) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$approver = $_SESSION['wms_appnameuser'] ?? '';
$branch   = 'WAREHOUSE';

if ($approver === '') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$po_id   = isset($input['po_id']) ? (int)$input['po_id'] : 0;
$items   = $input['items'] ?? null;
$remarks = trim($input['remarks'] ?? ''); // <-- single remark

if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

if ($po_id <= 0 || !is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'No items to receive']);
    exit;
}

if ($remarks === '') {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Remarks is required']);
    exit;
}

/* ===============================
   0. CHECK PO STATUS / CLOSED
================================ */
$stmt_po = $db->prepare("
    SELECT status, closed_po 
    FROM purchase_orders 
    WHERE id = ?
    FOR UPDATE
");

if (!$stmt_po) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare PO check']);
    exit;
}

$db->begin_transaction();

try {

$stmt_po->bind_param("i", $po_id);
$stmt_po->execute();
$po = $stmt_po->get_result()->fetch_assoc();

if (!$po) {
    throw new Exception('PO not found');
}

if ($po['closed_po'] == 1 || in_array($po['status'], ['RECEIVED','CANCELLED'])) {
    throw new Exception('PO already closed');
}

    /* ===============================
       1. INSERT RECEIPT HEADER (with remarks)
    ================================ */
    $stmt = $db->prepare("
        INSERT INTO purchase_receipts 
        (po_id, received_date, received_by, branch, status, remarks, created_at)
        VALUES (?, NOW(), ?, ?, 'confirmed', ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare receipt header insert');
    }

    $stmt->bind_param("isss", $po_id, $approver, $branch, $remarks);
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert receipt header');
    }

    $receipt_id = (int)$stmt->insert_id;
    if ($receipt_id <= 0) {
        throw new Exception('Invalid receipt id generated');
    }

    $receipt_no = 'GR-' . date('Ymd') . '-' . $receipt_id;
    $stmt_upd = $db->prepare("
        UPDATE purchase_receipts 
        SET receipt_no=? 
        WHERE id=?
    ");

    if (!$stmt_upd) {
        throw new Exception('Failed to prepare receipt number update');
    }

    $stmt_upd->bind_param("si", $receipt_no, $receipt_id);
    if (!$stmt_upd->execute()) {
        throw new Exception('Failed to update receipt number');
    }

    /* ===============================
       2. INSERT RECEIVED ITEMS (without remarks)
    ================================ */
    $stmt_item = $db->prepare("
        INSERT INTO purchase_receipt_items
        (receipt_id, po_item_id, received_qty, unit_price, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    if (!$stmt_item) {
        throw new Exception('Failed to prepare receipt item insert');
    }

    $inserted_count = 0;

    foreach ($items as $item) {
        if (!is_array($item) || !isset($item['po_item_id'], $item['received_qty'])) {
            throw new Exception('Invalid item payload');
        }

        $po_item_id   = (int)$item['po_item_id'];
        $received_qty = (float)$item['received_qty'];

        if ($po_item_id <= 0 || $received_qty <= 0) {
            continue;
        }

        $item_info_stmt = $db->prepare("
            SELECT qty, unit_price
            FROM purchase_order_items
            WHERE id = ? AND po_id = ?
            FOR UPDATE
        ");

        if (!$item_info_stmt) {
            throw new Exception('Failed to prepare PO item check');
        }

        $item_info_stmt->bind_param("ii", $po_item_id, $po_id);
        $item_info_stmt->execute();
        $item_info = $item_info_stmt->get_result()->fetch_assoc();

        if (!$item_info) {
            throw new Exception('PO item does not belong to this PO');
        }

        $received_sum_stmt = $db->prepare("
            SELECT IFNULL(SUM(received_qty), 0) AS received_qty
            FROM purchase_receipt_items
            WHERE po_item_id = ?
        ");

        if (!$received_sum_stmt) {
            throw new Exception('Failed to prepare received quantity check');
        }

        $received_sum_stmt->bind_param("i", $po_item_id);
        $received_sum_stmt->execute();
        $bal = $received_sum_stmt->get_result()->fetch_assoc();

        $ordered_qty = (float)$item_info['qty'];
        $already_received = (float)($bal['received_qty'] ?? 0);
        $remaining = $ordered_qty - $already_received;

        if ($received_qty > $remaining) {
            throw new Exception('Over-receiving detected.');
        }

        $unit_price = (float)$item_info['unit_price'];

        $stmt_item->bind_param(
            "iidd",
            $receipt_id,
            $po_item_id,
            $received_qty,
            $unit_price
        );

        if (!$stmt_item->execute()) {
            throw new Exception('Failed to insert receipt item');
        }

        $inserted_count++;
    }

    if ($inserted_count === 0) {
        throw new Exception('No valid items to receive');
    }

    /* ===============================
       3. COMPUTE PO STATUS
    ================================ */
    $chk_po = $db->prepare("
        SELECT 
            SUM(qty) AS total_ordered,
            (
                SELECT IFNULL(SUM(pri.received_qty),0)
                FROM purchase_receipt_items pri
                JOIN purchase_order_items poi2
                    ON poi2.id = pri.po_item_id
                WHERE poi2.po_id = ?
            ) AS total_received
        FROM purchase_order_items
        WHERE po_id = ?
    ");

    if (!$chk_po) {
        throw new Exception('Failed to prepare PO totals check');
    }

    $chk_po->bind_param("ii", $po_id, $po_id);
    $chk_po->execute();
    $res = $chk_po->get_result()->fetch_assoc();

    $total_ordered = (float)($res['total_ordered'] ?? 0);
    $total_received = (float)($res['total_received'] ?? 0);

    if ($total_ordered <= 0) {
        throw new Exception('PO has no items');
    }

    if ($total_received >= $total_ordered) {
        $new_status = 'RECEIVED';
        $closed_po  = 1;
        $closed_by = $approver;
        $closed_date = date('Y-m-d H:i:s');
    } else {
        $new_status = 'PARTIAL_RECEIVED';
        $closed_po  = 0;
        $closed_by = null;
        $closed_date = null;
    }

    /* ===============================
       4. UPDATE PURCHASE ORDER
    ================================ */
    $upd_po = $db->prepare("
        UPDATE purchase_orders
        SET 
            status=?,
            closed_po=?,
            closed_by=?,
            closed_date=?,
            updated_at=NOW(),
            updated_by=?
        WHERE id=?
    ");

    if (!$upd_po) {
        throw new Exception('Failed to prepare PO update');
    }

    $upd_po->bind_param(
        "sisssi",
        $new_status,
        $closed_po,
        $closed_by,
        $closed_date,
        $approver,
        $po_id
    );

    if (!$upd_po->execute()) {
        throw new Exception('Failed to update purchase order');
    }

    $db->commit();

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Goods received successfully',
        'receipt_no' => $receipt_no,
        'po_status'  => $new_status,
        'closed_po'  => $closed_po
    ]);

} catch (Throwable $e) {
    if ($db->errno || $db->in_transaction) {
        $db->rollback();
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
