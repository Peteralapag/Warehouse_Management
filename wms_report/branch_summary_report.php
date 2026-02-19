<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT'] . "/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT'] . "/Modules/Warehouse_Management/class/Class.inventory.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.store_functions.php";
$storeFunction = new WMSStoreFunctions;
$function = new WMSFunctions;
$inventory = new WMSInventory;

$_SESSION['WMS_MONTH'] = $_POST['month'];
$_SESSION['WMS_YEAR'] = $_POST['year'];
$_SESSION['WMS_REPORT_TARGET'] = $_POST['target'];
$_SESSION['WMS_REPORT_CLUSTER'] = $_POST['cluster'];
$_SESSION['WMS_REPORT_CLASSES'] = $_POST['classes'];

if (isset($_POST['classes']) && $_POST['classes'] != '') {
    $class = $_POST['classes'];
    $q = "AND wbo.class='$class'";
} else {
    $q = "";
    $class = "ALL CLASSES";
}
if (isset($_POST['cluster']) && $_POST['cluster'] != '') {
    $classes = $_POST['classes'];
    $cluster = $_POST['cluster'];
    $qq = "WHERE location='$cluster'";
    $_header = $_POST['cluster'];
} else {
    $cluster = "";
    if (isset($_POST['branch']) && $_POST['branch'] != '') {
        $branch = $_POST['branch'];
        $qq = "WHERE branch='$branch'";
        $_header = $_POST['branch'];
    } else {
    	$qq='';
        $_header = "ALL BRANCHES";
    }
}

$recipient = $_POST['recipient'];
$month = $_POST['month'];
$year = $_POST['year'];

