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
	'returned'  => 'status-pill status-danger',
    'pending'  => 'status-pill status-warning',
    'approved' => 'status-pill status-success',
    'rejected' => 'status-pill status-danger',
   	'for_canvassing' => 'status-pill status-info',
   	'canvassing_reviewed'  => 'status-pill status-info',
   	'canvassing_approved'  => 'status-pill status-success',
   	'for_canvassing_rejected' => 'status-pill status-danger',
    'partial_conversion'  => 'status-pill status-warning',
    'converted'  => 'status-pill status-success',
    'convert_rejected' => 'status-pill status-danger',
];

?>

<style>
.pr-table {
	margin-bottom:0;
	font-size:13px;
	min-width:1100px;
}
.pr-table thead th {
	position:sticky;
	top:0;
	z-index:2;
	background:#f8fafc;
	color:#334155;
	text-transform:uppercase;
	letter-spacing:.02em;
	font-size:11px;
	font-weight:700;
	padding:10px 8px !important;
	border-bottom:1px solid #e2e8f0 !important;
	white-space:nowrap;
}
.pr-table tbody td {
	padding:8px 8px !important;
	vertical-align:middle;
	color:#0f172a;
	border-color:#eef2f6;
	background:#fff;
}
.pr-table tbody tr:nth-child(even) td {
	background:#fcfdff;
}
.pr-table tbody tr:hover td {
	background:#f4f8ff;
}
.pr-number {
	font-weight:700;
	color:#0f172a;
}
.aging-cell {
	font-weight:600;
	text-align:center;
}
.remarks-cell {
	max-width:220px;
	overflow:hidden;
	text-overflow:ellipsis;
	white-space:nowrap;
}
.status-pill {
	display:inline-block;
	padding:2px 10px;
	border-radius:999px;
	font-size:11px;
	font-weight:700;
	line-height:1.4;
	text-transform:capitalize;
	white-space:nowrap;
}
.status-success {background:#e8f8ef;color:#107a3f;border:1px solid #c4ebd5;}
.status-danger {background:#fff0f0;color:#a11b1b;border:1px solid #ffd4d4;}
.status-warning {background:#fff7e6;color:#8a5a00;border:1px solid #ffe1a8;}
.status-info {background:#e9f4ff;color:#0f4d85;border:1px solid #cbe4ff;}
.btn-view-pr {
	padding:3px 9px;
	font-size:12px;
	font-weight:600;
	border-radius:6px;
	border:1px solid #bfd7ff;
	background:#edf4ff;
	color:#0f4d85;
}
.btn-view-pr:hover {background:#dceaff;}
.empty-cell {
	text-align:center;
	color:#64748b;
	font-size:13px;
	padding:22px 8px !important;
}
</style>

<table class="table table-bordered pr-table" id="purchaserequesttable">
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
					
					$aging_display = '-';
					$aging_color = '#64748b';
					
					// Aging rule: start from PR approved_at.
					// End at first PO approved_date (approved PO). If no approved PO yet, run until today.
					if (!empty($req['approved_at'])) {
						$approved_date = new DateTime($req['approved_at']);
						$end_date = new DateTime();
						$start_date = clone $approved_date;
						$has_po_approved = false;

						$poStmt = $db->prepare(" 
							SELECT 
								MIN(approved_date) AS first_po_approved_date,
								COUNT(*) AS po_approved_count
							FROM purchase_orders 
							WHERE pr_number = ?
							AND approved_date IS NOT NULL
						");
						$poStmt->bind_param("s", $prnumber);
						$poStmt->execute();
						$poStmt->bind_result($first_po_approved_date, $po_approved_count);
						$poStmt->fetch();
						$poStmt->close();

						if ((int)$po_approved_count > 0 && !empty($first_po_approved_date)) {
							$has_po_approved = true;
							$end_date = new DateTime($first_po_approved_date);
						}

						// Handle data anomalies where PO timestamp is earlier than approved_at.
						if ($has_po_approved && $end_date < $start_date && !empty($req['request_date'])) {
							$start_date = new DateTime($req['request_date']);
						}

						$seconds_diff = $end_date->getTimestamp() - $start_date->getTimestamp();
						if ($seconds_diff < 0) {
							$seconds_diff = 0;
						}
						$aging_days = (int)floor($seconds_diff / 86400);

						// Once PO is approved, keep at least 1 day to avoid visual reset to 0 on converted PR.
						if ($has_po_approved && $aging_days === 0) {
							$aging_days = 1;
						}

						$aging_display = $aging_days . ' day(s)';

						if ($has_po_approved) {
							$aging_color = '#0f4d85';
						} elseif ($aging_days > 7) {
							$aging_color = 'red';
						} elseif ($aging_days > 3) {
							$aging_color = 'orange';
						} else {
							$aging_color = 'green';
						}
					}

					
					
					

            ?>
                <tr>
                    <td><?= htmlspecialchars($i) ?></td>
					<td class="pr-number"><?= htmlspecialchars($prnumber) ?></td>
                    <td><?= htmlspecialchars($req['requested_by']) ?></td>
                    <td><?= htmlspecialchars($req['source']) ?></td>
                    <td><?= htmlspecialchars($destination) ?></td>
                    <td><?= date('Y-m-d', strtotime($req['request_date'])) ?></td>
                    
					<td class="aging-cell" style="color:<?= $aging_color ?>">
					    <?= $aging_display ?>
					</td>           
					         
					<td class="remarks-cell" title="<?= htmlspecialchars($remarks, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(strlen($remarks) > $max ? substr($remarks, 0, $max) . '...' : $remarks)?></td>
                    <td>
						<span class="<?= isset($status_badge[$status]) ? $status_badge[$status] : 'status-pill status-info' ?>"><?= ucwords(str_replace('_', ' ', $status)) ?></span>
                    </td>

					<td style="text-align:center"><button type="button" onclick="vieviapr('<?= $prnumber?>','<?= $status?>','<?= $destination?>')" class="btn-view-pr"><i class="fa-solid fa-eye"></i> View</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
			<tr><td colspan="10" class="empty-cell">No purchase requests found.</td></tr>
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
