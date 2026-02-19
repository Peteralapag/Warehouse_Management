<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$branch = $_POST['branch'];
$_SESSION['WMS_MONTH'] = $_POST['month'];
$_SESSION['WMS_YEAR'] = $_POST['year'];
$recipient = $_POST['recipient'];
$year = $_POST['year']; 
$month = $_POST['month']; 
$month_name = date("F", strtotime($year."-".$month));
$columns = [];
for ($days = 1; $days <= 31; $days++) {
    $column = "day_" . str_pad($days, 2, '0', STR_PAD_LEFT); // Format day as two digits
    $columns[] = $column;
}
$columnList = implode(', ', $columns);
$col = "*, ".$columnList;
$ddate = $year."-".$month;
?>
<style>
.table td, .table th {border: 1px solid #232323 !important;}
.color-inv-th { background:#78adfb !important }
.color-del-th {	background:#fbdb9f !important;color:#232323 !important;}
.color-ttl-th {	background:#fbcd76 !important;color:#232323 !important;}
.color-invout-th { background:#fce5a1 !important;color:#232323 !important;}
.color-total-th { background:#db858d !important; }
.border-report-title th { border-bottom:3px solid #636363; }
.footer-values td {background:#cecece;text-align:right;border-top:3px solid #232323;border-bottom:1px solid #232323;}
.highlight {
  background-color: #fbfbd6;
}
</style>
<table style="width:100%" class="table table-bordered table-striped" id="highLight">
	<thead>
		<tr class="border-report-title">			
			<th colspan="4" style="text-align:center;color:#fff" class="bg-primary">INVENTORY</th>
			<th colspan="<?php $kol = (31 + 1); echo $kol; ?>" style="text-align:center;color:#fff" class="bg-warning"><?php echo strtoupper($month_name); ?> INVENTORY OUT</th>
		</tr>
		<tr>
			<td style="width:50px !important;text-align:center;background:#aeaeae">#</td>
			<th style="width:70px" class="color-inv-th">ITEM CODE</th>
			<th class="color-inv-th">ITEM NAME</th>
			<th class="color-inv-th">UOM</th>
		<?php
			for ($th = 1; $th < 1 + 31; $th++)
			{
				echo '<th style="text-align:center;width:50px" class="color-invout-th">Day ' . $th . '</th>';
			}		
		?>
			<th class="color-invout-th">TOTAL OUT</th>
		</tr>
	</thead>
	<tbody>
<?php
	$sqlQuery = "SELECT * FROM wms_itemlist WHERE recipient='$recipient'";
	$results = mysqli_query($db, $sqlQuery);
	if ($results->num_rows > 0) {
    $i = 0;
    $variance_amount_total=0;$shortages_total=0;$overages_total=0;$variances_total=0;
    $beginning=0;$weekly_1=0;$weekly_2=0;$weekly_3=0;$weekly_4=0;$weekly_5=0;$total_inv=0;
    while ($INVROW = mysqli_fetch_array($results))
    {
	    $i++;
	    $itemcode = $INVROW['item_code'];	    
?>	
		<tr>
			<td style="text-align:center;background:#aeaeae"><?php echo $i; ?></td>
			<td style="text-align:center"><?php echo $itemcode; ?></td>
			<td><?php echo $INVROW['item_description']; ?></td>
			<td style="text-align:center"><?php echo $INVROW['uom']; ?></td>
		<?php
			$total = 0;
			$positiveTotal = 0;			
//			$sqlQueryIR = "SELECT * FROM wms_branch_order WHERE DATE_FORMAT(delivery_date, '%Y-%m') = '$ddate' AND item_code='$itemcode' AND branch='$branch'";
			$sqlQueryIR = "SELECT * FROM wms_branch_order WHERE control_no IN ( SELECT control_no FROM wms_order_request WHERE status = 'Closed' ) AND DATE_FORMAT(delivery_date, '%Y-%m') = '$ddate' AND item_code='$itemcode' AND branch='$branch'";
			$irResults = mysqli_query($db, $sqlQueryIR);			
			$quantitiesByDate = [];
			while ($IRVROW = mysqli_fetch_array($irResults))
			{
				$transDate = date("j", strtotime($IRVROW['delivery_date']));
			    $quantity = $IRVROW['actual_quantity'];
			    $quantitiesByDate[$transDate] = isset($quantitiesByDate[$transDate]) ? $quantitiesByDate[$transDate] + $quantity : $quantity;

			    if ($quantity > 0) {
			        $positiveTotal += $quantity;
			    }
			}
			for ($x = 1; $x <= 31; $x++) {
			    if (isset($quantitiesByDate[$x])) {
			        echo '<td style="text-align:center;width:50px;color:blue !important">' . $quantitiesByDate[$x] . '</td>';
			        $total += $quantitiesByDate[$x]; // Add quantity to total
			    } else {
			        echo '<td style="text-align:center;width:50px">--</td>';
			    }
			}
		?>
			<td style="text-align:center;color:red;font-weight:bold"><?php echo number_format($total,2); ?></td>
		</tr>
<?php } } else { ?>		
		<tr>
			<td colspan="23" style="text-align:center;color:#fff" class="bg-primary"><i class="fa fa-bell color-orange"></i> No Record(s) found.</td>
		</tr>
<?php } ?>
	</tbody>
</table>
<script>
$(document).ready(function()
{
	$('table tr').click(function() {
        // Remove highlight class from all rows
        $('table tr').removeClass("highlight");
        
        // Add highlight class to the clicked row
        $(this).addClass("highlight");
    });
});
</script>
