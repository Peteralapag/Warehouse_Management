<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$recipient = $_POST['recipient'];
$permit = $function->checkPolicy($_SESSION['wms_username'],'Branch Order Receiving','p_approver',$_SESSION['wms_userlevel'],$db);
?>
<style>
.reopen-wrapper {
	padding-bottom:10px;
}
.reopen-wrapper  th {
	width:80%;
	white-space:nowrap;
}
.reopen-wrapper  td {
	white-space:nowrap;
	padding:3px 7px 3px 7px;
}
.reopen-wrapper td button {
	padding:3px 5px 3px 5px !important;
}
.reopen-wrapper { background:#fff;}
.reopen-wrapper { overflow: auto; max-height: calc(100vh - 222px); width:100% }
.reopen-wrapper thead th { position: sticky; top: 0; z-index: 1; background:#0cccae; color:#fff }
.reopen-wrapper table  { border-collapse: collapse;}
.reopen-wrapper th, .reopen-wrapper td { font-size:14px; white-space:nowrap }
</style>
<div class="reopen-wrapper">
	
	<table style="width: 100%" class="table table-bordered table-striped table-hover">
		<thead>
			<tr>
				<th style="width:80px !important;text-align:center">#</th>
				<th>RECIPIENT</th>
				<th>CONTROL No.</th>
				<th>REQUESTED BY</th>
				<th>REQUESTED DATE</th>
				<th>GRANTED BY</th>
				<th>GRANDTED DATE</th>
				<th>STATUS</th>
				<th>ACTION</th>
			</tr>
		</thead>
		<tbody>
<?php
	$sqlQuery = "SELECT * FROM wms_request WHERE recipient='$recipient' ORDER BY flag ASC";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$n=0;
    	while($ROWS = mysqli_fetch_array($results))  
		{
			$n++;
			if ($ROWS['request_date'] != NULL && $ROWS['request_date'] != '0000-00-00 00:00:00')
			{
			    $requested_date = date("M. d, Y @ h:i a", strtotime($ROWS['request_date']));
			} else {
			    $requested_date = '';
			}
			
			if ($ROWS['granted_date'] != NULL && $ROWS['granted_date'] != '0000-00-00 00:00:00')
			{
			    $granted_date = date("M. d, Y @ h:i a", strtotime($ROWS['granted_date']));
			} else {
			    $granted_date = '';
			}			
			if($ROWS['flag'] == 0)
			{
				$status = "<strong>Pending</strong>";
				$btn_enabled = '';
			} 
			elseif($ROWS['flag'] == 1)
			{
				$status = "Granted";
				$btn_enabled = 'disabled style="background:#fbcdcd;color:grey"';
			}
?>		
			<tr>
				<td style="text-align:center;width:80px !important"><?php echo $n; ?></td>
				<td><?php echo $ROWS['recipient'];?></td>
				<td><?php echo $ROWS['control_no'];?></td>
				<td><?php echo $ROWS['requested_by'];?></td>
				<td><?php echo $requested_date;?></td>
				<td><?php echo $ROWS['granted_by'];?></td>
				<td><?php echo $granted_date;?></td>
				<td style="text-align:center"><?php echo $status; ?></td>
				<td style="padding:0px !important">
					<button <?php echo $btn_enabled; ?> class="btn btn-success btn-sm w-100" onclick="grandRequest('<?php echo $ROWS['id']; ?>','<?php echo $ROWS['control_no']; ?>')">Grand</button>
				</td>
			</tr>
<?php	} } else { ?>		
			<tr>
				<td colspan="9" style="text-align:center"><i class="fa fa-bell"></i>&nbsp;&nbsp;No Pending Request</td>
			</tr>
<?php } ?>		
		</tbody>			
	</table>
</div>
<div id="requestresults">
<script>
function grandRequest(rowid,control_no)
{
	var permit = '<?php echo $permit; ?>';
	if(permit == 0)
	{
		swal("Access Denied","You have insufficient access. Please contact System Administrator","warning");
		return false;
	} 
	var mode = 'grandwmsrequest';
	
	rms_reloaderOn("Granting Request...");
	setTimeout(function()
	{
		$.post("./Modules/Warehouse_Management/actions/actions.php", { mode: mode, rowid: rowid, control_no: control_no },
		function(data) {		
			$('#requestresults').html(data);
			checkWMSRequest();
			orderProcess(control_no);
			rms_reloaderOff();
		});
	},500);
}
</script>