<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$_SESSION['WMS_RTV_SEARCH'] = $_POST['search_term'];
$search_term = $_POST['search_term'];
$q = "WHERE po_no LIKE '%$search_term%' OR si_no LIKE '%$search_term%' OR received_date LIKE '%$search_term%'";

$sqlQuery = "SELECT * FROM wms_receiving_details $q";
$results = mysqli_query($db, $sqlQuery);    

?>
	<table style="width: 100%" class="table table-bordered rtv-table">
		<thead>
			<tr>
				<th>SUPPLIER</th>				
				<th>DESCRIPTION</th>
				<th>PO NUMBER</th>
				<th>SALES INVOICE</th>
				<th>QUANTITY</th>
				<th>RECEIVED BY</th>
				<th>RECEIVED DATE</th>
				<th>ACTIONS</th>
			</tr>
		</thead>			
		<tbody>
<?PHP
    if ( $results->num_rows > 0 ) 
    {
    	$sp=0;
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$sp++;
			$rowid = $ITEMSROW['receiving_detail_id'];
			$po_no = $ITEMSROW['po_no'];
?>		
			<tr>
				<td><?php echo $function->GetSupplierName($ITEMSROW['supplier_id'],$db) ?></td>				
				<td><?php echo $ITEMSROW['item_description']?></td>
				<td style="text-align:center"><?php echo $ITEMSROW['po_no']?></td>
				<td style="text-align:center"><?php echo $ITEMSROW['si_no']?></td>
				<td style="text-align:right;padding-right:10px !important;"><?php echo $ITEMSROW['quantity_received']?></td>
				<td><?php echo $ITEMSROW['received_by']?></td>
				<td style="text-align:center">
				<?php 
					if (!empty($ITEMSROW['received_date'])) {
				        echo date("M. d, Y", strtotime($ITEMSROW['received_date']));
				    }
				?>
				</td>
				<td style="padding:1px !important">
					<button class="btn btn-primary btn-sm" onclick="requestRTVApproval('<?php echo $rowid?>','<?php echo $po_no?>')"><i class="fa-solid fa-paper-plane-top"></i>&nbsp; Request R.T.V.</button>
				</td>
			</tr>
<?php } } else { ?>			
			<tr>
				<td colspan="5">&nbsp;NO RECORDS</td>
			</tr>
<?php } ?>			
		</tbody>
	</table>
<script>
function requestRTVApproval(rowid,po_no)
{
	var supplier = $('#searchrtv').val();
	var mode = 'new';
	rms_reloaderOn('Searching...')
	$('#formodalsmtitle').html("RETURN TO VENDOR REQUEST");
	$.post("./Modules/Warehouse_Management/apps/rtv_approval_form.php", { mode: mode, rowid: rowid, po_no: po_no },
	function(data) {		
		$('#formodalsm_page').html(data);
		$('#formodalsm').show();
		rms_reloaderOff();
		$('#formmodal').hide();
	});
}
</script>
