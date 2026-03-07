<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$prnumber = $_POST['prnumber'] ?? '';
$status = $_POST['status'] ?? '';

// Fetch all active warehouse items
$sql = "SELECT id, recipient, item_code, category, class, item_description, unit_price, uom 
        FROM wms_itemlist 
        WHERE recipient='WAREHOUSE' AND active=1
        AND (
            NOT EXISTS (
                SELECT 1 FROM wms_item_module_visibility mv0
                WHERE mv0.item_id = wms_itemlist.id AND mv0.active=1
            )
            OR EXISTS (
                SELECT 1 FROM wms_item_module_visibility mv1
                WHERE mv1.item_id = wms_itemlist.id
                AND mv1.module_code='Warehouse_Management'
                AND mv1.active=1
            )
        )";
$result = $db->query($sql);
$items = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $items[$row['id']] = $row;
    }
}

// Fetch existing PR items if revising
$existing_items = [];
$remarks = '';
if(!empty($prnumber)){
    // PR items
    $stmt = $db->prepare("SELECT pri.item_code, pri.quantity, pri.estimated_cost, pri.total_estimated 
                          FROM purchase_request_items pri
                          JOIN purchase_request pr ON pr.id = pri.pr_id
                          WHERE pr.pr_number=?");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $existing_items[$row['item_code']] = $row;
    }
    $stmt->close();

    // PR remarks
    $stmt2 = $db->prepare("SELECT remarks FROM purchase_request WHERE pr_number=?");
    $stmt2->bind_param("s", $prnumber);
    $stmt2->execute();
    $stmt2->bind_result($remarks);
    $stmt2->fetch();
    $stmt2->close();
}

?>


<table class="table table-bordered mb-0" id="itemsTable">
    <thead class="table-light">
        <tr>
            <th width="3%">#</th>
            <th width="3%">ITEM CODE</th>
            <th width="12%">Type</th>
            <th>Description</th>
            <th width="8%">Qty</th>
            <th width="8%">Unit</th>
            <th width="12%">Est. Cost</th>
            <th width="12%">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $i => $item): 
            $existing_qty = $existing_items[$item['item_code']]['quantity'] ?? 0;
            $existing_cost = $existing_items[$item['item_code']]['estimated_cost'] ?? $item['unit_price'];
            $existing_total = $existing_items[$item['item_code']]['total_estimated'] ?? 0;
        ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($item['item_code']) ?></td>
            <td><?= htmlspecialchars($item['category']) ?></td>
            <td><?= htmlspecialchars($item['item_description']) ?></td>
            <td>
                <input type="number" name="qty[]" class="form-control qty" 
                       value="<?= $existing_qty ?>" min="0">
            </td>
            <td><?= htmlspecialchars($item['uom']) ?></td>
            <td>
                <input type="text" name="cost[]" class="form-control cost" 
                       value="<?= $existing_cost ?>" readonly>
            </td>
            <td>
                <input type="text" class="form-control total_display" 
                       value="<?= number_format($existing_total,2) ?>" readonly>
                <input type="hidden" name="total[]" class="total_hidden" 
                       value="<?= $existing_total ?>">
            </td>
            <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
            <input type="hidden" name="category[]" value="<?= $item['category'] ?>">
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8" class="text-end">
                <strong>Grand Total:</strong>
                <span id="grandTotalDisplay">0.00</span>
                <input type="hidden" id="grandTotal" name="grandTotal" value="0.00">
            </td>
        </tr>
    </tfoot>
</table>

<div class="mb-3">
    <label for="pr_remarks"><strong>Remarks:</strong></label>
    <textarea id="pr_remarks" class="form-control" rows="2" placeholder="Enter remarks for this PR"><?= htmlspecialchars($remarks) ?></textarea>
</div>


<div style="float:right; margin:5px 1px">
    <button type="button" onclick="submitpr()" class="btn btn-primary ms-2">Submit</button>
</div>

<script>
// Compute totals
document.querySelectorAll('.qty').forEach(input => {
    input.addEventListener('input', function() {
        let row = this.closest('tr');
        let qty = parseFloat(this.value) || 0;
        let cost = parseFloat(row.querySelector('.cost').value) || 0;
        let total = qty * cost;

        row.querySelector('.total_hidden').value = total.toFixed(2);
        row.querySelector('.total_display').value = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

        computeGrandTotal();
    });
});

function computeGrandTotal(){
    let grand = 0;
    document.querySelectorAll('.total_hidden').forEach(input => {
        grand += parseFloat(input.value) || 0;
    });
    document.getElementById('grandTotal').value = grand.toFixed(2);
    document.getElementById('grandTotalDisplay').innerText = grand.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Submit PR

function submitpr() {
    let btn = $(this);
    btn.prop('disabled', true);

    let destination_branch = $('#destination_branch').val();
    let prnumber = '<?= $prnumber?>';

    if (!destination_branch) {
        swal("Warning", "Please select a Destination Branch.", "warning");
        btn.prop('disabled', false);
        return;
    }

    let items = [];
    $('#itemsTable tbody tr').each(function() {
        let qty = parseFloat($(this).find('.qty').val()) || 0;
        if (qty > 0) {
            items.push({
                item_type: $(this).find('input[name="category[]"]').val(),
                item_id: $(this).find('input[name="item_id[]"]').val(),
                qty: qty,
                cost: parseFloat($(this).find('.cost').val()) || 0,
                total: parseFloat($(this).find('.total_hidden').val()) || 0
            });
        }
    });

    if (items.length === 0) {
        swal("Warning", "Please enter quantity for at least one item.", "warning");
        btn.prop('disabled', false);
        return;
    }


    let remarks = $('#pr_remarks').val().trim();

    swal({
        title: "Confirm Submission",
        text: "Are you sure you want to submit this Purchase Request?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willSubmit) => {
        if (!willSubmit) {
            btn.prop('disabled', false);
            return;
        }

        $.post(
            '../../../Modules/Warehouse_Management/actions/actions.php',
            { 
                mode: 'submitprform',
                destination_branch: destination_branch,
                prnumber: prnumber,
                items: items,
                grandTotal: parseFloat($('#grandTotal').val()),
                remarks: remarks
            }
        )
        .done(function(response){
            console.log('RAW RESPONSE:', response);
            try {
                let json = JSON.parse(response);
                if (json.success) {
                    swal("Success", "PR saved: " + json.pr_number, "success")
                    .then(() => { location.reload(); });
                } else {
                    swal("System Message", json.message, "warning");
                    btn.prop('disabled', false);
                }
            } catch(e) {
                swal("System Message", "INVALID JSON RESPONSE", "warning");
                btn.prop('disabled', false);
            }
        })
        .fail(function(xhr){
            console.error(xhr.responseText);
            swal("System Error", "Check console for details.", "error");
            btn.prop('disabled', false);
        });
    });
}

</script>
