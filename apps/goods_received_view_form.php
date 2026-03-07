<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$approver = $_SESSION['wms_appnameuser'] ?? '';
$rowid = $_POST['rowid'] ?? '';
$prnumber = $_POST['prnumber'] ?? '';
$ponumber = $_POST['ponumber'] ?? '';
$suppliername = $_POST['suppliername'] ?? '';
$status = $_POST['status'] ?? '';
$statusLower = strtolower((string)$status);


if (empty($ponumber)) {
    echo '<script>swal("Error","PO Number not provided","error");</script>';
    exit;
}

// Fetch PO items with previously received qty
$sql = "
	SELECT 
	    poi.id AS po_item_id,
	    i.item_description AS item_name,
	    poi.qty AS ordered_qty,
	    IFNULL(SUM(pri.received_qty), 0) AS received_qty
	FROM purchase_order_items poi
	JOIN wms_itemlist i 
	    ON i.item_code = poi.item_code
	LEFT JOIN purchase_receipts pr 
	    ON pr.po_id = poi.po_id
	LEFT JOIN purchase_receipt_items pri 
	    ON pri.receipt_id = pr.id 
	    AND pri.po_item_id = poi.id
	WHERE poi.po_id = ?
	GROUP BY poi.id, i.item_description, poi.qty
	";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $rowid);
$stmt->execute();
$result = $stmt->get_result();

$poItems = [];
$hasReceivableBalance = false;
while ($itemRow = $result->fetch_assoc()) {
    $itemRow['ordered_qty'] = (float)($itemRow['ordered_qty'] ?? 0);
    $itemRow['received_qty'] = (float)($itemRow['received_qty'] ?? 0);
    $itemBalance = $itemRow['ordered_qty'] - $itemRow['received_qty'];
    if ($itemBalance > 0) {
        $hasReceivableBalance = true;
    }
    $poItems[] = $itemRow;
}


// Fetch full history of received items
$sql_history = "
	SELECT 
	    pr.receipt_no,
	    pr.received_date,
	    pr.received_by,
	    i.item_description,
	    pri.received_qty,
	    pri.unit_price,
	    pri.amount,
	    pri.po_item_id
	FROM purchase_receipts pr
	JOIN purchase_receipt_items pri 
	    ON pri.receipt_id = pr.id
	JOIN purchase_order_items poi 
	    ON poi.id = pri.po_item_id
	JOIN wms_itemlist i 
	    ON i.item_code = poi.item_code
	WHERE pr.po_id = ?
	ORDER BY pr.received_date ASC
	";

$stmt_hist = $db->prepare($sql_history);
$stmt_hist->bind_param("i", $rowid);
$stmt_hist->execute();
$result_hist = $stmt_hist->get_result();

// Group history per PO item
$history = [];
while($hist = $result_hist->fetch_assoc()){
    $history[$hist['po_item_id']][] = $hist;
}


?>

