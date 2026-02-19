<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

if (isset($_POST['limit'])) {
    $show_limit = $_POST['limit'];
    $_SESSION['WMS_SHOW_LIMIT'] = $show_limit;
} else {
    $show_limit = $_SESSION['WMS_SHOW_LIMIT'] ?? '25';
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = $show_limit; // Adjust per your needs
$offset = ($page - 1) * $records_per_page;

$currentMonthDays = date('t');
$date = date("Y-m-d");
$user_level = $_SESSION['wms_userlevel'];

$limitClause = "LIMIT $offset, $records_per_page"; // Adjusted for pagination

$ord = $_POST['ord'];
$_SESSION['WMS_ORD'] = $ord;

$qr = "";
if (isset($_POST['recipient'])) {
    $recipient = $_POST['recipient'];
    if ($ord == 'Process Order') {
        $qr = "AND recipient='$recipient' AND (status='Submitted' OR status='In-Transit')";
    } elseif ($ord == 'Closed Order') {
        $qr = "AND recipient='$recipient' AND status='Closed'";
    }
    $_SESSION['wms_user_recipient'] = $recipient;
} elseif (isset($_SESSION['wms_user_recipient'])) {
    $recipient = $_SESSION['wms_user_recipient'];
    if ($ord == 'Process Order') {
        $qr = "AND recipient='$recipient' AND (status='Submitted' OR status='In-Transit')";
    } elseif ($ord == 'Closed Order') {
        $qr = "AND recipient='$recipient' AND status='Closed'";
    }
}

if (isset($_POST['search']) && $_POST['search'] != '') {
    $search = $_POST['search'];
    $qr .= " AND (control_no LIKE '%$search%' OR branch LIKE '%$search%')";
}

$sqlQuery = "SELECT * FROM wms_order_request 
WHERE checked='Approved' AND approved='Approved' $qr 
ORDER BY trans_date DESC $limitClause";
$results = mysqli_query($db, $sqlQuery);

$total_records_query = "SELECT COUNT(*) as total FROM wms_order_request 
WHERE checked='Approved' AND approved='Approved' $qr";
$total_result = mysqli_query($db, $total_records_query);
$total_records = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_records / $records_per_page);
?>
<style>
.lamesako td {
	padding: 0 6px 0 6px !important;
}
</style>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
		<tr>
			<th style="width:60px;text-align:center">#</th>
			<th style="width:300px">BRANCH</th>
			<th style="width:70px">REQUEST TYPE</th>
			<th style="width:250px">RECIPIENT</th>
			<th style="width:150px">CONTROL No.</th>
			<th>ORDER DATE</th>
			<th>DELIVERY DATE</th>
			<th style="width:100px">PICK LIST</th>
			<th style="width:100px">STATUS</th>
			<th style="width:150px">ACTIONS</th>
		</tr>
	</thead>		
	<tbody>
