<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$approver = $_SESSION['wms_appnameuser'] ?? '';
$rowid = $_POST['rowid'] ?? '';
$ponumber = $_POST['ponumber'] ?? '';
$suppliername = $_POST['suppliername'] ?? '';
$status = $_POST['status'] ?? '';


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

<h4 class="mb-3">
    <i class="fa-solid fa-building text-primary"></i>
    Supplier: <strong><?= htmlspecialchars($suppliername) ?></strong>
</h4>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa-solid fa-box"></i> Goods Receiving
    </div>
    <div class="card-body p-3">
        <table class="table table-bordered table-hover table-striped align-middle" id="gr-table">
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
                <?php while($row = $result->fetch_assoc()): 
                    $balance = $row['ordered_qty'] - $row['received_qty'];
                ?>
                <tr data-ordered="<?= $row['ordered_qty'] ?>" data-received="<?= $row['received_qty'] ?>" data-poitemid="<?= $row['po_item_id'] ?>">
                    <td><?= $row['item_name'] ?></td>
                    <td><?= $row['ordered_qty'] ?></td>
                    <td><?= $row['received_qty'] ?></td>
                    <td>
                        <?php if($status != 'CANCELLED' && $status != 'received'){ ?>
                            <input type="number" class="form-control form-control-sm receiving-now" min="0" max="<?= $balance ?>" value="0" <?= ($balance <= 0 ? 'disabled' : '') ?> placeholder="Qty">
                        <?php } ?>
                    </td>
                    <td><span class="badge bg-warning balance"><?= $balance ?></span></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td><strong>Remarks / Invoice #</strong></td>
                    <td colspan="4">
                        <textarea class="form-control form-control-sm" id="gr-remarks" rows="2" placeholder="Enter remarks or invoice number"></textarea>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if($status == 'APPROVED' || $status == 'PARTIAL_RECEIVED'): ?>
        <div class="text-end mt-3">
            <button class="btn btn-success btn-sm shadow-sm" id="save-gr">
                <i class="fa-solid fa-floppy-disk"></i> Save Receiving
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($history)): ?>
<div class="accordion mb-4" id="historyAccordion">
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

        row.querySelector('.balance').textContent = (ordered - received - receivingNow);
    });
});

// Save Receiving
document.getElementById('save-gr').addEventListener('click', function() {
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

    // âœ… Check if remarks is filled
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
                        viewgr(<?= json_encode($rowid) ?>, <?= json_encode($ponumber) ?>)
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



function viewgr(rowid, ponumber){

    $.post("./Modules/Warehouse_Management/includes/goods_received_view.php", { rowid: rowid, ponumber: ponumber },
	function(data) {
		$('#contents').html(data);
	});

}

</script>
