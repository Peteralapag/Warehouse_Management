<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT'] . "/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

$cluster = $_POST['cluster'];
$date_from = $_POST['date_from'];
$date_to = $_POST['date_to'];
$recipient = $_POST['recipient'];

$_SESSION['WMS_CONDATE_FROM'] = $date_from;
$_SESSION['WMS_CONDATE_TO'] = $date_to;
$_SESSION['WMS_CONSUMPTION_CLUSTER'] = $cluster;


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
	$BRANCHQUERY = "SELECT cluster, branch, item_code, cunsumption, date_from, date_to, SUM(consumption) AS total
		FROM wms_branch_consumption
		WHERE date_range BETWEEN ? AND ?
		GROUP BY branch, item_code";
	$stmt = $db->prepare($BRANCHQUERY);	
	$branch_totals = [];
	if ($stmt) {
	    $stmt->bind_param("ss", $date_from, $date_to);
	    $stmt->execute();
	    $BRANCHresult = $stmt->get_result();
	
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
.warning-message {
	display: none;
}
.warning-message td {
	background:#fbf0db;
	border: 1px solid #fbbb4d;
	font-size: 14px;
}
</style>
<table style="width: 100%" class="table table-bordered table-style" border="1">
    <thead>
    	<tr class="warning-message" id="warningmsg">
    		<td colspan="<?php echo (7 + count($branches) * 3); ?>">
    			<marquee><strong>Warning!</strong> You are in reading mode and do not have permission to modify this data.</marquee>
    		</td>
    	</tr>
    	<tr>
    		<th colspan="<?php echo (7 + count($branches) * 3); ?>" style="text-align:center;background:#696969;color:#fff"><span style="font-size:16px"><?php echo $recipient?> - BRANCH CONSUMPTION</span></th>
    	</tr>
        <tr>
            <th colspan="4" style="text-align:center;background:#0e5333;color:#fff" valign="middle"><?php echo $cluster.'<br>( ' .$date_from . ' - ' . $date_to.' )'; ?></th>
            <?php foreach ($branches as $branch) { ?>
                <th colspan="3" style="text-align:center;background:#218397;color:#fff;width:250px !important" valign="middle"><?php echo $branch; ?></th>
            <?php } ?>
            <th colspan="3" style="text-align:center;background:#696969;color:#fff;font-size:16px;width:250px" valign="middle">TOTAL</th>
        </tr>
        <tr style="white-space:nowrap">
            <th style="width:50px;text-align:center;background:#198754;color:#fff">#</th>
            <th style="width:120px;text-align:center;background:#198754;color:#fff">ITEM CODE</th>
            <th style="background:#198754;color:#fff">ITEM NAME</th>
            <th class="bg-success" style="width:100px;text-align:center;background:#198754;color:#fff">PRICE</th>
            <?php foreach ($branches as $branch) { ?>
                <th colspan="3" style="background:#0d6efd;color:#fff;width:200px !important">
                	<div style="width:160px;text-align:center">CONSUMPTION</div>
                </th>
            <?php } ?>
            <th colspan="3" style="text-align:center;background:#aaa7a7;color:#0d6efd">CONSUMPTION</th>
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
        $rowid = $RECONROW['id'];
?>
        <tr style="white-space:nowrap">
            <td style="text-align:center"><?php echo $i; ?></td>
            <td style="text-align:center"><?php echo $item_code; ?></td>
            <td><?php echo $RECONROW['item_description']; ?></td>
            <td style="text-align:right"><?php echo number_format($RECONROW['unit_price'], 2); ?></td>
            <?php 

            $br_row_total = 0;

            foreach ($branches as $branch)
            {
                $consumption = $branch_totals[$branch][$item_code] ?? 0;
                if($consumption = 0)
                {
                	$consumption = '';
                }
                if($consumption = 0)
                {
                	
                }
               	$br_row_total += $consumption;
            ?>
                <td colspan="3" class="variables-td-style editable" data-rowid="<?php echo $rowid; ?>" data-branch="<?php echo $branch; ?>" data-item="<?php echo $item_code; ?>"><?php echo $consumption; ?></td>
            <?php } ?>
            <td colspan="3" style="text-align:center">
            	<div style="width:160px;text-align:center"><?php echo $br_in > 0 ? $br_in : ""; ?></div>
            </td> <!-- AUTOMATIC TOTAL WILL DO IT LATER -->
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

	GetAccess('p_write', 'Branch Consumption Data').then(hasAccess => {
        if (hasAccess) {
            $(".editable").attr("contenteditable", "true");
            $('#warningmsg').hide();
        } else {
            $(".editable").attr("contenteditable", "false");
            $('#warningmsg').show();
        }
    }).catch(error => {
        swal("Error", "An error occurred while checking permissions. Please try again.", "error");
    });
    

    $(".editable").on("keydown", function(e) {
        let current = $(this);
        let row = current.closest("tr");
        let colIndex = current.closest("td").index(); // Get column index
        let nextCell;

		let text = current.text();

	    if (
	        !e.key.match(/^[0-9.]$/) && 
	        !["Backspace", "Delete", "Tab", "Escape", "Enter", "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"].includes(e.key) &&
	        !(e.ctrlKey && ["a", "c", "v", "x"].includes(e.key)) // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
	    ) {
	        e.preventDefault();
	        return;
	    }	
	    if (e.key === "." && text.includes(".")) {
	        e.preventDefault();
	        return;
	    }
        switch (e.key) {
            case "ArrowRight": // Move right
                nextCell = current.closest("td").nextAll(".editable").first();
                break;

            case "ArrowLeft": // Move left
                nextCell = current.closest("td").prevAll(".editable").first();
                break;

            case "ArrowDown": // Move down
                let nextRow = row.next("tr");
                if (nextRow.length) {
                    nextCell = nextRow.find("td").eq(colIndex).filter(".editable");
                }
                break;

            case "ArrowUp": // Move up
                let prevRow = row.prev("tr");
                if (prevRow.length) {
                    nextCell = prevRow.find("td").eq(colIndex).filter(".editable");
                }
                break;

            case "Enter":
            case "Tab":
                e.preventDefault(); 

                nextCell = current.closest("td").nextAll(".editable").first();

                if (!nextCell.length) {
                    let nextRow = row.next("tr");
                    if (nextRow.length) {
                        nextCell = nextRow.find(".editable").first();
                    }
                }
                break;
        }

        if (nextCell && nextCell.length) {
            nextCell.focus();
        }
    });
    // CHANGE BG OF THE CELL ON FOCUS
    $(".editable").on("focus", function() {
        $(this).css("background-color", "#eaf5f7");
    }).on("blur", function() {
        $(this).css("background-color", "");
    });
	// SAVE EACH CELL ON THE DATABASE
	$(".editable").on("blur", function() {
        let cell = $(this);
        let newValue = cell.text().trim();
        let branch = cell.data("branch");
        let itemCode = cell.data("item");
        let rowId = cell.data("rowid");
        let cluster = "<?php echo $cluster; ?>";

        $.ajax({
            url: "./Modules/Warehouse_Management/actions/branch_consumption_process.php", // Your PHP script to process the update
            type: "POST",
            data: {
                branch: branch,
                item_code: itemCode,
                value: newValue,
                cluster: cluster
            },
            success: function(response) {
                console.log("Updated successfully:", response);
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
            }
        });
    });
});
</script>
