<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$_SESSION['WMS_INVSUBMENU'] = $_POST['invpage'];
$_SESSION['WMS_ITEMCATEGORY'] = $_POST['category'];
$inventory = new WMSInventory;
$year = date("Y");
$month = date("m");
$min_leadtime = $inventory->GetLeadTime('average_leadtime',$db);
$max_leadtime = $inventory->GetLeadTime('max_leadtime',$db);
$days_count = 31;
$year = $_REQUEST['year'];
$month = $_REQUEST['months'];
$date_string = $year."-".$month;
$_month = date("F", strtotime($date_string));
if($_REQUEST['limit'] != '' && $_REQUEST['category'] != '')
{
	$cat = $_REQUEST['category'];
	$cat_stock = $_REQUEST['category'];
	$limit = $_REQUEST['limit'];
	$q = "month='$month' AND year='$year' AND category='$cat' LIMIT $limit";
	$qq = "WHERE category='$cat' LIMIT $limit";
}
else if($_REQUEST['limit'] != '' && $_REQUEST['category'] == '')
{	
	$limit = $_REQUEST['limit'];
	$q = "month='$month' AND year='$year' LIMIT $limit";
	$qq =  "LIMIT $limit";
}
else if($_REQUEST['limit'] == '' && $_REQUEST['category'] == '')
{	
	$limit = $_REQUEST['limit'];
	$q = "$month='$month' AND year='$year'";
	$qq =  "LIMIT $limit";
}

?>
<style>.stable td {padding:2px 5px 2px 5px !important;}</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th style="width:130px">SUPPLIER ID</th>
			<th style="width:130px">ITEM CODE</th>
			<th>ITEM DESCRIPTION</th>
			<th style="width:80px">STOCK IN HAND</th>
			<th style="width:80PX">AVE. DAILY</th>
			<th style="width:80PX">MAX. DAILY</th>
			<th style="width:130px">RE-ORDER LEVEL</th>
			<th style="width:130px">SAFETY STOCKS</th>
			<th style="width: 100PX">INVENTORY STATUS</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT * FROM wms_inventory_stock $qq";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$i=0;
    	while($INVROW = mysqli_fetch_array($results))  
		{
			$i++;$average=0;
			$rowid = $INVROW['inventory_id'];
			$supplier_id = $INVROW['supplier_id'];
			$item_code = $INVROW['item_code'];
			$item = $INVROW['item_description'];
						
			$sqlQueryRecords = "SELECT * FROM wms_inventory_records WHERE item_code='$item_code' AND $q";
			$invResults = mysqli_query($db, $sqlQueryRecords);    
		    if ( $invResults->num_rows > 0 ) 
		    {
		    	while($INVENTORYROW = mysqli_fetch_array($invResults))  
				{
			    	$max_average=array();$cnt=array();$cnt=array();$max=0;
					for($i = 1; $i <= $days_count; $i++)
					{
						$x = str_pad($i, 2, '0', STR_PAD_LEFT);
						if($INVENTORYROW['day_'.$x] > 0)
						{
							$cnt++;
							$average += $INVENTORYROW['day_'.$x];
							$max = $INVENTORYROW['day_'.$x];
							$cnt[] = $INVENTORYROW['day_'.$x];
							$max_average[] = $max;
							$item_code = $INVENTORYROW['item_code'];							
						}												
					}
					$count=0;
					foreach ($cnt as $sales) {
					    $count = count($cnt);
					}
					if($count <= 0)
					{											
						$average_dr = 0;			
						$max_dr = 0;			
						$safety_stocks = 0;			
						$rol = 0;
					} else {
						$average_dr = round($average / $count);			
						$max_dr = max($max_average);			
						$safety_stocks = $max_dr * $max_leadtime - $average_dr * $min_leadtime;	
						$rol = $average_dr * $min_leadtime + $safety_stocks;
					}						
				}
		    } else {
			    $average_dr = 0;			
				$max_dr = 0;
		    	$rol = 0;
		    	$safety_stocks=0;
		    }
		    if($INVROW['stock_in_hand'] <= $safety_stocks)
			{
				$tdInv_color = 'style="background:#f0284a;color:white;text-align:center"';
				$tdInv_text = "Re Order";
			} 
			elseif($INVROW['stock_in_hand'] < $rol)
			{
				$tdInv_color = 'style="background:orange;color:white;text-align:center"';
				$tdInv_text = "Re Order";
			} else {
				$tdInv_color = 'style="background:green;color:white;text-align:center"';
				$tdInv_text = "Stock Sufficient";
			}
?>			
		<tr>
			<td style="width:50px;text-align:center"><?php echo $i; ?></td>
			<td><?php echo $supplier_id; ?></td>
			<td><?php echo $item_code; ?></td>
			<td><?php echo $INVROW['item_description']; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $INVROW['stock_in_hand']; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $average_dr; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $max_dr; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $rol; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $safety_stocks; ?></td>
			<td style="text-align:center"><?php echo $tdInv_text; ?></td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="9" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } ?>			
	</tbody>
</table>
