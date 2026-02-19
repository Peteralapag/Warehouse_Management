
<head>
<meta content="en-us" http-equiv="Content-Language">
</head>

<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

$branch = $_POST['branch'];
$month = $_POST['month']; 
$year = $_POST['year']; 
$class = $_POST['classes'];

$date_range = $year . "-" . $month;
$detailInQuery = "SELECT * FROM wms_branch_order WHERE branch='$branch' AND class='$class' AND (DATE_FORMAT(delivery_date, '%Y-%m') = '$date_range')";
$inQueryResults = $db->query($detailInQuery);
$item_count = $inQueryResults->num_rows;
?>
<style>
.tableFixHead {background:#fff;}
.tableFixHead  { overflow: auto; max-height: calc(100vh - 300px); min-height:500px; width:100% }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; background:dodgerblue; color:#fff }
.tableFixHead table  { border-collapse: collapse;}
.tableFixHead th, .tableFixHead td { font-size:14px; white-space:nowrap }
</style>
<div class="tableFixHead">
	<div style="padding:10px;text-align:center;font-size:16px;font-weight:600"><?php echo $branch?></div>
	<table style="width: 100%" class="table table-bordered">
		<thead>
			<tr>
				<th style="width:50px;text-align:center">#</th>
				<th>ITEM CODE&nbsp;</th>
				<th>ITEM DESCRIPTION</th>
				<th>UNIT PRICE</th>
				<th>QUANTITY</th>
			</tr>
		</thead>
		<tbody>
	<?php
		if($inQueryResults->num_rows > 0)
		{	
			$inQ = 0;
			$total_item=0;
			while($ROWIN = mysqli_fetch_array($inQueryResults))  
			{
				$inQ++;
				$total_item += $ROWIN['actual_quantity'];
	?>
			<tr>
				<td><?php echo $inQ?></td>
				<td><?php echo $ROWIN['item_code']?></td>
				<td><?php echo $ROWIN['item_description']?></td>
				<td><?php echo $ROWIN['unit_price']?></td>
				<td style="text-align:right"><?php echo $ROWIN['actual_quantity']?></td>
			</tr>
		</tbody>
	<?php } ?>
		<tr>
			<td colspan="3" style="text-align:center;font-weight:600">Total Items <?php echo $item_count ?></td>
			<td colspan="1" style="text-align:center;font-weight:600">TOTAL</td>
			<td style="text-align:right;font-weight:600;border-top:1px solid #232323"><?php echo number_format($total_item,2)?></td>
		</tr>
	<?php } else { ?>
		<tr>
			<td colspan="5" style="text-align:center"><i class="fa fa-bell"> No Records</i></td>
		</tr>
	<?php } ?>
	</table>
</div>