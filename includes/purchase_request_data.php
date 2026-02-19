<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);

// Fetch warehouse purchase requests
$sql = "SELECT * FROM purchase_request WHERE source='WAREHOUSE'";
$params = [];
$types  = "";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

// ORDER + LIMIT
$sql .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";


// =======================
// EXECUTE
// =======================
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();


$i = 0;

$status_badge = [
	'returned'  => 'badge bg-danger',
    'pending'  => 'badge bg-danger', //sa branch ni nag request
    'approved'     => 'badge bg-success',	// sa supervisor sa branch dapat naka approved
    'rejected' => 'badge bg-success',	// kung e reject sa branch pa lang
   	'for_canvassing' => 'badge bg-success',	// kung ge push na sa canvassing ni purchasing
   	'canvassing_reviewed'  => 'badge bg-success',
   	'canvassing_approved'  => 'badge bg-success', // kung ge approved ang canvassing
   	'for_canvassing_rejected' => 'badge bg-success',	// kung ge reject ang canvassing
    'partial_conversion'  => 'badge bg-success', // kung ge convert na sa PO
    'converted'  => 'badge bg-success', // kung ge convert na sa PO
    'convert_rejected' => 'badge bg-success',
];

?>

<table class="table table-bordered table-striped" id="purchaserequesttable">
    <thead>
        <tr>
            <th>#</th>
            <th>PR Number</th>
            <th>Requested By</th>
            <th>Branch</th>
            <th>Destination</th>
            <th>Request Date</th>
            <th>Aging</th>
            <th>Remarks</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($requests)): ?>
            <?php foreach($requests as $req): $i++;
            	
            		$prnumber = $req['pr_number'];
            		$status = $req['status'];
            		$remarks = $req['remarks'];
            		$destination = $req['destination_branch'];
            		$max = 15;
            		          		
					$statusbtn = 'primary';
					
					
					
					$today = new DateTime();

					$aging_allowed_status = [
					    'approved',
					    'for_canvassing',
					    'canvassing_reviewed',
					    'canvassing_approved',
					    'partial_conversion',
					    'partial_received'
					];
					
					$aging_display = '-';
					$aging_color = '#000';
					
					// check allowed status PER ROW
					if (in_array($status, $aging_allowed_status)) {
					
					    $closed_po = 0;
					
					    $poStmt = $db->prepare("
					        SELECT closed_po 
					        FROM purchase_orders 
					        WHERE pr_number = ? 
					        LIMIT 1
					    ");
					    $poStmt->bind_param("s", $prnumber);
					    $poStmt->execute();
					    $poStmt->bind_result($closed_po);
					    $poStmt->fetch();
					    $poStmt->close();
					
					    // walay PO or open pa
					    if ($closed_po == 0 && !empty($req['approved_at'])) {
					
					        $approved_date = new DateTime($req['approved_at']);
					        $aging_days = $approved_date->diff($today)->days;
					
					        $aging_display = $aging_days . ' day(s)';
					
					        if ($aging_days > 7) {
					            $aging_color = 'red';
					        } elseif ($aging_days > 3) {
					            $aging_color = 'orange';
					        } else {
					            $aging_color = 'green';
					        }
					    }
					}

					
					
					

            ?>
                <tr>
                    <td><?= htmlspecialchars($i) ?></td>
                    <td><?= htmlspecialchars($prnumber) ?></td>
                    <td><?= htmlspecialchars($req['requested_by']) ?></td>
                    <td><?= htmlspecialchars($req['source']) ?></td>
                    <td><?= htmlspecialchars($destination) ?></td>
                    <td><?= date('Y-m-d', strtotime($req['request_date'])) ?></td>
                    
					<td style="text-align:center;color:<?= $aging_color ?>">
					    <?= $aging_display ?>
					</td>           
					         
                    <td title="<?= $remarks?>"><?= htmlspecialchars(strlen($remarks) > $max ? substr($remarks, 0, $max) . '...' : $remarks)?></td>
                    <td>
                    	<span class="<?= $status_badge[$status] ?>"><?= $status ?></span>
                    </td>

                    <td style="text-align:center"><button type="button" onclick="vieviapr('<?= $prnumber?>','<?= $status?>','<?= $destination?>')" class="btn btn-<?= $statusbtn?> btn-sm"><i class="fa-solid fa-eye"></i> View</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No purchase requests found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>

function vieviapr(prnumber,status,destination) {

	
	$.post("./Modules/Warehouse_Management/includes/purchase_request_view.php", { prnumber: prnumber, status: status, destination: destination },
	function(data) {
		$('#contents').html(data);
	});

}

</script>
