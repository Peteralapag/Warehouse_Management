<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$mode = $_POST['params'];
if($_POST['params'] == 'edit')
{
	$rowid = $_POST['rowid'];
	$rowid;
	$QUERY = "SELECT * FROM wms_supplier WHERE id='$rowid'";
	$result = mysqli_query($db, $QUERY );    
    if ( $result->num_rows > 0 ) 
    {
		while($ROW = mysqli_fetch_array($result))  
		{
			$rowid = $ROW['id'];
			$name = $ROW['name'];
			$address = $ROW['address'];
			$telephone = $ROW['telephone'];
			$cellphone = $ROW['cellphone'];
			$email = $ROW['email'];
			$date_added = $ROW['date_added'];
			$added_by = $ROW['added_by'];
			$contact_person = $ROW['contact_person'];
			$person_contact = $ROW['person_contact'];
			if($ROW['active'] == 1)
			{
				$checked = 'checked="checked"';
			} 
			if($ROW['active'] == 0)
			{
				$checked = '';
			}
		}
    }
} else {
	
}
if($_POST['params'] == 'add')
{
	$rowid = "";
	$name = "";
	$address = "";
	$telephone = "";
	$cellphone = "";
	$email = "";
	$date_added = "";
	$added_by = "";
	$contact_person = "";
	$person_contact = "";
	$checked = '';
}
?>
<style>
.form-wrapper {width:500px;max-height:500px;overflow-y:auto;}
.table th {font-size:14px !important;}
</style>
<div class="form-wrapper">	
	<table style="width: 100%" class="table">
		<tr>
			<th>Supplier Name</th>
			<td>
				<input type="hidden" value="<?php echo $rowid; ?>">
				<input id="name" type="tex" class="form-control" value="<?php echo $name; ?>">				
			</td>
		</tr>
		<tr>
			<th>Supplier Address</th>
			<td>
				<textarea id="address" class="form-control form-control-sm"><?php echo $address; ?></textarea>
			</td>
		</tr>
		<tr>
			<th>T.I.N.</th>
			<td><input id="tin" type="text" class="form-control" value="<?php echo $telephone; ?>"></td>
		</tr>
		<tr>
			<th>Telephone</th>
			<td><input id="telephone" type="text" class="form-control" value="<?php echo $telephone; ?>"></td>
		</tr>
		<tr>
			<th>Cellphone</th>
			<td><input id="cellphone" type="text" class="form-control" value="<?php echo $cellphone; ?>"></td>
		</tr>
		<tr>
			<th>Email</th>
			<td><input id="email" type="text" class="form-control" value="<?php echo $email; ?>"></td>
		</tr>
		<tr>
			<th>Contact Person</th>
			<td><input id="contact_person" type="text" class="form-control" value="<?php echo $contact_person; ?>"></td>
		</tr>
		<tr>
			<th>Contact Number</th>
			<td><input id="person_contact" type="text" class="form-control" value="<?php echo $person_contact; ?>"></td>
		</tr>
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
	<button class="btn btn-primary btn-sm" onclick="Check_Access('','p_update',validateForm)">Update Supplier</button>
	<button class="btn btn-warning btn-sm" onclick="Check_Access('<?php echo $rowid; ?>','p_delete',deleteSupplier)">Delete Supplier</button>
	<?php } ?>
	<button class="btn btn-danger btn-sm" onclick="closeModal('formmodal')">Close</button>
</div>
<script>
function deleteSupplier(rowid)
{
	app_confirm("Delete","Are you sure to delete this supplier?","warning","deletesupplier",rowid,"red");
	return false;
}
function deleteSupplierYes(params)
{
	rms_reloaderOn("Deleting...");
	setTimeout(function()
	{
		var limit = $('#limit').val();
		$.post("./Modules/Warehouse_Management/actions/deletesupplier_process.php", { rowid: params,limit: limit },
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
function validateForm()
{
	var mode = '<?php echo $mode; ?>';
	var limit = $('#limit').val(); 
	var rowid = '<?php echo $rowid; ?>';
	var name = $('#name').val();
	var address = $('#address').val();
	var tin = $('#tin').val();
	var telephone = $('#telephone').val();
	var cellphone = $('#cellphone').val();
	var email = $('#email').val();
	var contact_person = $('#contact_person').val();
	var person_contact = $('#person_contact').val();
	if(name === '')
	{
		app_alert("Supplier Name","Please enter the Supplier Name","warning","Ok","name","focus");
		return false;
	}
	if(address === '')
	{
		app_alert("Supplier Address","Please enter the Supplier Address","warning","Ok","address","focus");
		return false;
	}
	if(contact_person === '')
	{
		app_alert("Contact Person","Please enter the Contact Person name","warning","Ok","contact_person","focus");
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
		rms_reloaderOn("Saving supplier...");
	} 
	if(mode == 'edit')
	{
		rms_reloaderOn("Updating supplier...");
	} 
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/supplier_process.php", { mode: mode, active: active, rowid: rowid, name: name, address: address, tin: tin, telephone: telephone, cellphone: cellphone, email: email, contact_person: contact_person, person_contact: person_contact, limit: limit },
		function(data) {		
			$('.results').html(data);
			rms_reloaderOff();
		});
	},1000);
}
</script>

