<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$_SESSION['WMS_MONTH'] = $_POST['month'];
$_SESSION['WMS_YEAR'] = $_POST['year'];
$_SESSION['WMS_WEEK'] = $_POST['week'];
$recipient = $_POST['recipient'];
$year = $_POST['year']; 
$month = $_POST['month']; 
$week = $_POST['week'];
$month_name = date("F", strtotime($year."-".$month));
if($week == 1)
{
	$cnt_start = 1;
	$days_cnt = 7;
	$cnt_end =  7;
}
else if($week == 2)
{
	$cnt_start = 8;
	$days_cnt = 7;
	$cnt_end =  14;
}
else if($week == 3)
{
	$cnt_start = 15;
	$days_cnt = 7;
	$cnt_end =  21;
}
else if($week == 4)
{
	$cnt_start = 22;
	$days_cnt = 7;
	$cnt_end =  28;
}
else if($week == 5)
{
	$cnt_start = 29;
	$days_cnt = 3;
	$cnt_end =  31;
}
else if($week == 0)
{
	$cnt_start = 1;
	$days_cnt = 31;
}
if($week != 0)
{
	$columns = [];
	for ($days = $cnt_start; $days <= $cnt_end; $days++) {
	    $column = "day_" . str_pad($days, 2, '0', STR_PAD_LEFT); // Format day as two digits
	    $columns[] = $column;
	}
	$columnList = implode(', ', $columns);
	$col = "*, ".$columnList;
} else {
	$col = '*';
}
	$q = "WHERE recipient='$recipient' AND month='$month' AND year='$year'";
?>
<style>
.color-inv-th { background:#78adfb !important }
.color-del-th {	background:#fbdb9f !important;color:#232323 !important;}
.color-invout-th { background:#fce5a1 !important;color:#232323 !important;}
.color-total-th { background:#db858d !important; }
.border-report-title th { border-bottom:3px solid #636363; }
.footer-values td {
	background:#cecece;
	text-align:right;
	border-top:3px solid #232323;
	border-bottom:1px solid #232323;
}
</style>
<table style="width:100%" class="table table-bordered table-striped">
	<thead>
		<tr class="border-report-title">			
			<th colspan="5" style="text-align:center;color:#fff" class="bg-primary">INVENTORY</th>
			<th colspan="2" style="background:orange;text-align:center">DELIVERIES IN</th>
			<th colspan="<?php $kol = ($days_cnt + 1); echo $kol; ?>" style="text-align:center;color:#fff" class="bg-warning"><?php echo strtoupper($month_name); ?> INVENTORY OUT</th>
			<th colspan="8" style="text-align:center;color:#fff" class="bg-danger">TOTALS</th>
		</tr>
		<tr>
			<td style="width:50px !important;text-align:center;background:#aeaeae">#</td>
			<th style="width:70px" class="color-inv-th">ITEM CODE</th>
			<th class="color-inv-th">ITEM NAME</th>
			<th class="color-inv-th">UOM</th>
			<th style="width:70px" class="color-inv-th">BEGINNING</th>
			<th style="width:70px" class="color-del-th">WEEK <?php echo $week; ?></th>
			<th style="width:70px" class="color-del-th">TOTAL</th>
		<?php
			for ($th = $cnt_start; $th < $cnt_start + $days_cnt; $th++)
			{
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
	$sqlQuery = "SELECT $col
    FROM wms_itemlist
    LEFT JOIN wms_inventory_records ON wms_itemlist.item_code = wms_inventory_records.item_code
    $q
	";
	$results = mysqli_query($db, $sqlQuery);
	if ($results->num_rows > 0) {
    $i = 0;
    $variance_amount_total=0;
	$shortages_total=0;
	$overages_total=0;
	$variances_total=0;
    while ($INVROW = mysqli_fetch_array($results))
    {
	    $i++;
	    $itemcode = $INVROW['item_code'];
	    $weekly_delivery = $inventory->getWeeklyIn($cnt_start,$cnt_end,$days_cnt,$itemcode,$month,$year,$db);		
	    $beginning = $inventory->getInventoryBeginning($cnt_start,$cnt_end,$days_cnt,$col,$itemcode,$month,$year,$db);	   	    
	    $total_inv = ($weekly_delivery + $beginning);
?>	
		<tr>
			<td style="text-align:center;background:#aeaeae"><?php echo $i; ?></td>
			<td style="text-align:center"><?php echo $itemcode; ?></td>
			<td><?php echo $INVROW['item_description']; ?></td>
			<td style="text-align:center"><?php echo $INVROW['uom']; ?></td>
			<td style="text-align:right;"><?php echo number_format($beginning,2); ?></td>
			<td style="text-align:right"><?php echo number_format($weekly_delivery,2); ?></td>
			<td style="text-align:right"><?php echo number_format($total_inv,2); ?></td>
		<?php
			$total=0;
            for ($x = $cnt_start; $x < $cnt_start + $days_cnt; $x++)
            {
	            $td = str_pad($x, 2, '0', STR_PAD_LEFT);
                $day = $INVROW['day_' . $td];
                echo '<td style="text-align:center;width:50px">' . $day . '</td>';
                $total += $day;
            }
            $expected_stocks = ($total_inv - $total);
            $pcount = $inventory->GetWeeklyPcount($cnt_start,$cnt_end,$days_cnt,$itemcode,$month,$year,$db);
            $variances = ($pcount - $expected_stocks);
            $variance_amount = ($function->GetUnitPrice($itemcode,$db) * $variances);
            $shortages = ($variance_amount < 0) ? $variance_amount : 0;
            $overages = ($variance_amount > 0) ? $variance_amount : 0;

            
		?>
			<td style="text-align:right"><?php echo number_format($total,2); ?></td>
			<td style="text-align:right"><?php echo number_format($expected_stocks,2); ?></td>
			<td style="text-align:right"><?php echo number_format($pcount,2); ?></td>
			<td style="text-align:right"><?php echo number_format($variances,2); ?></td>
			<td style="text-align:right"><?php echo $function->GetUnitPrice($itemcode,$db); ?></td>
			<td style="text-align:right"><?php echo number_format($variance_amount,2); ?></td>
			<td style="text-align:right"><?php echo number_format($shortages,2); ?></td>
			<td style="text-align:right"><?php echo number_format($overages,2); ?></td>
		</tr>
<?php 
		$variance_amount_total += $variance_amount;
	    $shortages_total += $shortages;
	    $overages_total += $overages; 
	}
	$col_span = ($days_cnt + 7)
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
			<td colspan="19" style="text-align:center;color:#fff" class="bg-primary"><i class="fa fa-bell color-orange"></i> No Record(s) found.</td>
		</tr>
<?php } ?>
	</tbody>
</table>
