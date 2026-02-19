<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT'] . "/Modules/Warehouse_Management/class/Class.functions.php";

$_SESSION['WMS_DATEFROM'] = $_POST['dateFrom']; 
$_SESSION['WMS_DATETO'] = $_POST['dateTo']; 
$_SESSION['WMS_ITEMBRANCH'] = $_POST['branch'];
$_SESSION['WMS_ITEMFILTERS'] = $_POST['filters']; 

$recipient = $_POST['recipient']; 
$dateFrom = $_POST['dateFrom']; 
$dateTo = $_POST['dateTo']; 
$branch = $_POST['branch']; 
$filters = $_POST['filters']; 
?>
<table style="width: 100%" class="table table-bordered table-striped">
	<thead>
	<tr>
		<th style="text-align:center;width:50px">#</th>
		<th style="width:100px">Quantity</th>
		<th>Order Date</th>
		<th>Delivery Date</th>		
		<th>Control No.</th>
		<th>D.R. Number</th>
		<th>Item Description</th>
	</tr>
	</thead>
	<tbody>
<?php
	$QUERY = "SELECT * 
	FROM wms_order_request WOR
	INNER JOIN wms_branch_order WBO
	ON WOR.control_no = WBO.control_no
	WHERE WOR.recipient='$recipient' AND WBO.branch='$branch' AND WOR.delivery_date BETWEEN '$dateFrom' AND '$dateTo'  ORDER BY WBO.$filters";
	$RESULTS = mysqli_query($db, $QUERY);
    if ($RESULTS->num_rows > 0)
    {
    	$x=0;
        while ($ROWS = mysqli_fetch_array($RESULTS))
        {
        	$x++;
?>	
	<tr>
		<td style="text-align:center"><?php echo $x;?></td>
		<td style="text-align:right"><?php echo $ROWS['actual_quantity']?></td>
		<td><?php echo date("M d Y", strtotime($ROWS['trans_date']))?></td>
		<td><?php echo date("M d Y", strtotime($ROWS['delivery_date']))?></td>		
		<td><?php echo $ROWS['control_no']?></td>
		<td><?php echo $ROWS['dr_number']?></td>
		<td><?php echo $ROWS['item_description']?></td>
	</tr>
<?php
		}
	} else {
?>
	<tr>
		<td colspan="7" style="text-align:center"><i class="fa fa-bell color-orange"></i> No Records</td>
	</tr>
<?php } ?>
	</tbody>		
</table>


