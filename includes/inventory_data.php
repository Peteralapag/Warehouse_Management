<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$year = date("Y");
$month = date("m");
$min_leadtime = $inventory->GetLeadTime('average_leadtime',$db);
$max_leadtime = $inventory->GetLeadTime('max_leadtime',$db);
$days_count = 31;
if(isset($_POST['location']) && $_POST['location'] != '')
{
	$_SESSION['WMS_ITEM_LOCATION'] = $_POST['location'];
} else {
	unset($_SESSION['WMS_ITEM_LOCATION']);
}
if(isset($_POST['limit']) && $_POST['limit'] != '')
{
	$_SESSION['WMS_SHOW_LIMIT'] = $_POST['limit'];
} else {
	unset($_SESSION['WMS_SHOW_LIMIT']);
}
if($_POST['limit'] != '')
{
	$limit = "LIMIT ".$_POST['limit'];
} else {
	$limit = "";
}
if(isset($_POST['search']))
{
	$search = $_POST['search'];	
	$location = $_POST['location'];
	
	if(isset($_POST['location']) && $_POST['location'] != '')
	{
		$q = "WHERE wms_itemlist.item_location='$location' AND  (wms_inventory_stock.item_code LIKE '%$search%' OR wms_inventory_stock.item_description LIKE '%$search%')";
	} else {	
		$q = "WHERE wms_inventory_stock.item_code LIKE '%$search%' OR wms_inventory_stock.item_description LIKE '%$search%'";
	}

} else {
	if(isset($_POST['location']) && $_POST['location'] != '')
	{
		$search = $_POST['search'];	
		$location = $_POST['location'];
		$q = "WHERE wms_itemlist.item_location='$location'";
	} else {
		$q = "ORDER BY wms_inventory_stock.supplier_id DESC";
	}
}
?>
<style>
.stable td {
	padding:2px 5px 2px 5px !important;
}
.sup-title {
	position:absolute;
	display: none;
	border:1px solid blue;;
	padding:5px 10px 5px 10px;
	white-space:nowrap;
	border-radius:5px;
	cursor: pointer;
	left:50px
}
</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:50px;text-align:center">#</th>
			<th style="width:130px">SUPPLIER ID</th>
			<th style="width:130px">ITEM CODE</th>
			<th>ITEM DESCRIPTION</th>
			<th style="width:80px">STOCK IN HAND</th>
			<th style="width:80px">UOM</th>
			<th style="width:80PX">AVE. DAILY</th>
			<th style="width:80PX">MAX. DAILY</th>
			<th style="width:130px">RE-ORDER LEVEL</th>
			<th style="width:130px">SAFETY STOCKS</th>
			<th style="width: 100PX">INVENTORY STATUS</th>
			<th style="width:90px">ACTION</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "
		SELECT * FROM wms_inventory_stock 
        INNER JOIN wms_itemlist ON wms_inventory_stock.item_code = wms_itemlist.item_code
        $q $limit
    ";
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
			$sqlQueryRecords = "SELECT * FROM wms_inventory_records WHERE year='$year' AND month='$month' AND item_code='$item_code'";
			$invResults = mysqli_query($db, $sqlQueryRecords);    
		    if ( $invResults->num_rows > 0 ) 
		    {
		    	while($INVENTORYROW = mysqli_fetch_array($invResults))  
				{
			    	$max_average=array();$cnt=array();$cnt=array();$max=0;
					for($n = 1; $n <= $days_count; $n++)
					{
					
						$x = str_pad($n, 2, '0', STR_PAD_LEFT);
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
			<td style="position:relative" id="supplier<?php echo $i; ?>"><?php echo $supplier_id; ?>
				<div class="sup-title" id="supplier_wrapper<?php echo $i; ?>" style="background:#ffffff !important">
					<?php echo $function->GetSupplierName($supplier_id,$db); ?>
				</div>
			</td>
			<td><?php echo $item_code; ?></td>
			<td><?php echo $INVROW['item_description']; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $INVROW['stock_in_hand']; ?></td>
			<td style="text-align:center !important"><?php echo $INVROW['uom']; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $average_dr; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $max_dr; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $rol; ?></td>
			<td style="text-align:right;padding-right:20px !important"><?php echo $safety_stocks; ?></td>
			<td <?php echo $tdInv_color; ?>><?php echo $tdInv_text; ?></td>
			<td style="padding:0 !important">
				<div class="btn-group" role="group" aria-label="Ronan Sarbon">
					<button class="btn btn-primary" style="padding:5px" onclick="itemDetails('<?php echo $item_code; ?>','<?php echo $item; ?>')">Item Details</button>
				</div>
			</td>
		</tr>
<script>
$(function()
{
	$('#supplier' + '<?php echo $i; ?>').mouseenter(function()
	{
    	$('#supplier_wrapper' + '<?php echo $i; ?>').show();
		}).mouseleave(function()
	{
    	$('#supplier_wrapper' + '<?php echo $i; ?>').hide();
	});
});
</script>		
<?PHP 	} } else { ?>
		<tr>
			<td colspan="12" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Records</td>
		</tr>
<?PHP } ?>			
	</tbody>
</table>
<script>
function itemDetails(itemcode,item)
{
	
}
function itemDetails(itemcode,item)
{
	$('#modaltitle').html("ITEM DETAILS");
	$.post("./Modules/Warehouse_Management/apps/inventory_item_details.php", { item_code: itemcode, item: item },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
