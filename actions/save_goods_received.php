<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$approver = $_SESSION['wms_appnameuser'] ?? '';
$branch   = 'WAREHOUSE';

$input = json_decode(file_get_contents('php://input'), true);
$po_id   = $input['po_id'] ?? '';
$items   = $input['items'] ?? [];
$remarks = trim($input['remarks'] ?? ''); // <-- single remark

if (empty($po_id) || empty($items)) {
    echo json_encode(['status'=>'error','message'=>'No items to receive']);
    exit;
}

// âœ… Ensure remarks is provided
if ($remarks === '') {
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
");
$stmt_po->bind_param("i", $po_id);
$stmt_po->execute();
$po = $stmt_po->get_result()->fetch_assoc();

if (!$po) {
    echo json_encode(['status'=>'error','message'=>'PO not found']);
    exit;
}

if ($po['closed_po'] == 1 || in_array($po['status'], ['RECEIVED','CANCELLED'])) {
    echo json_encode(['status'=>'error','message'=>'PO already closed']);
    exit;
}

$db->begin_transaction();

try {

    /* ===============================
       1. INSERT RECEIPT HEADER (with remarks)
    ================================ */
    $stmt = $db->prepare("
        INSERT INTO purchase_receipts 
        (po_id, received_date, received_by, branch, status, remarks, created_at)
        VALUES (?, NOW(), ?, ?, 'confirmed', ?, NOW())
    ");
    $stmt->bind_param("isss", $po_id, $approver, $branch, $remarks);
    $stmt->execute();
    $receipt_id = $stmt->insert_id;

    $receipt_no = 'GR-' . date('Ymd') . '-' . $receipt_id;
    $stmt_upd = $db->prepare("
        UPDATE purchase_receipts 
        SET receipt_no=? 
        WHERE id=?
    ");
    $stmt_upd->bind_param("si", $receipt_no, $receipt_id);
    $stmt_upd->execute();

    /* ===============================
       2. INSERT RECEIVED ITEMS (without remarks)
    ================================ */
    $stmt_item = $db->prepare("
        INSERT INTO purchase_receipt_items
        (receipt_id, po_item_id, received_qty, unit_price, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    foreach ($items as $item) {

        $po_item_id  = (int)$item['po_item_id'];
        $received_qty = (float)$item['received_qty'];

        if ($received_qty <= 0) continue;

        // get ordered + already received
        $chk = $db->prepare("
            SELECT 
                poi.qty AS ordered_qty,
                IFNULL(SUM(pri.received_qty),0) AS received_qty
            FROM purchase_order_items poi
            LEFT JOIN purchase_receipt_items pri 
                ON pri.po_item_id = poi.id
            WHERE poi.id = ?
            GROUP BY poi.id
        ");
        $chk->bind_param("i", $po_item_id);
        $chk->execute();
        $bal = $chk->get_result()->fetch_assoc();

        $remaining = $bal['ordered_qty'] - $bal['received_qty'];

        if ($received_qty > $remaining) {
            throw new Exception("Over-receiving detected.");
        }

        // fetch price
        $p = $db->prepare("
            SELECT unit_price 
            FROM purchase_order_items 
            WHERE id=?
        ");
        $p->bind_param("i", $po_item_id);
        $p->execute();
        $po_data = $p->get_result()->fetch_assoc();

        $stmt_item->bind_param(
            "iidd",
            $receipt_id,
            $po_item_id,
            $received_qty,
            $po_data['unit_price']
        );
        $stmt_item->execute();
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
    $chk_po->bind_param("ii", $po_id, $po_id);
    $chk_po->execute();
    $res = $chk_po->get_result()->fetch_assoc();

    if ($res['total_received'] >= $res['total_ordered']) {
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
    $upd_po->bind_param(
        "sisssi",
        $new_status,
        $closed_po,
        $closed_by,
        $closed_date,
        $approver,
        $po_id
    );
    $upd_po->execute();

    $db->commit();

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Goods received successfully',
        'receipt_no' => $receipt_no,
        'po_status'  => $new_status,
        'closed_po'  => $closed_po
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}
