<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$control_no = $_POST['control_no'];
$branch = '';
?>
<style>
.order-wrapper {padding:10px;width:10.5in;}
.lamesako {border-collapse:collapse;}
.lamesako thead th {background: #f1f1f1;padding: 5px;font-weight: 600;color: #636363;border:1px solid #aeaeae;font-size: 12px;}
.lamesako tbody td {position:relative;border:1px solid #aeaeae;font-size:12px;}
.lamesako tbody td input {width: 100%; box-sizing: border-box;-webkit-box-sizing:border-box;-moz-box-sizing: border-box;border:0;background:#fbf3dc;}
.remarks-footer td {color: #636363;}
.remarks-footer textarea {width: 100%; box-sizing: border-box;-webkit-box-sizing:border-box;-moz-box-sizing: border-box;border:0;background:#fdfcf3;resize: none}
.closedform {position:absolute; display: flex;height:100%;top:0;width:100%;justify-content: center;text-align:center;align-items: center;}
</style>
<div class="order-wrapper" id="orders" style="position:relative">
	<table style="width: 100%" class="lamesako">
		<thead>
			<tr>
				<th style="width:40px;text-align:center">#</th>
				<th style="width:100px">ITEM CODE</th>
				<th>ITEM DESCRIPTION</th>
				<th style="width:100px">STOCK</th>
				<th style="width:100px">UNITS (UOM)</th>
				<th style="width:100px">REQUESTED QTY.</th>
				<th style="width:100px">ACTUAL QTY.</th>
				<th style="width:70px">ACTIONS</th>
			</tr>
		</thead>
		<tbody>
<?php
	$sqlQuery = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
	$results = mysqli_query($db, $sqlQuery);
	$x=0;
	if ( $results->num_rows > 0 ) 
    {
	while($ROWS = mysqli_fetch_array($results))  
	{ 
		$x++;
		$rowid = $ROWS['id'];
		$branch = $ROWS['branch'];
		$quantity = $ROWS['quantity'];

		$item_code = $ROWS['item_code'];
		
		$unit_price = $function->GetUnitPrice($item_code,$db);
		
		if($ROWS['actual_quantity'] == 0.00 AND $ROWS['actual_quantity'] == '')
		{
			$actual_quantity = '';			
		} else {
			$actual_quantity = $ROWS['actual_quantity'];	
		}		
		if($function->GetItemStock($item_code,$db) <= 0 || $function->GetItemStock($item_code,$db) <= 0.00)
		{
			$stock_text = 'disabled';
			$actual_quantity = 0;
			$actual_quantity = '';
			$type = 'text';
		} else {
			$stock_text = '';
			$type = 'number';
		}
?>		
			<tr>
				<td style="text-align:center;background:#f1f1f1"><?php echo $x; ?></td>
				<td style="text-align:center"><?php echo $ROWS['item_code']; ?></td>
				<td style="padding-left:5px;padding-right:5px;"><?php echo $ROWS['item_description']; ?></td>
				<td>
					<input id="onhand<?php echo $x; ?>" type="text" class="form-control form-control-sm" style="text-align:right" value="<?php echo $function->GetItemStock($item_code,$db); ?>" readonly>
				</td>
				<td style="text-align:center"><?php echo $ROWS['uom']; ?></td>
				<td style="text-align:center">
					<?php echo $quantity; ?>
					<input id="quantity<?php echo $x; ?>" type="hidden" value="<?php echo $quantity; ?>">
				</td>
				<td>
					<input id="aqty<?php echo $x; ?>" <?php echo $stock_text; ?> type="<?php echo $type; ?>" class="form-control form-control-sm quantityInput" style="text-align:right" placeholder="<?php echo $actual_quantity; ?>" value="<?php echo $actual_quantity; ?>">
				</td>
				<td><button id="aqtybtn<?php echo $x; ?>" class="btn btn-success btn-sm w-100" onclick="changeAQ('<?php echo $x; ?>','<?php echo $rowid; ?>','<?php echo $quantity; ?>','<?php echo $item_code; ?>','<?php echo $unit_price; ?>')">Save</button></td>
			</tr>
<script>
$(document).ready(function()
{
	$('#aqty<?php echo $x; ?>').keydown(function(event)
	{
	    if (event.keyCode === 13) {
	        $('#aqtybtn<?php echo $x; ?>').click();
	    }
	});
});
</script>
			
<?php } ?>
			<tr class="remarks-footer">
				<td colspan="3" style="padding:5px;white-space:normal !important" valign="top"><strong>ORDER REMARKS:</strong> <?php echo $function->getOrderRemarks($control_no,'remarks',$db); ?></td>
				<td colspan="5"><textarea type="text" class="form-control form-control-sm" placeholder="Preparator Remarks" onkeyUp="prepRemarks(this.value,'<?php echo $control_no; ?>')"><?php echo $function->getOrderRemarks($control_no,'preparator_remarks',$db); ?></textarea></td>
			</tr>
<?php } else { $basket = 0; ?>    			
			<tr>
				<td colspan="7" style="padding:8px;text-align:center;font-weight:600" class="bg-warning color-white">THE ORDER BASKET FROM THIS BRANCH IS EMPTY.</td>
			</tr>
<?php } ?>
		</tbody>
	</table>
<div class="closedform">
	<img src="../images/closed-stamp.png"></div>
</div>
<div id="results"></div>
<script>
function createDR(controlno)
{
	
	var mode = 'forwardtologistics';
	var module = sessionStorage.module_name;	
	var isValid = true;
	var firstEmptyInput = null;
	 $('.quantityInput').each(function() {
        if ($(this).val() === '') {
            isValid = false;
            if (firstEmptyInput === null) {
                firstEmptyInput = $(this);
            }
        }
    });	
/*  if (!isValid)
    {
        if (firstEmptyInput !== null)
        {
           firstEmptyInput.focus();
        }
      	return false;
    } */
	$('#finbtn').prop('disabled', true);
	rms_reloaderOn("Generating Delivery Receipt...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, module: module },
		function(data) {		
			$('#results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
$(function()
{
	if('<?php echo $function->GetOrderStatus($control_no,'logistics',$db) ?>' == 1)
	{
		$('.closedform').show();
		$("#orders input,textarea").each(function() {
			$(this).prop("disabled", true);
		});
	} else {
		$('.closedform').hide();
	}
});
function prepRemarks(value,controlno)
{
	var mode = 'preparatorremarks';
	$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, control_no: controlno, remarks: value },
	function(data) {		
		$('#results').html(data);
	});
}
function changeAQ(elemid,rowid,quantity,item_code,unit_price)
{
	var onhand = parseFloat($('#onhand' + elemid).val()) || 0;
	var value = parseFloat($('#aqty' + elemid).val()) || 0;
	
	if (value > quantity) {
		swal("Warning", "Actual Quantity cannot be higher than actual order quantity", "warning");
		return false;
	} 
	
	
	if(value > onhand)
	{
		swal("Warning", "Actual Quantity cannot be higher than on hand stock", "warning");
		return false;
	}
	var mode = 'changeactualcount';
	var control_no = '<?php echo $control_no; ?>';	
	var actual_quantity = value;
	$('#aqtybtn' + elemid).prop('disabled', true);
	$('#aqtybtn' + elemid).html('<i class="fa fa-spinner fa-spin"></i>');

	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid, actual_quantity: actual_quantity, control_no: control_no, item_code: item_code, unit_price: unit_price },
		function(data) {		
			$('#results').html(data);
			$('#aqtybtn' + elemid).prop('disabled', false);
			$('#aqtybtn' + elemid).html('Save');
		});
	},500);
}
</script>