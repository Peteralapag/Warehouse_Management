<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$_SESSION['WMS_YEARS'] = $_POST['theyears'];
$_SESSION['WMS_MONTHS'] = $_POST['themonths'];
$_SESSION['WMS_DAYS'] = $_POST['thedays'];

$year = $_POST['theyears'];
$month = $_POST['themonths'];
$day = $_POST['thedays'];
$trans_date = $year."-".$month."-".$day;
if(isset($_POST['location']) && $_POST['location']!='')
{
	$item_location = $_POST['location'];
	$q = "item_location='$item_location' AND ";
} else {
	$q = "";
}
?>
<style>
.tableFixSetup {background:#fff}
.tableFixSetup { overflow: auto; height: calc(100vh - 288px); width:100% }
.tableFixSetup thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff; text-align:center }
.tableFixSetup table  { border-collapse: collapse;}
.tableFixSetup th, .tableFixSetup td { font-size:14px; padding:4px; border: 1px solid #cecece }
.tableFixSetup td { position:relative; }
.tableFixSetup input {position: absolute;top: 0;left: 0;bottom:0;margin: 0;border:0;width:100%;font-size:14px;text-align:center;cursor: pointer;outline:none;padding:5px}
.pcount-input-value {}
</style>
<div class="tableFixSetup">
	<table style="width: 100%">
		<thead>
			<tr>
				<th style="width:50px">#</th>
				<th style="width:100px">ITEMCODE</th>
				<th>ITEM NAME</th>
				<th>UM</th>
				<th style="width:100px">PHY.COUNT</th>				
				<!-- th style="width:170px">EXPIRATION DATE</th>
				<th style="width:270px">REMARKS</th -->
			</tr>
		</thead>
		<tbody>
<?php
	$queryItems = "SELECT * FROM wms_itemlist WHERE $q active=1";
	$resultsItems = $db->query($queryItems);			
	$j=0;
	while($ITEMROWS = mysqli_fetch_array($resultsItems))  
	{
		$j++;
		$itemcode = $ITEMROWS['item_code'];
?>		
			<tr>
				<td style="text-align:center"><?php echo $j; ?>
					<input id="category<?php echo $j; ?>" type="hidden" value="<?php echo $ITEMROWS['category']; ?>">
				</td>
				<td><input id="itemcode<?php echo $j; ?>" type="text" value="<?php echo $ITEMROWS['item_code'];?>" disabled></td>
				<td><input id="itemname<?php echo $j; ?>" type="text" style="text-align:left" value="<?php echo $ITEMROWS['item_description'];?>" readonly></td>
				<td><input id="uom<?php echo $j; ?>" type="text" value="<?php echo $ITEMROWS['uom'];?>" disabled></td>
				<td><input id="phycount<?php echo $j; ?>" type="number" value="<?php echo $inventory->GetPcountData($itemcode,$trans_date,'p_count',$db); ?>" onblur="changeThis('<?php echo $j; ?>')"></td>
				<!-- td><input id="expdate<?php echo $j; ?>" type="date" value="<?php echo $inventory->GetPcountData($itemcode,$trans_date,'expiration_date',$db); ?>" onchange="changeThis('<?php echo $j; ?>')"></td>
				<td><input id="remarks<?php echo $j; ?>" type="text" value="<?php echo $inventory->GetPcountData($itemcode,$trans_date,'remarks',$db); ?>" onchange="changeThis('<?php echo $j; ?>')"></td -->
			</tr>
<?php } ?>			
		</tbody>			
	</table>
	<div id="results"></div>
</div>
<script>
function changeThis(elemid)
{
	var mode = 'saveinvsetup';
	var trans_date = '<?php echo $trans_date; ?>';
	var itemcode = $('#itemcode' + elemid).val();
	var category = $('#category' + elemid).val();
	var itemname = $('#itemname' + elemid).val();
	var phycount = $('#phycount' + elemid).val();
	var uom = $('#uom' + elemid).val();
/*	var expdate = $('#expdate' + elemid).val();
	var remarks = $('#remarks' + elemid).val();
*/	

	$.post("./Modules/Warehouse_Management/actions/actions.php",
	{ 
		mode: mode,
		category: category,
		trans_date: trans_date,
		itemcode: itemcode,
		itemname: itemname,
		phycount: phycount,
		uom: uom
	},
	function(data) {		
		$('#results').html(data);
	});
}
</script>
