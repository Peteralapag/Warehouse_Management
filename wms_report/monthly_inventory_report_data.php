<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
ini_set('memory_limit', '999M');
set_time_limit(300);

require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;

// Set session variables
$_SESSION['WMS_MONTH'] = $_POST['month'];
$_SESSION['WMS_YEAR'] = $_POST['year'];
$_SESSION['WMS_WEEK'] = $_POST['week'];
$recipient = $db->real_escape_string($_POST['recipient']);

$year = $db->real_escape_string($_POST['year']); 
$month = $db->real_escape_string($_POST['month']); 
$week = $db->real_escape_string($_POST['week']);

// Build search query
if(isset($_POST['search']) && $_POST['search'] != '') {
    $search = $db->real_escape_string($_POST['search']);
    $q = "AND (`item_description` LIKE '%$search%' OR `item_code` LIKE '%$search%')";
} else {
    $q = '';
}
// Determine date ranges based on week
$month_name = date("F", strtotime($year."-".$month));
switch($week) {
    case 1: $cnt_start = 1; $days_cnt = 7; $cnt_end = 7; break;
    case 2: $cnt_start = 8; $days_cnt = 7; $cnt_end = 14; break;
    case 3: $cnt_start = 15; $days_cnt = 7; $cnt_end = 21; break;
    case 4: $cnt_start = 22; $days_cnt = 7; $cnt_end = 28; break;
    case 5: $cnt_start = 29; $days_cnt = 3; $cnt_end = 31; break;
    case 0: $cnt_start = 1; $days_cnt = 31; $cnt_end = 31; break;
    default: $cnt_start = 1; $days_cnt = 31; $cnt_end = 31; break;
}

// Build column list
if($week != 0) {
    $columns = [];
    for ($days = $cnt_start; $days <= $cnt_end; $days++) {
        $columns[] = "day_" . str_pad($days, 2, '0', STR_PAD_LEFT);
    }
    $columnList = implode(', ', $columns);
    $col = "*, ".$columnList;
} else {
    $col = '*';
}

// Initialize variables for pre-fetched data
$itemCodes = [];
$inventoryRecords = [];
$monthlyInData = [];
$beginningData = [];
$pcountData = [];
$unitPriceData = [];

// Pre-fetch all items data
$sqlQuery = "SELECT * FROM wms_itemlist WHERE recipient='$recipient' $q";
$results = mysqli_query($db, $sqlQuery);

if ($results === false) {
    die("Query failed: " . $db->error);
}

