<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$approver = $_SESSION['wms_appnameuser'] ?? '';
$prnumber = $_POST['prnumber'] ?? '';

$status = $_POST['status'] ?? '';
if (empty($prnumber)) {
    echo '<script>swal("Error","PR Number not provided","error");</script>';
    exit;
}


// Fetch PR items
$stmt = $db->prepare("
    SELECT pri.item_type, pri.item_code, pri.item_description, pri.quantity, pri.unit, pri.estimated_cost, pri.total_estimated
    FROM purchase_request_items pri
    JOIN purchase_request pr ON pr.id = pri.pr_id
    WHERE pr.pr_number = ?
");
$stmt->bind_param("s", $prnumber);
$stmt->execute();
$result = $stmt->get_result();
$items = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Calculate grand total
$grandTotal = 0;
foreach($items as $item){
    $grandTotal += $item['total_estimated'];
}

// Check if logged-in user is an approver
$canApprove = false;
$stmt2 = $db->prepare("
    SELECT COUNT(*) 
    FROM tbl_system_permission 
    WHERE acctname = ? 
      AND applications = 'Warehouse Management'
      AND modules = 'Purchase Request' 
      AND p_approver = 1
");
$stmt2->bind_param("s", $approver);
$stmt2->execute();
$stmt2->bind_result($count);
$stmt2->fetch();
$canApprove = $count > 0;
$stmt2->close();

$stmt3 = $db->prepare("SELECT remarks FROM purchase_request WHERE pr_number = ?");
$stmt3->bind_param("s", $prnumber);
$stmt3->execute();
$stmt3->bind_result($remarks);
$stmt3->fetch();
$stmt3->close();
?>

<style>
.pr-view-form {
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(15, 23, 42, 0.04);
}
.pr-view-table {
    margin-bottom:0;
    font-size:13px;
    min-width:980px;
}
.pr-view-table thead th {
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
.pr-view-table tbody td {
    padding:8px 8px !important;
    vertical-align:middle;
    color:#0f172a;
    background:#fff;
    border-color:#eef2f6;
}
.pr-view-table tbody tr:nth-child(even) td {
    background:#fcfdff;
}
.pr-view-table tbody tr:hover td {
    background:#f4f8ff;
}
.col-center {text-align:center;}
.col-right {text-align:right;}
.item-code {
    font-weight:700;
    color:#0f172a;
}
.empty-row {
    text-align:center;
    color:#64748b;
    padding:22px 8px !important;
}
.pr-view-table tfoot td {
    background:#fbfcfe;
    border-top:1px solid #e5e7eb;
    padding:10px 8px !important;
}
.grand-total-label {
    font-size:13px;
    font-weight:700;
    color:#334155;
}
.grand-total-value {
    font-size:14px;
    font-weight:700;
    color:#0f172a;
}
.remarks-box {
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:8px;
    padding:10px 12px;
    font-size:13px;
    color:#334155;
    line-height:1.45;
}
.approval-bar {
    margin-top:10px;
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:10px;
    padding:10px 4px 2px;
}
.approval-label {
    font-size:12px;
    color:#475569;
}
.btn-approve-pr {
    padding:6px 12px;
    font-size:12px;
    font-weight:600;
    border-radius:6px;
}
</style>

<div class="pr-view-form">
<table class="table table-bordered pr-view-table" id="itemsTable">
    <thead class="table-light">
        <tr>
            <th width="3%">#</th>
            <th width="12%">ITEM TYPE</th>
            <th width="12%">ITEM CODE</th>
            <th>ITEM DESCRIPTION</th>
            <th width="8%">QTY</th>
            <th width="8%">Unit</th>
            <th width="12%">Est. Cost</th>
            <th width="12%">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($items)): ?>
            <?php $i=0; foreach($items as $item): $i++; ?>
                <tr>
                    <td class="col-center"><?= $i ?></td>
                    <td><?= htmlspecialchars($item['item_type']) ?></td>
                    <td class="item-code"><?= htmlspecialchars($item['item_code']) ?></td>
                    <td><?= htmlspecialchars($item['item_description']) ?></td>
                    <td class="col-center"><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td class="col-right"><?= number_format($item['estimated_cost'],2) ?></td>
                    <td class="col-right"><strong><?= number_format($item['total_estimated'],2) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="empty-row">No items found.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" class="col-right"><span class="grand-total-label">Grand Total:</span></td>
            <td class="col-right"><span class="grand-total-value">₱ <?= number_format($grandTotal, 2) ?></span></td>
        </tr>
        <tr>
            <td colspan="8">
                <div class="remarks-box"><strong>Remarks:</strong> <?= nl2br(htmlspecialchars((string)$remarks)) ?></div>
            </td>
    	</tr>
    </tfoot>
</table>
</div>





<!-- Approve Button Section -->
<?php if($canApprove && ($status === 'pending' || $status === 'returned')): ?>
<div class="approval-bar">
    <span class="approval-label"><strong>Approver:</strong> <?= htmlspecialchars($approver) ?></span>
    <button type="button" class="btn btn-success btn-sm btn-approve-pr" onclick="approvePR('<?= $prnumber ?>','<?= $status?>')">
        <i class="fa-solid fa-check"></i> Approve this?
    </button>
</div>
<?php endif; ?>

<script>

function approvePR(prnumber,status) {
    swal({
        title: "Confirm Approval",
        text: "Are you sure you want to approve PR: " + prnumber + "?",
        icon: "warning",
        buttons: true,
        dangerMode: false,
    }).then((willApprove) => {

        if (!willApprove) return;

        $.ajax({
            url: "../../../Modules/Warehouse_Management/actions/actions.php",
            type: "POST",
            dataType: "json", // <-- VERY IMPORTANT
            data: {
                mode: "approvepurchaserequest",
                prnumber: prnumber
            },
            success: function(res) {
                console.log("RESPONSE:", res);

                if (res.success) {
                    swal("Approved!", res.message ?? ("PR " + prnumber + " approved"), "success")
                        .then(() => window.location.reload());
                } else {
                    swal("Error", res.message ?? "Approval failed", "error");
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                swal("Error", "Server error occurred", "error");
            }
        });

    });
}



</script>
