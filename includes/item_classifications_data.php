<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$recipient = $_POST['recipient'];
if(isset($_POST['search']) && $_POST['search'] != '')
{
	$search = $_POST['search'];
	$q = "AND item_description LIKE '%$search%' OR item_code LIKE '%$search%'";
} else {
	$q = '';
}
?>
<style>
.table-data td {
	cursor: pointer;
}
</style>
<table  id="highlightrow" style="width: 100%" class="table table-striped table-hover table-bordered table-data">
	<thead>
		<tr>
			<th style="width:60px;text-align:center">#</th>
			<th>ITEMCODE</th>
			<th>ITEM DESCRIPTION</th>
			<th>BAD ORDER</th>
			<th>DAMAGE</th>
			<th>SECONDS</th>
			<th>RE-CLASSIFY</th>
			<th>FOR DISPOSAL</th>
			<th>DETAILS</th>
		</tr>
	</thead>
	<tbody>
<?php
	$sqlQuery = "SELECT * FROM wms_itemlist WHERE recipient='$recipient' $q";
	$results = mysqli_query($db, $sqlQuery);
	if ($results->num_rows > 0)
	{
		$x=0;
	    while ($ROWS = mysqli_fetch_array($results))
	    {
	    	$x++;
	?>	
			<tr>
				<td style="text-align:center;background "><?php echo $x; ?></td>
				<td style="text-align:center"><?php echo $ROWS['item_code']; ?></td>
				<td><?php echo $ROWS['item_description']; ?></td>
				<td><?php echo $function->GetItemClassData('bad_order_qty',$ROWS['item_code'],$db); ?></td>
				<td><?php echo $function->GetItemClassData('damage_qty',$ROWS['item_code'],$db); ?></td>
				<td><?php echo $function->GetItemClassData('seconds_qty',$ROWS['item_code'],$db); ?></td>
				<td><?php echo $function->GetItemClassData('reclassify_qty',$ROWS['item_code'],$db); ?></td>
				<td><?php echo $function->GetItemClassData('disposal_qty',$ROWS['item_code'],$db); ?></td>
				<td style="padding:0 !important;width:120px"><button class="btn btn-success w-100">Details</button></td>
			</tr>
<?php
		}
	} else { 
?>			
			<tr>
				<td colspan="9">&nbsp;</td>
			</tr>
<?php } ?>		
	</tbody>
</table>
<script>

document.getElementById("highlightrow").addEventListener("click", function(event) {
    let clickedRow = event.target.closest("tr");
    if (!clickedRow) return;

    if (lastClickedRow && lastClickedRow !== clickedRow) {
        lastClickedRow.style.backgroundColor = ""; // Reset previous row background
        lastClickedRow.style.color = ""; // Reset previous row background
    }

    lastClickedRow = clickedRow; // Update last clicked row
});
document.getElementById("highlightrow").addEventListener("dblclick", function(event) {
    let clickedRow = event.target.closest("tr");
    if (!clickedRow) return;

    clickedRow.style.backgroundColor = "#0699cf"; // Change background color of current row
    clickedRow.style.color= "#fff"; // Change background color of current row
});
if (typeof lastClickedRow === 'undefined') {
    let lastClickedRow = null;
}
</script>