// Collect item codes if we have results
if ($results && $results->num_rows > 0) {
    while ($row = $results->fetch_assoc()) {
        $itemCodes[] = $row['item_code'];
    }
    mysqli_data_seek($results, 0); // Reset pointer for main loop

    // Get all inventory records in one query if we have items
    if (!empty($itemCodes)) {
        $itemCodesList = "'" . implode("','", array_map([$db, 'real_escape_string'], $itemCodes)) . "'";
        $sqlIR = "SELECT * FROM wms_inventory_records WHERE item_code IN ($itemCodesList) AND month='$month' AND year='$year'";
        $irResult = mysqli_query($db, $sqlIR);
        
        if ($irResult) {
            while ($row = $irResult->fetch_assoc()) {
                $inventoryRecords[$row['item_code']] = $row;
            }
        }

        // Pre-fetch monthly in data for all items
        foreach ($itemCodes as $code) {
            for ($wk = 1; $wk <= 5; $wk++) {
                $monthlyInData[$code][$wk] = $inventory->getMonthlyIn($wk, $code, $month, $year, $db);
            }
            $beginningData[$code] = $inventory->getInventoryBeginning($cnt_start, $cnt_end, $days_cnt, $col, $code, $month, $year, $db);
            $pcountData[$code] = $inventory->GetMonthlyPcount($cnt_start, $cnt_end, $days_cnt, $code, $month, $year, $db);
            $unitPriceData[$code] = $function->GetUnitPriceRecords($code, $year, $month, $db);
        }
    }
}
?>
<style>
.table td, .table th {border: 1px solid #232323 !important;}
.color-inv-th { background:#78adfb !important }
.color-del-th { background:#fbdb9f !important;color:#232323 !important;}
.color-ttl-th { background:#fbcd76 !important;color:#232323 !important;}
.color-invout-th { background:#fce5a1 !important;color:#232323 !important;}
.color-total-th { background:#db858d !important; }
.border-report-title th { border-bottom:3px solid #636363; }
.footer-values td {background:#cecece;text-align:right;border-top:3px solid #232323;border-bottom:1px solid #232323;}
</style>
<table style="width:100%" class="table table-bordered table-striped">
    <thead>
        <tr class="border-report-title">            
            <th colspan="5" style="text-align:center;color:#fff" class="bg-primary">INVENTORYs</th>
            <th colspan="6" style="background:orange;text-align:center">DELIVERIES IN</th>
            <th colspan="<?php $kol = ($days_cnt + 1); echo $kol; ?>" style="text-align:center;color:#fff" class="bg-warning"><?php echo strtoupper($month_name); ?> INVENTORY OUT</th>
            <th colspan="7" style="text-align:center;color:#fff" class="bg-danger">TOTAL SUMMARY</th>
        </tr>
        <tr>
            <td style="width:50px !important;text-align:center;background:#aeaeae">#</td>
            <th style="width:70px" class="color-inv-th">ITEM CODE</th>
            <th class="color-inv-th">ITEM NAME</th>
            <th class="color-inv-th">UOM</th>
            <th style="width:70px" class="color-inv-th">BEGINNING</th>
            <th style="width:70px" class="color-del-th">WK1(1-7)</th>
            <th style="width:70px" class="color-del-th">WK2(8-14)</th>
            <th style="width:70px" class="color-del-th">WK3(15-21)</th>
            <th style="width:70px" class="color-del-th">WK4(22-28)</th>
            <th style="width:70px" class="color-del-th">WK5(29-31)</th>
            <th style="width:70px;text-align:center" class="color-ttl-th">TOTAL</th>
        <?php
            for ($th = $cnt_start; $th < $cnt_start + $days_cnt; $th++) {
                echo '<th style="text-align:center;width:50px" class="color-invout-th">Day ' . $th . '</th>';
            }        
        ?>
            <th class="color-invout-th">TOTAL OUT</th>
            <th class="color-total-th">EXPTD. STKS</th>
            <th class="color-total-th">PHY. COUNT</th>
            <th class="color-total-th">VARIANCES</th>
            <th class="color-total-th">UNIT PRICE</th>
            <th class="color-total-th">VAR. AMOUNT</th>
            <th class="color-total-th">SHORT</th>
            <th class="color-total-th">OVER</th>
        </tr>
    </thead>
    <tbody>
<?php
if ($results && $results->num_rows > 0) {
    $i = 0;
    $variance_amount_total=0;$shortages_total=0;$overages_total=0;$variances_total=0;
    $beginning=0;$weekly_1=0;$weekly_2=0;$weekly_3=0;$weekly_4=0;$weekly_5=0;$total_inv=0;
    
    while ($INVROW = $results->fetch_array()) {
        $i++;
        $itemcode = $INVROW['item_code'];
        
        // Get pre-fetched data
        $weekly_1 = $monthlyInData[$itemcode][1] ?? 0;
        $weekly_2 = $monthlyInData[$itemcode][2] ?? 0;
        $weekly_3 = $monthlyInData[$itemcode][3] ?? 0;
        $weekly_4 = $monthlyInData[$itemcode][4] ?? 0;
        $weekly_5 = $monthlyInData[$itemcode][5] ?? 0;
        
        $beginning = $beginningData[$itemcode] ?? 0;
        $total_inv = ($weekly_1 + $weekly_2 + $weekly_3 + $weekly_4 + $weekly_5 + $beginning);
?>    
        <tr>
            <td style="text-align:center;background:#aeaeae"><?php echo $i; ?></td>
            <td style="text-align:center"><?php echo htmlspecialchars($itemcode); ?></td>
            <td><?php echo htmlspecialchars($INVROW['item_description']); ?></td>
            <td style="text-align:center"><?php echo htmlspecialchars($INVROW['uom']); ?></td>
            <td style="text-align:right;"><?php echo number_format($beginning,2); ?></td>
            <td style="text-align:right"><?php echo number_format($weekly_1,2); ?></td>
            <td style="text-align:right"><?php echo number_format($weekly_2,2); ?></td>
            <td style="text-align:right"><?php echo number_format($weekly_3,2); ?></td>
            <td style="text-align:right"><?php echo number_format($weekly_4,2); ?></td>
            <td style="text-align:right"><?php echo number_format($weekly_5,2); ?></td>
            <td style="text-align:right"><?php echo number_format($total_inv,2); ?></td>
        <?php
            $total = 0;
            if (isset($inventoryRecords[$itemcode])) {
                $IRVROW = $inventoryRecords[$itemcode];
                for ($x = $cnt_start; $x < $cnt_start + $days_cnt; $x++) {
                    $td = str_pad($x, 2, '0', STR_PAD_LEFT);
                    $day = $IRVROW['day_' . $td] ?? 0;
                    if($day > 0) {
                        echo '<td style="text-align:center;width:50px;color:blue !important">' . htmlspecialchars($day) . '</td>';
                    } else {
                        echo '<td style="text-align:center;width:50px">--</td>';
                    }
                    $total += $day;
                }
            } else {
                for ($x = $cnt_start; $x < $cnt_start + $days_cnt; $x++) {
                    echo '<td style="text-align:center;width:50px">--</td>';
                }
            }
            
            $expected_stocks = ($total_inv - $total);
            $pcount = $pcountData[$itemcode] ?? 0;
            $variances = ($pcount - $expected_stocks);
            $unitPrice = $unitPriceData[$itemcode] ?? 0;
            $variance_amount = $unitPrice * $variances;
            $shortages = ($variance_amount < 0) ? $variance_amount : 0;
            $overages = ($variance_amount > 0) ? $variance_amount : 0;

            // Update totals
            $variance_amount_total += $variance_amount;
            $shortages_total += $shortages;
            $overages_total += $overages;
        ?>
            <td style="text-align:right"><?php echo number_format($total,2); ?></td>
            <td style="text-align:right"><?php echo number_format($expected_stocks,2); ?></td>
            <td style="text-align:right"><?php echo number_format($pcount,2); ?></td>
            <td style="text-align:right"><?php echo number_format($variances,2); ?></td>
            <td style="text-align:right"><?php echo number_format($unitPrice, 2); ?></td>
            <td style="text-align:right"><?php echo number_format($variance_amount,2); ?></td>
            <td style="text-align:right"><?php echo number_format($shortages,2); ?></td>
            <td style="text-align:right"><?php echo number_format($overages,2); ?></td>
        </tr>
<?php 
    }
    $col_span = ($days_cnt + 11);
?>
        <tr class="footer-values">
            <td colspan="<?php echo $col_span; ?>"></td>
            <td colspan="5" style="text-align:center;font-weight:600">GRAND TOTAL</td>
            <td><?php echo number_format($variance_amount_total,2); ?></td>
            <td><?php echo number_format($shortages_total,2); ?></td>
            <td><?php echo number_format($overages_total,2); ?></td>
        </tr>
<?php } else { ?>        
        <tr>
            <td colspan="50" style="text-align:center;color:#fff" class="bg-primary"><i class="fa fa-bell color-orange"></i> No Record(s) found.</td>
        </tr>
<?php } ?>
    </tbody>
</table>