$cnt_start = 1;
$days_cnt = 31;
$cnt_end = 31;
$col = '*';
$as_of_date = date("F Y", strtotime($year . "-" . $month));
?>
<style>
.paper-size {margin: 0 auto;margin-top: 10px;width: 8.5in;/* height: 13in; */border: 1px solid #aeaeae;padding: 0.125in;}
.lamesa td,th {border: 1px solid #232323;padding: 3px;font-size: 10px;}
.qty-box td {text-align: center;border-left: 0;border-right: 0;border-bottom: 0;width: 25%;font-weight: 600}
.qty-box-data td {text-align: center;border-top: 0;border-left: 0;border-right: 0;border-bottom: 0;width: 25%;}
.numbox td {border: 0 !important}
.pos_num {color: blue;}
</style>
AAAAAAAAAAA
<div class="paper-size">
    <div class="paper-inner">

        <table style="width: 100%;border-collapse:collapse" class="lamesa">
            <tr>
                <td colspan="5" style="font-size:18px; text-align:center;font-weight:600"><?php echo $_header ?></td>
            </tr>
            <tr>
                <td style="width:230px;text-align:center">As of <?php echo $as_of_date ?></td>
                <th colspan="3" style="text-align:center"><?php echo $class ?></th>
            </tr>
            <tr>
                <td style="text-align:center;font-size:18px">BRANCH LIST</td>
                <td style="padding:0">
                    <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                    <table style="width: 100%">
                        <tr>
                            <td colspan="4" style="border:0;text-align:center;font-weight:600">QUANTITY</td>
                        </tr>
                        <tr class="qty-box">
                            <td style="border-right:1px solid #232323;">Beg.</td>
                            <td style="border-right:1px solid #232323">In</td>
                            <td style="border-right:1px solid #232323">Out</td>
                            <td>Ending</td>
                        </tr>
                    </table>
                    <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                </td>
                <td style="width:20px;border-bottom:0">&nbsp;</td>
                <td style="padding:0">
                    <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                    <table style="width: 100%">
                        <tr>
                            <td colspan="4" style="border:0;text-align:center;font-weight:600">AMOUNT</td>
                        </tr>
                        <tr class="qty-box">
                            <td style="border-right:1px solid #232323">Beg.</td>
                            <td style="border-right:1px solid #232323">In</td>
                            <td style="border-right:1px solid #232323">Out</td>
                            <td>Ending</td>
                        </tr>
                    </table>
                    <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                </td>
            </tr>
            <?php
            $date_range = $year . "-" . $month;
            $QuerySummary = "SELECT * FROM tbl_branch $qq ORDER BY branch ASC";
            $summaryResults = mysqli_query($db, $QuerySummary);
            if ($summaryResults->num_rows > 0) {
                $s = 0;$main_total=0;
                while ($ROWS = mysqli_fetch_array($summaryResults)) {
                    $s++;
                    $branch = $ROWS['branch'];
                    $in = $inventory->GetBranchOrder($branch, $month, $year, $class, $db);

                    $sqlQueryIR = "SELECT * FROM wms_branch_order WHERE branch='$branch' AND class='$class' AND (DATE_FORMAT(delivery_date, '%Y-%m') = '$date_range')";
                    $irResults = mysqli_query($db, $sqlQueryIR);
                    $total = 0; // Initialize total amount
                    if (mysqli_num_rows($irResults) > 0) {
                        while ($IRVROW = mysqli_fetch_array($irResults)) {
                            $a_qty = $IRVROW['actual_quantity'];
                            $u_price = $IRVROW['unit_price'];
                            $sub_total = $a_qty * $u_price;
                            $total += $sub_total; // Add sub-total to total amount
                        }
                        $in_amount = $total; // Assign total amount to $in_amount
                    } else {
                        $in_amount = 0;
                    }                    
                    if($in_amount > 0)
                    {
	                    $num_color = 'class="pos_num"';
                    } else {
                    	$num_color = '';
                    }
                    $main_total += $in_amount;

            ?>
                    <tr  ondblclick="showInDetails('<?php echo $branch ?>','<?php echo $month ?>','<?php echo $year ?>','<?php echo $class ?>')">
                        <td style="padding:0 !important">
                            <table style="width: 100%" class="numbox" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width:30px; text-align:center;border-right:1px solid #232323 !important"><?php echo $s; ?></td>
                                    <td style="padding-left:10px"><?php echo $function->limitString($ROWS['branch'], 25) ?></td>
                                </tr>
                            </table>
                            <td style="padding:0">
                                <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                                <table style="width: 100%">
                                    <tr class="qty-box-data">
                                        <td style="border-right:1px solid #232323">0</td>
                                        <td style="border-right:1px solid #232323"><?php echo $in ?></td>
                                        <td style="border-right:1px solid #232323">0</td>
                                        <td>0</td>
                                    </tr>
                                </table>
                                <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                            </td>
                            <td style="width:20px;border-top:0;border-bottom:0">&nbsp;</td>
                            <td style="padding:0">
                                <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                                <table style="width: 100%">
                                    <tr class="qty-box-data">
                                        <td style="border-right:1px solid #232323">0</td>
                                        <td style="border-right:1px solid #232323" <?php echo $num_color?>><?php echo $in_amount ?></td>
                                        <td style="border-right:1px solid #232323">0</td>
                                        <td>0</td>
                                    </tr>
                                </table>
                                <!-- @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ -->
                            </td>
                    </tr>
            <?php } ?>
                <tr>
                    <td colspan="3" style="border-right:0;text-align:center;font-weight:bold">TOTAL AMOUNT</td>
                    <td style="padding:0;border-left:0">
                    	<table style="width: 100%">
	                        <tr class="qty-box-data">
                                <td style="border-right:0">&nbsp;</td>
                                <td style="border:1px solid #232323;border-bottom:0;font-weight:600"><?php echo $main_total ?></td>
                                <td style="border-right:0">&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                    	</table>
                    </td>
                <tr>       
<?php            } else {
            ?>
                <tr>                	
                    <td colspan="4">NO RECORDS</td>
                <tr>
            <?php }?>
        </table>
    </div>
</div>
<script>
function showInDetails(branch,month,year,classes)
{
	$('#modaltitle').html("ITEM DETAILS");
	$.post("./Modules/Warehouse_Management/wms_report/show_in_details.php", { branch: branch,month: month,year: year,classes: classes },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
$(function() {
    if ($('#target').val() === 'BRANCHES') {
        $('#branch').show();
        $('.brnselector').show();
    } else {
        $('#branch').hide();
    }
});
</script>
