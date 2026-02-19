<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
$search = '';
// $_SESSION['WMS_SHOW_LIMIT'] = $_POST['limit'];
if(isset($_POST['search']) && $_POST['search'] != '')
{
	$search = $_POST['search'];
	$q = "WHERE item_description LIKE '%$search%'";
} else {
	$q='';
}
?>
<style>
.status-close td {
	background: #fcd3dd !important;
}
</style>
<table style="width: 600px" class="table table-bordered table-hover">
	<tr class="search-header">
		<th style="width:50px;text-align:center">#</th>
		<th>ITEM CODE&nbsp;</th>
		<th>ITEM DESCRIPTION</th>
		<th>CREATED DATE</th>
	</tr>
	<tbody>	
<?php
//	$itemcode = $_POST['itemcode'];
		$sqlQuery = "SELECT * FROM wms_warehouse_transfer $q ORDER BY CASE WHEN status = 'Closed' THEN 1 ELSE 0 END, id DESC";
		$results = mysqli_query($db, $sqlQuery);    
		if ( $results->num_rows > 0 ) 
		{
			$n=0;
			while($ITEMROW = mysqli_fetch_array($results))  
			{	
				$n++;
				$rowid = $ITEMROW['id'];
				if($ITEMROW['created_date'] != '')
				{
					$cd = date("M,d Y @h:i A" ,strtotime($ITEMROW['created_date']));
				} else {
					$cd = '';
				}
				$run = 'onclick="getItemToFormID(\'edit\', \'' . $rowid . '\')"';
				if($ITEMROW['status'] == 'Closed')
				{
					$stats = 'status-close';
				} else {
					$stats = '';
				}
?>
	<tr class="search-data  <?php echo $stats; ?>" <?php echo $run; ?>>
		<td style="text-align:center"><?php echo $n; ?></td>
		<td style="text-align:center"><?php echo $ITEMROW['item_code']; ?></td>
		<td><?php echo $ITEMROW['item_description']; ?></td>
		<td><?php echo $cd; ?></td>
	</tr>
<?php } } else {?>
	<tr class="search-data">
		<td colspan="4" style="text-align:center"><i class="fa fa-bell"></i> No Item found</td>
	</tr>	
<?php } ?>
	</tbody>
</table>
