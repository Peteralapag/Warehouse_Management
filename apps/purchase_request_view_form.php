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

<table class="table table-bordered mb-0" id="itemsTable">
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
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($item['item_type']) ?></td>
                    <td><?= htmlspecialchars($item['item_code']) ?></td>
                    <td><?= htmlspecialchars($item['item_description']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td><?= number_format($item['estimated_cost'],2) ?></td>
                    <td><?= number_format($item['total_estimated'],2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No items found.</td></tr>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
            <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
        </tr>
        <tr>
	        <td colspan="8">
	            <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($remarks)) ?>
	        </td>
    	</tr>
    </tfoot>
</table>





<!-- Approve Button Section -->
<?php if($canApprove && ($status === 'pending' || $status === 'returned')): ?>
<div class="mt-3 text-end" style="margin-right:5px">
    <span class="me-3"><strong>Approver:</strong> <?= htmlspecialchars($approver) ?></span>
    <button type="button" class="btn btn-success btn-sm" onclick="approvePR('<?= $prnumber ?>','<?= $status?>')">
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
