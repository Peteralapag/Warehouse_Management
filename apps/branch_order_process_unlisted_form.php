<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
define("MODULE_NAME", "/Warehouse_Management");
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/".MODULE_NAME."/class/Class.functions.php");
$function = new WMSFunctions;

$control_no = $_POST['control_no'];
$order_type = 1;

$sqlQuery = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
$results = mysqli_query($db, $sqlQuery);    
if ( $results->num_rows > 0 ) 
{
	$i=0;
	while($ORDERROW = mysqli_fetch_array($results))  
	{
		$branch = $ORDERROW['branch'];
		$control_no = $ORDERROW['control_no'];
		$mrs_no = $ORDERROW['control_no'];
		$recipient = $ORDERROW['recipient'];
		$form_type = $ORDERROW['form_type'];
		$trans_date = $ORDERROW['trans_date'];
		$status = $ORDERROW['status'];
		if($form_type = 'MRS')
		{
			$form_text = 'MRS';
		}
		else if($form_type = 'POF')
		{
			$form_text = 'POF';
		}

	}
} else {
	echo "";
}
$uom = '';
?>
<style>
.lamess td {border:0 !important;padding: 0 !important;vertical-align:middle !important;}
.lamess td input {border:0;padding: 5px 10px 5px 10px}
.lamesako th {padding: 3px !important;background:#f1f1f1;text-align:center;}
.lamesako th, td {font-size: 14px}
.inputdata td {padding:0 !important;border-top:3px solid #232323;border-bottom:3px solid #232323}
.inputdata input,.inputdata select {border:0;background:#effde6}
.datarows td {vertical-align:middle;padding:0 !important;}
.datarows input, .datarows select {border: 0 !important;width: 100% !important;}
.datavalues:disabled{background-color: #e9ecef;cursor: not-allowed;}
.padinginfo {display: none;padding: 5px;background-color: #f8d7da;color: #721c24;border: 1px solid red;margin-top: 20px;text-align: center;border-radius: 10px}
</style>
<div style="width:100%;border:1px solid #aeaeae;padding:5px;font-size:14px;">
	<table style="width: 100%;white-space:nowrap">
		<tr>
			<td style="width:100px;font-size:13px">Section/Branch:</td>
			<td style="width:0px;">&nbsp;</td>
			<td style="border-bottom:1px solid #232323;width:300px;font-size:13px"><?php echo $branch?></td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:10px;font-size:13px;white-space:nowrap">Control No.:</td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:150px;font-size:13px;border-bottom:1px solid #232323;color:red;text-align:center"><?php echo $control_no?></td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:10px;font-size:13px">Date:</td>
			<td style="width:10px;">&nbsp;</td>
			<td style="width:200px;font-size:13px;border-bottom:1px solid #232323;text-align:center"><?php echo $trans_date?></td>
		</tr>
	</table>
	<table style="width: 100%;margin-top:20px" class="lamess">
		<tr>
			<th>MRS No.:</th>
			<td><input type="text" value="<?php echo $control_no?>" disabled></td>
			<td style="width:100px">&nbsp;</td>
			<th>RECIPIENT:</th>
			<td><input type="text" value="<?php echo $recipient?>" disabled></td>
		</tr>
	</table>
</div>
<div class="padinginfo"></div>
<div style="width:100%;border:1px solid #aeaeae;padding:5px;font-size:14px;margin-top:20px;margin-bottom:10px">
	<table style="width: 100%" class="table-bordered lamesako">
		<tr>
			<th style="width:40px !important;text-align:center">#</th>
			<th style="width:100px; white-space:nowrap">Item Code</th>
			<th>Description</th>
			<th style="width:100px">Unit Price</th>
			<th style="width:100px">UOM</th>
			<th style="width:100px">Quantity</th>
		</tr>
<?php
	$sqlQueryData = "SELECT * FROM wms_branch_order_unlisted WHERE control_no='$mrs_no'";
	$dataResults = mysqli_query($db, $sqlQueryData);    
	$iCount = $dataResults->num_rows;
	if ( $dataResults->num_rows > 0 ) 
	{
		$submit_text = "";
		$x=0;$item_cnt=0;
		while($DATAROW = mysqli_fetch_array($dataResults))  
		{
			$x++;
			if($DATAROW['item_code'] != '' && $DATAROW['item_code'] != NULL)
			{
				$item_cnt++;
			}
			$rowid = $DATAROW['id'];
			$uom = $DATAROW['uom'];
			$item_code = $DATAROW['item_code'];
			$unit_price = $DATAROW['unit_price'];

?>		
		<tr class="datarows" id="datarows<?php echo $x?>">
			<td style="text-align:center"><?php echo $x?></td>			
			<td style="padding:0 !important;white-space:nowrap">
				<input id="item_code<?php echo $x?>" style="text-align:center" type="text" class="form-control form-control-sm" value="<?php echo $item_code?>" disabled>
			</td>
			<td>
				<input id="item_description<?php echo $x?>" list="items" name="items" class="form-control form-control-sm" value="<?php echo $DATAROW['item_description']?>" onchange="getItemCodeUom(this.value,'<?php echo $x?>','<?php echo $rowid?>')">
				<datalist id="items">
					<?php echo $function->GetUnlistedName($db); ?>
				</datalist>
			</td>
			<td>
				<input id="unit_price<?php echo $x?>" type="text" style="text-align:right" class="form-control form-control-sm datavalues" value="<?php echo $DATAROW['unit_price']?>" disabled>
			</td>
			<td>
				<select id="uom<?php echo $x?>" style="text-align:center" class="form-control form-control-sm datavalues" onchange="exe('<?php echo $rowid?>','<?php echo $x?>')">
					<?php echo $function->GetUOM($uom,$db)?>
				</select>
			</td>
			<td>
				<input id="quantity<?php echo $x?>" type="number" style="text-align:right" class="form-control form-control-sm datavalues" value="<?php echo $DATAROW['quantity']?>"  onchange="exe('<?php echo $rowid?>','<?php echo $x?>')">
			</td>
		</tr>
<?php }  } else { ?>
		<tr>
			<td colspan="5" style="text-align:center">No Items</td>
		</tr>
<?php } 
if($item_cnt == $iCount)
{
	$disabled = '';
} else {
	$disabled = 'disabled';
}
?>		
	</table>
	<div style="margin-top:10px;text-align:right">
		<button <?php echo $disabled; ?> class="btn btn-primary" onclick="executeOrder()">Execute Order</button>
		<button class="btn btn-warning" onclick="closeModal('formmodal')"><i class="fa-solid fa-x"></i>&nbsp;&nbsp;Cancel</button>
	</div>
</div>
<div class="resultas"></div>
<script>
function executeOrder()
{
	var module = '<?php echo MODULE_NAME; ?>';
	
	var mode = 'transferunlistedtolisted';
	var control_no = '<?php echo $control_no?>';
	
	
	
	$.post("./Modules/" + module + "/actions/actions.php", { mode: mode, control_no: control_no },
	function(data) {		
		$('.resultas').html(data);
		rms_reloaderOff();
		closeModal('formmodal');
		$('#' + sessionStorage.navwms).trigger('click');
	});
}
function exe(rowid,elemid)
{
	saveThisUpdate(elemid,rowid);
}
function getItemCodeUom(item_description,elemid,rowid)
{
	var mode ='getitemcodeuom';
	$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, item_description: item_description, elemid: elemid },
	function(data) {		
		$('.resultas').html(data);
		saveThisUpdate(elemid,rowid);		
	});
}
function submitOrder(controlno)
{
	app_confirm("Submit Order","Are you sure to finish and submit your order?","warning","submitOrderYes",controlno,"orange")
}
function submitOrderYes(controlno)
{
	var mode = 'submitorderunlisted';
	rms_reloaderOn("Submitting Order...");
	setTimeout(function()
	{
		$.post("./Modules/<?php echo MODULE_NAME; ?>/actions/actions.php", { mode: mode, control_no: controlno },
		function(data) {		
			$('.resultas').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function saveThisUpdate(elemid,rowid)
{
	var mode = 'updatedunlistedorder';
	var editid = rowid;
	var module = '<?php echo MODULE_NAME; ?>';
	var control_no = '<?php echo $control_no?>';
	
	var item_code = $('#item_code' + elemid).val();;	
	var item_description = $('#item_description' + elemid).val();
    var uom = $('#uom' + elemid).val();
    var quantity = $('#quantity' + elemid).val();
    var unit_price = $('#unit_price' + elemid).val();
    
    if (item_description === '')
	{
        swal("Required", "Please fill in the Item Description.", "warning").then(() => {
            $('#item_description' + elemid).focus();
        });
    } else if (uom === '') {
        swal("Required", "Please fill in the UOM.", "warning").then(() => {
            $('#uom' + elemid).focus();
        });
    } else if (quantity === '') {
        swal("Required", "Please fill in the Quantity.", "warning").then(() => {
            $('#quantity' + elemid).focus();
        });
    } 
	else
	{	
		$.post("./Modules/" + module + "/actions/unlisted_process.php", {
			mode: mode,
			editid: editid,
			item_description: item_description,
			item_code: item_code,
			uom: uom,
			quantity: quantity,
			unit_price: unit_price
		},
		function(data) {		
			$('.resultas').html(data);
			$('.padinginfo').fadeIn('slow', function() {
                setTimeout(function() {
                    $('.padinginfo').fadeOut('slow');
                }, 1100);
            });
		});	
	}
}
$(function()
{
	var status = '<?php echo $status?>';
	if(status == 'Approval')
	{
		$('.padinginfo').html("Request is in Approval Status");
		$('.padinginfo').show();
	}
	$('#item_description, #uom, #quantity').on('keypress', function(event)
	{
		if (event.which === 13)
		{ 
		    event.preventDefault();
		    saveThisData();
		}
	});
});
</script>
