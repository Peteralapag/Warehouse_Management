<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);

$defaultStatuses = ['approved','partial_received'];

// SQL with pre-aggregated received qty
$sql = "
SELECT 
    po.id,
    po.po_number,
    po.pr_number,
    po.supplier_id,
    po.source,
    s.name AS supplier_name,
    po.order_date,
    po.expected_delivery,
    SUM(poi.qty) AS total_ordered,
    IFNULL(SUM(pri.received_sum),0) AS total_received,
    (SUM(poi.qty) - IFNULL(SUM(pri.received_sum),0)) AS balance,
    po.status
FROM purchase_orders po
JOIN purchase_order_items poi ON po.id = poi.po_id
JOIN suppliers s ON po.supplier_id = s.id
LEFT JOIN (
    SELECT 
        po_item_id,
        SUM(
            CASE
                WHEN accepted_qty IS NULL AND rejected_qty IS NULL THEN IFNULL(received_qty, 0)
                ELSE IFNULL(accepted_qty, 0) + IFNULL(rejected_qty, 0)
            END
        ) AS received_sum
    FROM purchase_receipt_items
    GROUP BY po_item_id
) pri ON pri.po_item_id = poi.id
WHERE po.source = 'WAREHOUSE'
";

// Status filter
if($status !== ''){
    $sql .= " AND po.status = ?";
    $params = [$status];
    $types = "s";
}else{
    $sql .= " AND po.status IN ('approved','partial_received')";
    $params = [];
    $types = "";
}

$sql .= " GROUP BY po.id ORDER BY po.order_date DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $db->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<style>
.gr-table {
    margin-bottom: 0;
    font-size: 13px;
    min-width: 1120px;
}
.gr-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8fafc;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: .02em;
    font-size: 11px;
    font-weight: 700;
    padding: 10px 8px !important;
    border-bottom: 1px solid #e2e8f0 !important;
    white-space: nowrap;
}
.gr-table tbody td {
    padding: 8px 8px !important;
    vertical-align: middle;
    color: #0f172a;
    border-color: #eef2f6;
    background: #fff;
}
.gr-table tbody tr:nth-child(even) td {
    background: #fcfdff;
}
.gr-table tbody tr:hover td {
    background: #f4f8ff;
}
.gr-po-number {
    font-weight: 700;
    color: #0f172a;
}
.gr-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.gr-status-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.4;
    text-transform: capitalize;
    white-space: nowrap;
}
.gr-status-approved {background:#e9f4ff;color:#0f4d85;border:1px solid #cbe4ff;}
.gr-status-partial_received {background:#fff7e6;color:#8a5a00;border:1px solid #ffe1a8;}
.gr-status-received {background:#e8f8ef;color:#107a3f;border:1px solid #c4ebd5;}
.gr-status-cancelled {background:#fff0f0;color:#a11b1b;border:1px solid #ffd4d4;}
.btn-view-gr {
    padding: 3px 9px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid #bfd7ff;
    background: #edf4ff;
    color: #0f4d85;
}
.btn-view-gr:hover { background: #dceaff; }
.gr-empty {
    text-align: center;
    color: #64748b;
    font-size: 13px;
    padding: 22px 8px !important;
}
</style>

<table class="table table-bordered gr-table" id="potable">
<thead>
<tr>
    <th>#</th>
    <th>PR Number</th>
    <th>PO Number</th>
    <th>Supplier</th>
    <th>Order Date</th>
    <th>Expected Delivery</th>
    <th>Total Qty</th>
    <th>Received Qty</th>
    <th>Balance</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php 
$i = 1;
while($row = $result->fetch_assoc()): 

    // Update status permanently if balance = 0
    if(floatval($row['balance']) <= 0 && strtolower((string)$row['status']) !== 'received'){
        $stmt_update = $db->prepare("UPDATE purchase_orders SET status='received', updated_at=NOW() WHERE id=?");
        $stmt_update->bind_param("i", $row['id']);
        $stmt_update->execute();
        $stmt_update->close();
        $row['status'] = 'received';
    }

?>
<tr>
    <td><?= $i ?></td>
    <td><?= htmlspecialchars($row['pr_number']) ?></td>
    <td class="gr-po-number"><?= htmlspecialchars($row['po_number']) ?></td>
    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
    <td><?= htmlspecialchars($row['order_date']) ?></td>
    <td><?= htmlspecialchars($row['expected_delivery']) ?></td>
    <td class="gr-num"><?= number_format((float)$row['total_ordered'], 2) ?></td>
    <td class="gr-num"><?= number_format((float)$row['total_received'], 2) ?></td>
    <td class="gr-num"><strong><?= number_format((float)$row['balance'], 2) ?></strong></td>
    <td>
        <?php $statusKey = strtolower((string)$row['status']); ?>
        <span class="gr-status-pill gr-status-<?= htmlspecialchars($statusKey) ?>"><?= ucwords(str_replace('_',' ',$statusKey)) ?></span>
    </td>
    <td>
        <button type="button" class="btn-view-gr w-100"
            onclick='viewgr(<?= (int)$row['id'] ?>, <?= json_encode($row['pr_number']) ?>, <?= json_encode($row['po_number']) ?>, <?= json_encode($row['supplier_name']) ?>, <?= json_encode($row['status']) ?>)'>
            <i class="fa-solid fa-eye"></i> View
        </button>
    </td>
</tr>
<?php $i++; endwhile; ?>

<?php if($result->num_rows === 0): ?>
<tr>
    <td colspan="11" class="gr-empty">
        <i class="fa fa-info-circle"></i> No goods received found.
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>



<script>

function viewgr(rowid, prnumber, ponumber, suppliername, status){
		
	$.post("./Modules/Warehouse_Management/includes/goods_received_view.php", { rowid: rowid, prnumber: prnumber, ponumber: ponumber, suppliername: suppliername, status: status },
	function(data) {
		$('#contents').html(data);
	});
}

</script>