<style>
.gr-view-form {
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(15, 23, 42, 0.04);
}
.gr-title {
    margin:0 0 10px 0;
    padding:10px 12px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:8px;
    font-size:14px;
    color:#334155;
}
.gr-title strong {color:#0f172a;}
.gr-table {
    margin-bottom:0;
    font-size:13px;
    min-width:980px;
}
.gr-table thead th {
    position:sticky;
    top:0;
    z-index:2;
    background:#f8fafc;
    color:#334155;
    text-transform:uppercase;
    letter-spacing:.02em;
    font-size:11px;
    font-weight:700;
    padding:10px 8px !important;
    border-bottom:1px solid #e2e8f0 !important;
    white-space:nowrap;
}
.gr-table tbody td {
    padding:8px 8px !important;
    vertical-align:middle;
    color:#0f172a;
    background:#fff;
    border-color:#eef2f6;
}
.gr-table tbody tr:nth-child(even) td {background:#fcfdff;}
.gr-table tbody tr:hover td {background:#f4f8ff;}
.num-right {text-align:right;font-variant-numeric:tabular-nums;}
.balance-badge {
    display:inline-block;
    min-width:62px;
    text-align:center;
    padding:2px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    background:#fff7e6;
    color:#8a5a00;
    border:1px solid #ffe1a8;
}
.remarks-area {
    resize:vertical;
    min-height:54px;
}
.gr-action-bar {
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:8px;
    padding:10px 12px;
    border-top:1px solid #e5e7eb;
    background:#fbfcfe;
}
.btn-save-gr {
    padding:6px 12px;
    font-size:12px;
    font-weight:600;
    border-radius:6px;
}
.gr-history .accordion-button {
    font-size:13px;
    font-weight:600;
}
.gr-history table th {
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.02em;
}
</style>

<div class="gr-title"><i class="fa-solid fa-building text-primary"></i> Supplier: <strong><?= htmlspecialchars($suppliername) ?></strong></div>

<div class="gr-view-form mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa-solid fa-box"></i> Goods Receiving
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered gr-table align-middle" id="gr-table">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Ordered Qty</th>
                    <th>Previously Received</th>
                    <th>Receiving Now</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($poItems as $row): 
                    $balance = $row['ordered_qty'] - $row['received_qty'];
                ?>
                <tr data-ordered="<?= $row['ordered_qty'] ?>" data-received="<?= $row['received_qty'] ?>" data-poitemid="<?= $row['po_item_id'] ?>">
                    <td><?= $row['item_name'] ?></td>
                    <td class="num-right"><?= number_format((float)$row['ordered_qty'], 2) ?></td>
                    <td class="num-right"><?= number_format((float)$row['received_qty'], 2) ?></td>
                    <td>
                        <?php if($statusLower !== 'cancelled' && $statusLower !== 'received'){ ?>
                            <input type="number" class="form-control form-control-sm receiving-now" min="0" max="<?= $balance ?>" value="0" <?= ($balance <= 0 ? 'disabled' : '') ?> placeholder="Qty">
                        <?php } ?>
                    </td>
                    <td class="num-right"><span class="balance-badge balance"><?= number_format((float)$balance, 2) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Remarks / Invoice #</strong></td>
                    <td colspan="4">
                        <textarea class="form-control form-control-sm remarks-area" id="gr-remarks" rows="2" placeholder="<?= $hasReceivableBalance ? 'Enter remarks or invoice number' : 'No remaining balance' ?>" <?= $hasReceivableBalance ? '' : 'disabled' ?>></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if($statusLower === 'approved' || $statusLower === 'partial_received'): ?>
        <div class="gr-action-bar">
            <button class="btn btn-success btn-sm btn-save-gr" id="save-gr">
                <i class="fa-solid fa-floppy-disk"></i> Save Receiving
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($history)): ?>
<div class="accordion mb-4 gr-history" id="historyAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="historyHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse" aria-expanded="false">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Receiving History
            </button>
        </h2>
        <div id="historyCollapse" class="accordion-collapse collapse" data-bs-parent="#historyAccordion">
            <div class="accordion-body p-0">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt No</th>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $items): ?>
                            <?php foreach($items as $h): ?>
                            <tr>
                                <td><?= $h['receipt_no'] ?></td>
                                <td><?= date('Y-m-d', strtotime($h['received_date'])) ?></td>
                                <td><?= $h['item_description'] ?></td>
                                <td><?= $h['received_qty'] ?></td>
                                <td><?= number_format($h['unit_price'],2) ?></td>
                                <td><?= number_format($h['amount'],2) ?></td>
                                <td><?= $h['received_by'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>







<script>
// Auto-update balance and validation
document.querySelectorAll('.receiving-now').forEach(input => {
    input.addEventListener('input', function() {
        const row = this.closest('tr');
        const ordered = parseFloat(row.dataset.ordered);
        const received = parseFloat(row.dataset.received);
        let receivingNow = parseFloat(this.value) || 0;

        const maxReceive = ordered - received;
        if(receivingNow > maxReceive) {
            receivingNow = maxReceive;
            this.value = maxReceive;
            swal("Warning","Cannot receive more than remaining balance!","warning");
        }

        row.querySelector('.balance').textContent = (ordered - received - receivingNow).toFixed(2);
    });
});

// Save Receiving
const saveGrButton = document.getElementById('save-gr');
if (saveGrButton) {
saveGrButton.addEventListener('click', function() {
    const data = [];
    document.querySelectorAll('#gr-table tbody tr[data-poitemid]').forEach(row => {
        const po_item_id = row.dataset.poitemid;
        const receivingNow = parseFloat(row.querySelector('.receiving-now').value) || 0;
        if(receivingNow > 0) {
            data.push({po_item_id, received_qty: receivingNow});
        }
    });

    if(data.length === 0){
        swal("Warning","No items selected to receive","warning");
        return;
    }

    // Check if remarks is filled
    const remarks = document.getElementById('gr-remarks').value.trim();
    if(remarks === ''){
        swal("Warning","Please enter remarks before receiving","warning");
        return; // stop execution
    }

    swal({
        title: 'Confirm Receiving',
        text: "Are you sure you want to save this Goods Received entry?",
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((willReceive) => {
        if (willReceive) {
            fetch('./Modules/Warehouse_Management/actions/save_goods_received.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ po_id: <?= json_encode($rowid) ?>, items: data, remarks })
            })
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success'){
                    swal("Saved!", res.message, "success").then(() => {
                        viewgr(
                            <?= json_encode($rowid) ?>,
                            <?= json_encode($prnumber) ?>,
                            <?= json_encode($ponumber) ?>,
                            <?= json_encode($suppliername) ?>,
                            <?= json_encode($status) ?>
                        )
                    });
                } else {
                    swal("Error", res.message, "error");
                }
            })
            .catch(() => {
                swal("Error", "Unable to save goods received. Please check module path/API response.", "error");
            });
        } else {
            swal("Cancelled","Receiving not saved","info");
        }
    });
});
}



function viewgr(rowid, prnumber, ponumber, suppliername, status){

    $.post("./Modules/Warehouse_Management/includes/goods_received_view.php", { rowid: rowid, prnumber: prnumber, ponumber: ponumber, suppliername: suppliername, status: status },
	function(data) {
		$('#contents').html(data);
	});

}

</script>