<?php
	$sqlQuery = "SELECT * FROM wms_order_request WHERE checked='Approved' AND approved='Approved' $qr ORDER BY trans_date DESC $limitClause";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$n=0;
    	while($ROWS = mysqli_fetch_array($results))  
		{
			$n++;
			$control_no = $ROWS['control_no'];
			$o_type = $ROWS['order_type'];
			
			if($ROWS['status'] == 'Closed')
			{
				$btn_text = "View Details";
				$btn_color = "btn-success";
			} else {
				$btn_text = "Process Order";
				$btn_color = "btn-info color-white";
			}
			if($ROWS['delivery_date'] == NULL && $ROWS['delivery_date'] == "")
			{
				$delivery_date = '';
			} else {
				$delivery_date = date("M. d, Y",strtotime($ROWS['delivery_date']));
			}
			if($ROWS['order_type'] == 0)
			{
				$order_type = 'Listed Items';
				$sty_type = '';
			}
			if($ROWS['order_type'] == 1)
			{
				$order_type = 'Unlisted Items';
				$sty_type = 'style="font-weight:600"';
			}
?>			
		<tr>
			<td style="text-align:center"><?php echo $n; ?></td>
			<td><?php echo $ROWS['branch']; ?></td>
			<td <?php echo $sty_type?>><?php echo $order_type; ?></td>
			<td><?php echo $ROWS['recipient']; ?></td>
			<td style="text-align:center"><?php echo $ROWS['control_no']; ?></td>
			<td><?php echo date("M. d, Y",strtotime($ROWS['trans_date'])); ?></td>
			<td><?php echo $delivery_date; ?></td>
		<?php 
			if($function->GetPickList($control_no,$db) == 1)
				{
					$td_style = 'style="background:#dafad1;text-align:center"';
					$td_icon = '<i class="fa-solid fa-circle-check color-green"></i>';
				} else {
					$td_style = '';
					$td_icon = '';
				}
			?>
			<td <?php echo $td_style; ?>><?php echo $td_icon; ?></td>
			<td><?php echo $ROWS['status']; ?></td>
			<td style="padding:3px !important">
				<button class="btn <?php echo $btn_color; ?> btn-sm w-100" onclick="orderProcess('<?php echo $control_no?>','<?php echo $o_type?>')"><?php echo $btn_text; ?></button>				
			</td>
		</tr>
	<?php } } else { ?>	
		<tr>
			<td colspan="10" style="text-align:center"><i class="fa fa-bell"></i> No Orders yet.</td>
		</tr>			
<?php } ?>
	</tbody>
</table>
<!-- ######################################################################################################## -->
<div class="pagination">
        <a href="#" class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" data-page="1">FIRST</a>
        <a href="#" class="pagination-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>" data-page="<?php echo $page - 1; ?>">PREVIOUS</a>
<?php
	$range = 2;
	$start_page = max(1, $page - $range);
	$end_page = min($total_pages, $page + $range);
	
	for ($i = $start_page; $i <= $end_page; $i++)
	{
	    if ($i == $page)
	    {
	        echo '<a href="#" class="pagination-link active" data-page="' . $i . '">' . $i . '</a>';
	    } else {
	        echo '<a href="#" class="pagination-link" data-page="' . $i . '">' . $i . '</a>';
	    }
	}
	if ($end_page < $total_pages)
	{
	    if ($end_page < $total_pages - 1)
	    {
	        echo '<span>...</span>';
	    }
	    echo '<a href="#" class="pagination-link" data-page="' . $total_pages . '">' . $total_pages . '</a>';
	}
?>
        <a href="#" class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" data-page="<?php echo $page + 1; ?>">NEXT</a>
        <a href="#" class="pagination-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" data-page="<?php echo $total_pages; ?>">LAST</a>
    </div>
<!-- ######################################################################################################## -->    
<script>
function CheckAccessAndProcess(permission, access, control_no, order_type)
{
	checkAccess(permission, access).then(hasAccess => {
        if(hasAccess) {
            orderProcess(control_no, order_type);
        } else {
            swal("Access Denied", "You have insufficient access. Please contact System Administrator", "warning");
        }
    }).catch(error => {
        swal("Error", "An error occurred while checking permissions. Please try again.", "error");
    });
}

function orderProcess(control_no, order_type)
{
	if(order_type == 0)
	{
		$.post("./Modules/Warehouse_Management/includes/branch_order_process.php", { control_no: control_no },
		function(data) {		
			$('#smnavdata').html(data);
		});
	}
	if(order_type == 1)
	{
		$('#modaltitle').html('Unlisted Items - [[ ' + control_no + ' ]]');
		
		$.post("./Modules/Warehouse_Management/apps/branch_order_process_unlisted_form.php", { control_no: control_no, order_type: order_type },
		function(data) {		
			$('#formmodal_page').html(data);
			$('#formmodal').show();
		});
	}
}
rms_reloaderOff();
</script>