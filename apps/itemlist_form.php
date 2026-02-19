<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php") ;
$mode = $_POST['params'];
$function = new WMSFunctions;
if($_POST['params'] == 'edit')
{
	$rowid = $_POST['rowid'];
	$QUERY = "SELECT * FROM wms_itemlist WHERE id='$rowid'";
	$result = mysqli_query($db, $QUERY );    
    if ( $result->num_rows > 0 ) 
    {
		while($ROW = mysqli_fetch_array($result))  
		{
			$rowid = $ROW['id'];
			$supplier_id = $ROW['supplier_id'];
			$item_code = $ROW['item_code'];
			$qr_code = $ROW['qr_code'];
			$category = $ROW['category'];
			$recipient = $ROW['recipient'];
			$item_location = $ROW['item_location'];
			$class = $ROW['class'];
			$item_description = $ROW['item_description'];
			$unit_price = $ROW['unit_price'];
			$uom = $ROW['uom'];
			$conversion = $ROW['conversion'];
			$added_by = $ROW['added_by'];
			$date_added = $ROW['date_added'];
			$active = $ROW['active'];
			
			if($ROW['active'] == 1)
			{
				$checked = 'checked="checked"';
			} 
			if($ROW['active'] == 0)
			{
				$checked = '';
			}
			if($ROW['date_added'] != '')
			{
				$date_added = date("F m, Y @h:i A");
			} else {
				$date_added = "--|--";
			}
			
			$item_class_ph = 'Ex: WIP';
		}
    }
}
if($_POST['params'] == 'add')
{
	$rowid = "";
	$supplier_id = "";
	$item_code = "";
	$qr_code =  "";
	$category =  "";
	$recipient =  "";
	$item_location = "";
	$class =  "";
	$item_description =  "";
	$uom =  "";
	$conversion =  "";
	$added_by =  "";
	$date_added =  "";
	$active =  "";
	$checked = "";
	$unit_price = "0.00";
	$item_class_ph = 'Ex: WIP';
}
?>
<style>
.form-wrapper {width:500px;max-height:500px;overflow-y:auto;}
.table th {font-size:14px !important;}
</style>
<div class="form-wrapper">	
	<table style="width: 100%" class="table">
		<tr>
			<th>Supplier</th>
			<td>
				<input type="hidden" value="<?php echo $rowid; ?>">
				<input id="supplier" type="text" list="supplierss" class="form-control">
				<datalist id="supplierss">
					<?php echo $function->GetSupplier($supplier_id,$db)?>
				</datalist>
			</td>		
		</tr>
		<tr>
			<th>Recipient</th>
			<td>
				<select id="recipient" class="form-control">
					<?php echo $function->GetRecipient($recipient,$db); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Loc.</th>
			<td>
				<select id="item_location" class="form-control">
					<?php echo $function->GetLocation($item_location,$db); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Code</th>
			<td>
				<input id="item_code" type="tex" class="form-control" value="<?php echo $item_code; ?>">				
			</td>
		</tr>
		<tr>
			<th>QR Code</th>
			<td>
				<input id="item_code" type="tex" class="form-control" value="<?php echo $qr_code; ?>" placeholder="Leave for now" disabled>
			</td>
		</tr>
		<tr>
			<th>Category</th>
			<td>
				<select id="categories" class="form-control">
					<?php echo $function->GetItemCategory($category,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Item Classfication</th>
			<td><input id="classification" type="text" class="form-control" value="<?php echo $class; ?>" placeholder="<?php echo $item_class_ph?>"></td>
		</tr>
		<tr>
			<th>Item Description</th>
			<td><input id="item_description" type="text" class="form-control" value="<?php echo $item_description; ?>"></td>
		</tr>
		<tr>
			<th>Units of Measure</th>
			<td>
				<select id="uom" class="form-control">
					<?php echo $function->GetUOM($uom,$db)?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Conversion</th>
			<td><input id="conversion" type="text" class="form-control" value="<?php echo $conversion; ?>"></td>
		</tr>
		<tr>
			<th>Unit Price</th>
			<td><input id="unit_price" type="number" class="form-control" value="<?php echo $unit_price; ?>" placeholder="<?php echo $unit_price; ?>"></td>
		</tr>
	<?php if($mode == 'edit') { ?>
		<tr>
			<th>Added By</th>
			<td><input id="added_by" type="text" class="form-control" value="<?php echo $added_by; ?>" disabled></td>
		</tr>
		<tr>
			<th>Date Added</th>
			<td><input id="date_added" type="text" class="form-control" value="<?php echo $date_added; ?>" disabled></td>
		</tr>
	<?php } ?>
		<tr>
			<th>Active</th>
			<td>
				<label class="switch">
					<input id="active" type="checkbox" <?php echo $checked; ?>>
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
	</table>
</div>
<div class="results" style="font-size:12px;"></div>
<div style="margin-top:10px;text-align:right">
	<?php if($mode == 'add') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Save Supplier</button>
	<?php } if($mode == 'edit') { ?>
	<button class="btn btn-primary btn-sm" onclick="validateForm()">Update Itemlist</button>
	<button class="btn btn-warning btn-sm" onclick="deleteItem('<?php echo $rowid; ?>')">Delete Item</button>
	<?php } ?>
	<button class="btn btn-danger btn-sm" onclick="closeModal('formmodal')">Close</button>
