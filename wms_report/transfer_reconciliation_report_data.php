<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT'] . "/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

$cluster = $_POST['cluster'];
$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];
$recipient = $_POST['recipient'];

$_SESSION['WMS_DATE_FROM'] = $date_from;
$_SESSION['WMS_DATE_TO'] = $date_to;
$_SESSION['WMS_RECON_CLUSTER'] = $cluster;


$branches = [];

if ($cluster == '') {
    $sql = "SELECT branch FROM tbl_branch";
} else {
    $sql = "SELECT branch FROM tbl_branch WHERE location = ?";
}

if ($stmt = $db->prepare($sql)) {
    if ($cluster != '') {
        $stmt->bind_param("s", $cluster);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row['branch'];
    }
    $stmt->close();
} else {
    echo "Error: " . $db->error;
    exit;
}
	
	$sqlQuery = "SELECT * FROM wms_itemlist WHERE recipient='$recipient' AND item_description IS NOT NULL AND item_description != '' AND active=1";
	$results = $db->query($sqlQuery);
	/* ########################### WAREHOUSE ##########################*/
	$WHQUERY = "SELECT WBO.branch, WBO.item_code, WOR.order_received, WOR.delivery_date, SUM(WBO.wh_quantity) AS total
          FROM wms_branch_order WBO
          INNER JOIN wms_order_request WOR
          ON WBO.control_no = WOR.control_no
          WHERE WOR.order_delivered = 1 AND WBO.delivery_date BETWEEN ? AND ?
          GROUP BY WBO.branch, WBO.item_code";
	$stmt = $db->prepare($WHQUERY);	
	$totals = [];
	if ($stmt) {
	    $stmt->bind_param("ss", $date_from, $date_to);
	    $stmt->execute();
	    $WHresult = $stmt->get_result();	
	    // Store results in the $totals array
	    while ($row = $WHresult->fetch_assoc()) {
	        $totals[$row['branch']][$row['item_code']] = $row['total'];
	    }
	    $stmt->close();
	}
	/* ########################### BRANCH ##########################*/
	$BRANCHQUERY = "SELECT WBO.branch, WBO.item_code, WOR.order_received, WOR.delivery_date, SUM(WBO.actual_quantity) AS total
	          FROM wms_branch_order WBO
	          INNER JOIN wms_order_request WOR
	          ON WBO.control_no = WOR.control_no
	          WHERE WOR.order_delivered = 1 AND WOR.delivery_date BETWEEN ? AND ?
	          GROUP BY WBO.branch, WBO.item_code";
	$stmt = $db->prepare($BRANCHQUERY);	
	$branch_totals = [];
	if ($stmt) {
	    $stmt->bind_param("ss", $date_from, $date_to);
	    $stmt->execute();
	    $BRANCHresult = $stmt->get_result();
	
	    // Store results in the $totals array
	    while ($row = $BRANCHresult->fetch_assoc()) {
	        $branch_totals[$row['branch']][$row['item_code']] = $row['total'];
	    }
	    $stmt->close();
	}
?>
<style>
.table-style td, th {padding: 5px;font-size: 12px;}
.variables-td-style {text-align: center;}
.bg-green-color {background:#198754}
</style>
<table style="width: 100%" class="table table-bordered table-style table-striped" border="1">
    <thead>
    	<tr>
    		<th colspan="<?php echo (7 + count($branches) * 3); ?>" style="text-align:center;background:#696969;color:#fff"><span style="font-size:16px"><?php echo $recipient?> - INVENTORY RECONCILIATION</span></th>
    	</tr>
        <tr>
            <th colspan="4" style="text-align:center;background:#0e5333;color:#fff" valign="middle"><?php echo $cluster.'<br>( ' .$date_from . ' - ' . $date_to.' )'; ?></th>
            <?php foreach ($branches as $branch) { ?>
                <th colspan="3" style="text-align:center;background:#218397;color:#fff" valign="middle"><?php echo $branch; ?></th>
            <?php } ?>
            <th colspan="3" style="text-align:center;background:#696969;color:#fff;font-size:16px" valign="middle">TOTAL</th>
        </tr>
        <tr style="white-space:nowrap">
            <th style="width:50px;text-align:center;background:#198754;color:#fff">#</th>
            <th style="width:120px;text-align:center;background:#198754;color:#fff">ITEM CODE</th>
            <th style="background:#198754;color:#fff">ITEM NAME</th>
            <th class="bg-success" style="width:100px;text-align:center;background:#198754;color:#fff">PRICE</th>
            <?php foreach ($branches as $branch) { ?>
                <th style="background:#ffc107;color:#fff">WH OUT</th>
                <th style="background:#0d6efd;color:#fff">BR. IN</th>
                <th style="background:#dc3545;color:#fff">VAR.</th>
            <?php } ?>
            <th style="text-align:center;background:#aaa7a7;color:#ffc107">WH. OUT</th>
            <th style="text-align:center;background:#aaa7a7;color:#0d6efd">BR. IN</th>
            <th style="text-align:center;background:#aaa7a7;color:#dc3545">VARIANCE</th>
        </tr>
    </thead>
    <tbody>
<?php    
if ($results && $results->num_rows > 0) {
    $i = 0;
    $wh_total = 0;
    $br_total = 0;
    $var_total = 0;
    while ($RECONROW = $results->fetch_assoc()) {
        $i++;
        $item_code = $RECONROW['item_code'];
?>
        <tr style="white-space:nowrap">
            <td style="text-align:center"><?php echo $i; ?></td>
            <td style="text-align:center"><?php echo $item_code; ?></td>
            <td><?php echo $RECONROW['item_description']; ?></td>
            <td style="text-align:right"><?php echo number_format($RECONROW['unit_price'], 2); ?></td>
            <?php 

            $wh_row_total = 0;
            $br_row_total = 0;
            $var_row_total = 0;

            foreach ($branches as $branch)
            {
                $wh_out = $totals[$branch][$item_code] ?? 0;
                $br_in = $branch_totals[$branch][$item_code] ?? 0;
                $var = $wh_out - $br_in;

                $wh_row_total += $wh_out;
                $br_row_total += $br_in;
                $var_row_total += $var;
            ?>
                <td class="variables-td-style"><?php echo $wh_out; ?></td>
                <td class="variables-td-style"><?php echo $br_in; ?></td>
                <td class="variables-td-style"><?php echo $var; ?></td>
            <?php } ?>
            <td style="text-align:center"><?php echo $wh_row_total; ?></td>
            <td style="text-align:center"><?php echo $br_row_total; ?></td>
            <td style="text-align:center"><?php echo $var_row_total; ?></td>
        </tr>
<?php
    }        } else {
            echo "<tr><td colspan='" . (7 + count($branches) * 3) . "' style='text-align:center'>No items found.</td></tr>";
        }
        ?>
    </tbody>
</table>
<script>
$(document).ready(function() {
    if ($('#recondata').is(':empty')) {
        $('#copyButton').prop('disabled', true);
    } else {
        $('#copyButton').prop('disabled', false);
    }
});
</script>