</div>
<script>
function deleteItem(rowid)
{
	app_confirm("Delete","Are you sure to delete this Item?","warning","deleteitem",rowid,"red");
	return false;
}
function deleteItemYes(params)
{
	rms_reloaderOn("Deleting...");
	setTimeout(function()
	{
		var limit = $('#limit').val();
		$.post("./Modules/Warehouse_Management/actions/deleteitem_process.php", { rowid: params,limit: limit },
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function validateForm()
{
	var mode = '<?php echo $mode; ?>';
	var rowid = '<?php echo $rowid; ?>';
	var limit = $('#limit').val(); 
	var supplier = $('#supplier').val(); 
	var recipient = $('#recipient').val(); 
	var classification = $('#classification').val(); 
	var item_location = $('#item_location').val();
	var item_code = $('#item_code').val();
	var qr_code = $('#qr_code').val();
	var category = $('#categories').val();
	var item_description = $('#item_description').val();
	var uom = $('#uom').val();
	var conversion = $('#conversion').val();
	var unit_price = $('#unit_price').val();
	var active = $('#active').val();
/*	if(supplier === '')
	{
		app_alert("Supplier","Please select Supplier","warning","Ok","supplier","focus");
		return false;
	} */
	if(recipient === '')
	{
		app_alert("Recipient","Please select Recipient","warning","Ok","recipient","focus");
		return false;
	}
	if(item_location === '')
	{
		app_alert("Item Location","Please select Item Location","warning","Ok","item_location","focus");
		return false;
	}

	if(item_code === '')
	{
		app_alert("Item Code","Please enter Item Code","warning","Ok","item_code","focus");
		return false;
	}
	if(category == '')
	{
		app_alert("Category","Please select Categorya","warning","Ok","category","focus");
		return false;
	}
	if(classification == '')
	{
		app_alert("Category","Please Enter item Classification","warning","Ok","classification","focus");
		return false;
	}
	if(item_description === '')
	{
		app_alert("item Description","Please enter the Description","warning","Ok","item_description","focus");
		return false;
	}
	if(uom === '')
	{
		app_alert("Units of Measures","Please select units of Measurements","warning","Ok","uom","focus");
		return false;
	}	
	if(unit_price < 0 || unit_price === '')
	{
		app_alert("Unit Price","Please enter Unit Price","warning","Ok","unit_price","focus");
		return false;
	}	
	if($('#active').is(":checked") == true)
	{
		var active = 1;
	} else {
		var active = 0;
	}
	if(mode == 'add')
	{
		rms_reloaderOn("Saving to Itemlist...");
	} 
	if(mode == 'edit')
	{
		rms_reloaderOn("Updating Itemlist...");
	} 
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/itemlist_process.php",
		{ 
			mode: mode,
			rowid: rowid,
			active: active, 
			recipient: recipient, 
			item_location: item_location,
			item_code: item_code,
			qr_code: qr_code,
			category: category, 
			classification: classification,
			item_description: item_description,
			uom: uom,
			conversion : conversion,
			unit_price: unit_price,
			active : active, 
			limit: limit,
			supplier: supplier
		},
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
</script>

