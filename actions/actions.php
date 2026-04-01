<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

if(isset($_POST['mode']))
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
if(isset($_SESSION['wms_appnameuser']))
{
	$app_user = strtolower($_SESSION['wms_appnameuser']);
	$app_user = ucwords($app_user);
}
$date = date("Y-m-d");
$date_time = date("Y-m-d H:i:s");



if ($mode === 'approvepurchaserequest') {

    $prnumber = trim($_POST['prnumber'] ?? '');
    $approver = trim($_SESSION['wms_appnameuser'] ?? '');

    if ($prnumber === '') {
        echo json_encode(['success'=>false,'message'=>'PR number is required']);
        exit;
    }

    if ($approver === '') {
        echo json_encode(['success'=>false,'message'=>'Approver not found']);
        exit;
    }

    // 1ï¸CHECK PR
    $stmt = $db->prepare("
        SELECT id, status 
        FROM purchase_request 
        WHERE pr_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'PR not found']);
        exit;
    }

    $pr = $res->fetch_assoc();
    $pr_id = $pr['id'];

    
    if ($pr['status'] !== 'pending' && $pr['status'] !== 'returned') {
	    echo json_encode([
	        'success' => false,
	        'message' => 'Only PENDING or RETURNED PR can be approved'
	    ]);
	    exit;
	}

    

    // 2ï¸UPDATE PR (NOTE: approved_at)
    $stmt = $db->prepare("
        UPDATE purchase_request
        SET status = 'approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $approver, $pr_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Nothing updated']);
        exit;
    }

    // 3ï¸INSERT LOG
    // action_by is INT â†’ use user ID or 0 if wala pa
    $user_id = $_SESSION['user_id'] ?? 0;

    $stmt = $db->prepare("
        INSERT INTO purchasing_logs
            (reference_type, reference_id, action, action_by, action_date)
        VALUES
            ('PR', ?, 'APPROVED', ?, NOW())
    ");
    $stmt->bind_param("is", $pr_id, $approver);
    $stmt->execute();

    echo json_encode([
        'success'=>true,
        'message'=>'Purchase Request approved successfully'
    ]);
    exit;
}


if ($mode === 'submitprform') {

    $prnumber = $_POST['prnumber'] ?? '';
    $isRevise = !empty($prnumber);

    $items       = $_POST['items'] ?? [];
    $grandTotal  = floatval($_POST['grandTotal'] ?? 0);
    $remarks     = $_POST['remarks'] ?? '';
    $date_time   = date("Y-m-d H:i:s"); 
    $user        = $_SESSION['wms_appnameuser'] ?? '';
    $destination_branch = $_POST['destination_branch'] ?? '';

    if (empty($destination_branch)) {
        echo json_encode(['success'=>false,'message'=>'Destination Branch is required.']);
        exit;
    }

    if (empty($items)) {
        echo json_encode(['success'=>false,'message'=>'No items to submit.']);
        exit;
    }

    if (empty($remarks)) {
        echo json_encode(['success'=>false,'message'=>'No remarks.']);
        exit;
    }

    try {

        $db->begin_transaction();

        /*
        |--------------------------------------------------------------------------
        | ADD MODE (INSERT)  â€“ EXISTING BEHAVIOR
        |--------------------------------------------------------------------------
        */
        if (!$isRevise) {

            $pr_number = $function->generateUniquePrNumber($db);
            $source = 'WAREHOUSE';
            $department = 'SUPPLY CHAIN';

            $stmt = $db->prepare("
                INSERT INTO purchase_request 
                (pr_number, request_date, source, department, destination_branch, requested_by, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssssss",
                $pr_number,
                $date_time,
                $source,
                $department,
                $destination_branch,
                $user,
                $remarks,
                $date_time
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $pr_id = $db->insert_id;
            $stmt->close();
        }

        /*
        |--------------------------------------------------------------------------
        | REVISE MODE (UPDATE)
        |--------------------------------------------------------------------------
        */
        else {

            // get PR id + status
            $stmt = $db->prepare("SELECT id, status FROM purchase_request WHERE pr_number=?");
            $stmt->bind_param("s", $prnumber);
            $stmt->execute();
            $stmt->bind_result($pr_id, $pr_status);
            $stmt->fetch();
            $stmt->close();

            if (!$pr_id) {
                throw new Exception("PR not found.");
            }

            $allowed_status = ['pending', 'returned'];
			if (!in_array($pr_status, $allowed_status)) {
			    throw new Exception("Only pending or returned PR can be revised.");
			}

            // update header only
            $stmt = $db->prepare("
                UPDATE purchase_request
                SET destination_branch = ?,
                    remarks = ?,
                    updated_at = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssi",
                $destination_branch,
                $remarks,
                $date_time,
                $pr_id
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            $stmt->close();

            // remove old items
            $stmt = $db->prepare("DELETE FROM purchase_request_items WHERE pr_id=?");
            $stmt->bind_param("i", $pr_id);
            $stmt->execute();
            $stmt->close();

            $pr_number = $prnumber; // return same PR number
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT ITEMS (USED BY BOTH ADD & REVISE)
        |--------------------------------------------------------------------------
        */
        $stmt2 = $db->prepare("
            INSERT INTO purchase_request_items
            (pr_id, item_type, item_code, item_description, quantity, unit, estimated_cost, total_estimated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {

            $item_id   = intval($item['item_id']);
            $item_type = $item['item_type'];
            $qty       = floatval($item['qty']);
            $cost      = floatval($item['cost']);
            $total     = floatval($item['total']);

            // trusted item data
            $res = $db->prepare("
                SELECT item_code, item_description, uom
                FROM wms_itemlist
                WHERE id = ?
                LIMIT 1
            ");
            $res->bind_param("i", $item_id);
            $res->execute();
            $res->bind_result($item_code, $item_description, $unit);

            if (!$res->fetch()) {
                throw new Exception("Item ID {$item_id} not found.");
            }
            $res->close();

            $stmt2->bind_param(
                "isssdsdd",
                $pr_id,
                $item_type,
                $item_code,
                $item_description,
                $qty,
                $unit,
                $cost,
                $total
            );

            if (!$stmt2->execute()) {
                throw new Exception($stmt2->error);
            }
        }

        $stmt2->close();

        $db->commit();

        echo json_encode([
            'success'   => true,
            'pr_number' => $pr_number
        ]);

    } catch (Exception $e) {

        $db->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}



if($mode == 'denyrtvrequest')
{
	$rowid = $_POST['rowid'];	
	$queryDataUpdate = "UPDATE wms_return_to_vendor SET `approved`=2,`status`='Rejected' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Success","Request has been denied.", "success");
				requestRTVApproveEdit("'.$rowid.'");
				loadRTVData();
			</script>
		';
	} else {
		return $db->error;
	}
}
if($mode == 'approvertvrequest')
{
	$rowid = $_POST['rowid'];
	$requested_by = $app_user;
	$requested_date = $date_time;
	
	$queryDataUpdate = "UPDATE wms_return_to_vendor SET approved_by='$requested_by',approved_date='$date_time',approved=1,`status`='Approved' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Success","Request has been updated successfully", "success");
				loadRTVData();
				$("#formodalsm").fadeOut();
			</script>
		';
	} else {
		return $db->error;
	}
}
if($mode == 'updatertvrequest')
{
	$rowid = $_POST['rowid'];
	$return_quantity = $_POST['quantity'];
	$requested_by = $app_user;
	$requested_date = $date_time;
	$remarks = $_POST['remarks'];		
	
	$queryDataUpdate = "UPDATE wms_return_to_vendor SET return_quantity='$return_quantity',remarks='$remarks' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Success","Request has been updated successfully", "success");
				requestRTVApproveEdit("'.$rowid.'");
				loadRTVData();
				$("#formodalsm").fadeOut();
			</script>
		';
	} else {
		return $db->error;
	}
}
if($mode == 'submitrtvrequest')
{
	$rowid = $_POST['rowid'];	
	$sqlQuery = "SELECT * FROM wms_receiving_details WHERE receiving_detail_id='$rowid'";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	while($DATA = mysqli_fetch_array($results))  
		{
			$details_id = $DATA['receiving_detail_id'];
			$supplier = $DATA['supplier_id'];
			$po_no = $DATA['po_no'];
			$item_code = $DATA['item_code'];
			$description = $DATA['item_description'];
			$quantity_received = $DATA['quantity_received'];
			$return_quantity = $_POST['quantity'];
			$requested_by = $app_user;
			$requested_date = $date_time;
			$remarks = $_POST['remarks'];			
		}
		$column = "`details_id`,`supplier_id`,`po_no`,`item_code`,`description`,`quantity_received`,`return_quantity`,`requested_by`,`requested_date`,`remarks`";
		$insert = "'$details_id','$supplier','$po_no','$item_code','$description','$quantity_received','$return_quantity','$requested_by','$requested_date','$remarks'";
		$queryInsert = "INSERT INTO wms_return_to_vendor ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			$last_id = mysqli_insert_id($db);
			echo '
				<script>
					swal("Success","Request has been added successfully", "success");
					requestRTVApproveEdit("'.$last_id.'");
					loadRTVData();
				</script>
			';
		} else {
			echo $db->error;
		}
	} else {			
		echo "No Records";
	}
}
if($mode == 'setmaintenance')
{
	$setVal = $_POST['maintenance'];
	$queryDataUpdate = "UPDATE tbl_wms_config SET maintenance='$setVal' WHERE id=1";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Activated","Physical Count is now activated","success");
				$("#" + sessionStorage.navwms).trigger("click");
			</script>
		';				
	} else {
		echo $db->error;
	}
}

if($mode == 'transferunlistedtolisted')
{
	$control_no = $_POST['control_no'];	
	$sqlQuery = "SELECT * FROM wms_branch_order_unlisted WHERE control_no=?";
	$stmt = $db->prepare($sqlQuery);
	$stmt->bind_param("s", $control_no); // "s" indicates the type of the parameter is string
	$stmt->execute();
	$results = $stmt->get_result();	
	if ($results->num_rows > 0) {
	    $data = array();
	    while ($ITEMSROW = $results->fetch_assoc()) {
	        $cluster = $ITEMSROW['cluster'];
	        $branch = $ITEMSROW['branch'];
	        $control_no = $ITEMSROW['control_no'];
	        $item_code = $ITEMSROW['item_code'];
	        $class = $ITEMSROW['class'];
	        $item_description = $ITEMSROW['item_description'];
	        $unit_price = $ITEMSROW['unit_price'];
	        $uom = $ITEMSROW['uom'];
	        $quantity = $ITEMSROW['quantity'];
	        $wh_quantity = $ITEMSROW['wh_quantity'];
	        $actual_quantity = $ITEMSROW['actual_quantity'];
	        $inv_ending = $ITEMSROW['inv_ending'];
	        $inv_ending_uom = $ITEMSROW['inv_ending_uom'];
	        $created_by = $ITEMSROW['created_by'];
	        $trans_date = $ITEMSROW['trans_date'];
	        $updated_by = $ITEMSROW['updated_by'];
	        $date_updated = $ITEMSROW['date_updated'];
	        $status = $ITEMSROW['status'];
	        $date_created = $ITEMSROW['date_created'];
	
	        $data[] = "('$cluster','$branch','$control_no','$item_code','$class','$item_description','$unit_price','$uom','$quantity','$wh_quantity','$actual_quantity','$inv_ending','$inv_ending_uom','$created_by','$trans_date','$updated_by','$date_updated','$status','$date_created')";
	    }	
	    $query = "INSERT INTO wms_branch_order (`cluster`,`branch`,`control_no`,`item_code`,`class`,`item_description`,`unit_price`,`uom`,`quantity`,`wh_quantity`,`actual_quantity`,`inv_ending`,`inv_ending_uom`,`created_by`,`trans_date`,`updated_by`,`date_updated`,`status`,`date_created`) VALUES " . implode(', ', $data);
	    if ($db->query($query) === TRUE)
	    {
	       $queryDataUpdate = "UPDATE wms_order_request SET status='Submitted', order_type=0 WHERE control_no='$control_no'";
			if ($db->query($queryDataUpdate) === TRUE)
			{
				echo '
					<script>
						swal("Successful","Order has now been mark as Listed Items","success");
					</script>
				';				
			} else {
				echo $db->error;
			}

	    } else {
	        echo "Error: " . $db->error;
	    }
	} else {
	    echo "No Records";
	}
}
if($mode == 'getitemcodeuom')
{
	$elemid = $_POST['elemid'];
	$item_description = $_POST['item_description'];
	
	$sqlQuery = "SELECT * FROM wms_itemlist WHERE item_description='$item_description'";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$item_code = $ITEMSROW['item_code'];
			$uom = $ITEMSROW['uom'];	
			$unit_price = $ITEMSROW['unit_price'];	
		}
		echo '
			<script>
				var elemid = '.$elemid.';
				$("#item_code" + elemid).val("'.$item_code.'");
				$("#unit_price" + elemid).val("'.$unit_price.'");
			</script>
		';
	} else {			
		echo "No Records";
	}
}

if($mode == 'reopenclosedorderrequest')
{
	$year = $_POST['year'];
	$month = $_POST['month'];
	$day = $_POST['day']; 
	$control_no = $_POST['control_no']; 
	$recipient = $_POST['recipient'];
	$daycol = "day_".$day;

	$query = "SELECT * FROM wms_request WHERE control_no='$control_no' AND flag=0"; 
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
		$log_msg = $recipient." | ".$control_no." | trying to Re-Open the pending Request.";
		echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);
		print_r('
			<script>
				swal("Pending Request","This transaction still have pending request for re-open","warning");
			</script>
		');
		exit();
	}
}
if($mode == 'grandwmsrequest')
{
	$rowid = $_POST['rowid'];
	$transit = $_POST['transit'];
	$control_no = $_POST['control_no'];
	$transit = $function->checkInTransit($control_no,$db);
	$queryDataUpdate = "UPDATE wms_request SET granted_by='$app_user',granted_date='$date_time', flag=1 WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		reOpenOrder($control_no,$app_user,$function,$transit,$db);
	} else {
		echo $db->error;
	}
}
function reOpenOrder($control_no,$app_user,$function,$transit,$db)
{
	$query = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results))  
		{
			$item_code = $ROW['item_code'];
			$actual_qty = $ROW['actual_quantity'];
			if($transit == 1)
			{
				echo $function->cancelOrderDeduction($item_code,$control_no,$actual_qty,$app_user,$db);
			}
			if($transit == 0)
			{
				echo $function->reOpenOrder($item_code,$control_no,$actual_qty,$app_user,$db);
			}
		}
	} else {
		echo "No Records";
	}
}
if($mode == 'reopenorderrequest')
{
	$recipient = $_POST['recipient'];
	$control_no = $_POST['control_no'];
	$query = "SELECT * FROM wms_request WHERE control_no='$control_no' AND flag=0";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
		$log_msg = $recipient." | ".$control_no." | trying to Re-Open the pending Request.";
		echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);
		print_r('
			<script>
				swal("Pending Request","This transaction still have pending request for re-open","warning");
			</script>
		');
	} else {
		$column = "`recipient`,`control_no`,`requested_by`,`request_date`";
		$insert = "'$recipient','$control_no','$app_user','$date_time'";
		$queryInsert = "INSERT INTO wms_request ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			$log_msg = $recipient." | ".$control_no." | Re-Open Request submitted";
			echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);
			print_r('
				<script>
					swal("Success","Your request has been submit","success");
				</script>
			');
		} else {
			echo $db->error;
		}
	}
}
if($mode == 'reopenorder')
{
	$control_no = $_POST['control_no'];
	$query = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results)) 
		{
			$item_code = $ROW['item_code'];
			$actual_qty = $ROW['actual_quantity'];
			echo $function->cancelOrderDeduction($item_code,$control_no,$actual_qty,$app_user,$db);
		}
	} else {
		echo "No Records";
	}
}
if($mode == 'updateleadtime')
{
	$minLT = $_POST['minValue'];
	$maxLT = $_POST['maxValue'];

	$queryDataUpdate = "UPDATE wms_inventory_leadtime SET average_leadtime='$minLT', max_leadtime='$maxLT' WHERE leadtime_id=1";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		
	} else {
		echo $db->error;
	}
}
if($mode == 'setreportbranch')
{
	$_SESSION['WMS_REPORT_BRANCH'] = $_POST['branch'];
}
if($mode == 'loadbranch')
{
	$cluster = $_POST['cluster'];
	$branch = $_POST['branch'];
	$query = "SELECT * FROM tbl_branch WHERE location='$cluster'";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
		$return = '<option value="">-- BRANCH --</option>';
	    while($ROW = mysqli_fetch_array($results))  
		{
			$branch_name = $ROW['branch'];
			$selected = '';
			if($branch == $branch_name)
			{
				$selected = "selected";
			}
			$return .= '<option '.$selected.' value="'.$branch_name.'">'.$branch_name.'</option>';
		}
		echo $return;
	} else {
		echo '<option value="">-- BRANCH --</option>';
	}
}
if($mode == 'setbranch')
{
	$_SESSION['WMS_BRANCH'] = $_POST['branch'];
}
if($mode == 'setcluster')
{
	$_SESSION['WMS_CLUSTER'] = $_POST['cluster'];
}
if($mode == 'undopcount')
{
	$transdate = $_POST['trans_date'];
	$itemcode = $_POST['itemcode'];
	$elemid = $_POST['elemid'];
	$sqlQueryStk = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode' AND stock_before_pcount_date='$transdate'";
    $stkResults = mysqli_query($db, $sqlQueryStk);
    if ($stkResults->num_rows > 0)
    {
       	while($ROWS = mysqli_fetch_array($stkResults))  
		{
			$rowid = $ROWS['inventory_id'];
			$stock_before_pcount = $ROWS['stock_before_pcount'];
		}
		$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$stock_before_pcount' WHERE inventory_id='$rowid'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
	    	$qDelete = "DELETE FROM wms_inventory_pcount WHERE trans_date='$transdate' AND item_code='$itemcode'";
			if ($db->query($qDelete) === TRUE){
				print_r('
					<script>
						var elemid = "'.$elemid.'";
						$("#pcountvalue" + elemid).html("0.00");
					</script>
				');
			} 
			else {echo $db->error;}
		} else {
			echo $db->error;
		}
	} else {
		print_r('		
			<script>
				swal("Invalid Request","The Physical count data was not recorded yet.", "warning");
			</script>
		');
	}
}
if($mode == 'saveinvsetup')
{
	$transdate = $_POST['trans_date'];
	$category = $_POST['category'];
	$itemcode = trim((string)$_POST['itemcode']);
	$itemname = $_POST['itemname'];
	$phycountRaw = isset($_POST['phycount']) ? trim((string)$_POST['phycount']) : '0';
	$phycountRaw = str_replace(',', '', $phycountRaw);
	$phycount = (is_numeric($phycountRaw) && (float)$phycountRaw >= 0) ? (float)$phycountRaw : 0;
	$uom = $_POST['uom'];

	if($itemcode === '')
	{
		print_r('        
			<script>
				swal("Invalid Item", "Item code is required.", "warning");
			</script>
		');
		exit;
	}

	$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date='$transdate' AND item_code='$itemcode'";
	$results = $db->query($query);			
    if($results->num_rows > 0)
    {
    	$update = "`item_code`='$itemcode',`category`='$category',`item_description`='$itemname',`uom`='$uom',`trans_date`='$transdate',`p_count`='$phycount'";
    	$queryDataUpdate = "UPDATE wms_inventory_pcount SET $update WHERE trans_date='$transdate' AND item_code='$itemcode'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			executeInventory($itemcode,$category,$itemname,$uom,$function,$phycount,$date_time,$date,$app_user,$db);
		} else {
			echo $db->error;
		}
    } else {
    	$column = "`item_code`,`category`,`item_description`,`uom`,`trans_date`,`p_count`";	
		$insert = "'$itemcode','$category','$itemname','$uom','$transdate','$phycount'";
		$queryInsert = "INSERT INTO wms_inventory_pcount ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
			executeInventory($itemcode,$category,$itemname,$uom,$function,$phycount,$date_time,$date,$app_user,$db);
		} else {
			echo $db->error;
		}
    }
}
function executeInventory($itemcode,$category,$itemname,$uom,$function,$phycount,$date_time,$date,$app_user,$db)
{
	$itemcode = trim((string)$itemcode);
	if($itemcode === '')
	{
		echo '<script>swal("Invalid Item", "Unable to update stock: blank item code.", "warning");</script>';
		return;
	}

	$checkItem = mysqli_query($db, "SELECT id FROM wms_itemlist WHERE item_code='".mysqli_real_escape_string($db, $itemcode)."' AND active=1 LIMIT 1");
	if(!$checkItem || $checkItem->num_rows === 0)
	{
		echo '<script>swal("Invalid Item", "Unable to update stock: item is not active or not found.", "warning");</script>';
		return;
	}

	$supplier_id = $function->GetItemInfo('supplier_id',$itemcode,$db);
	
	$sqlQueryStk = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
    $stkResults = mysqli_query($db, $sqlQueryStk);
    if ($stkResults->num_rows > 0)
    {
       	while($ROWS = mysqli_fetch_array($stkResults))  
		{
			$value_before_pcount = $ROWS['stock_in_hand'];
			$before_pcount_date = $ROWS['stock_before_pcount_date'];			
		}
		if($before_pcount_date == $date)
		{	
			$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_before_pcount_date='$date', stock_in_hand='$phycount', date_updated='$date_time',updated_by='$app_user' WHERE item_code='$itemcode'";
	        if ($db->query($queryDataUpdate) === TRUE) {
	        } else {
	            echo $db->error;
	        }
		}
		else 
		{
			$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_before_pcount_date='$date',stock_before_pcount='$value_before_pcount', stock_in_hand='$phycount', date_updated='$date_time',updated_by='$app_user' WHERE item_code='$itemcode'";
	        if ($db->query($queryDataUpdate) === TRUE) {
	        } else {
	            echo $db->error;
	        }
	    }
    } 
    else
    {
		$column = "supplier_id,item_code,category,item_description,stock_before_pcount_date,stock_in_hand,uom,date_updated,updated_by";
        $insert = "'$supplier_id','$itemcode','$category','$itemname','$date','$phycount','$uom','$date_time','$app_user'";
        $queryInsert = "INSERT INTO wms_inventory_stock ($column) VALUES ($insert)";
        if ($db->query($queryInsert) === TRUE) {
        } else {
            echo $db->error;
        } 
	}
}
if($mode == 'saveexpdate')
{
	$transdate = $_POST['transdate'];
	$itemcode = $_POST['itemcode'];
	$itemname = $_POST['itemname'];
	$category = $_POST['category'];
	$expdate = $_POST['expdate'];
	
	$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date='$transdate' AND item_code='$itemcode'";
	$results = $db->query($query);			
    if($results->num_rows > 0)
    {
    	$queryDataUpdate = "UPDATE wms_inventory_pcount SET expiration_date='$expdate' WHERE trans_date='$transdate' AND item_code='$itemcode'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
		} else {
			echo $db->error;
		}
    } else {
    	$column = "`item_code`,`category`,`item_description`,`trans_date`,`expiration_date`";	
		$insert = "'$itemcode','$category','$itemname','$transdate','$expdate'";
		$queryInsert = "INSERT INTO wms_inventory_pcount ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			echo $db->error;
		}
    }
}
if($mode == 'saveremarks')
{
	$transdate = $_POST['transdate'];
	$itemcode = $_POST['itemcode'];
	$itemname = $_POST['itemname'];
	$category = $_POST['category'];
	$remarks = $_POST['remarks'];
	
	$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date='$transdate' AND item_code='$itemcode'";
	$results = $db->query($query);			
    if($results->num_rows > 0)
    {
    	$queryDataUpdate = "UPDATE wms_inventory_pcount SET remarks='$remarks' WHERE trans_date='$transdate' AND item_code='$itemcode'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
		} else {
			echo $db->error;
		}
    } else {
    	$column = "`item_code`,`category`,`item_description`,`trans_date`,`remarks`";	
		$insert = "'$itemcode','$category','$itemname','$transdate','$remarks'";
		$queryInsert = "INSERT INTO wms_inventory_pcount ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			echo $db->error;
		}
    }
}
if($mode == 'savepcount')
{
	$transdate = $_POST['transdate'];
	$itemcode = trim((string)$_POST['itemcode']);
	$itemname = $_POST['itemname'];
	$category = $_POST['category'];
	$phycountRaw = isset($_POST['phycount']) ? trim((string)$_POST['phycount']) : '0';
	$phycountRaw = str_replace(',', '', $phycountRaw);
	$phycount = (is_numeric($phycountRaw) && (float)$phycountRaw >= 0) ? (float)$phycountRaw : 0;
	
	if($itemcode === '')
	{
		echo '<script>swal("Invalid Item", "Item code is required.", "warning");</script>';
		exit;
	}

	$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date='$transdate' AND item_code='$itemcode'";
	$results = $db->query($query);			
    if($results->num_rows > 0)
    {
    	$queryDataUpdate = "UPDATE wms_inventory_pcount SET p_count='$phycount' WHERE trans_date='$transdate' AND item_code='$itemcode'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
		} else {
			echo $db->error;
		}
    } else {
    	$column = "`item_code`,`category`,`item_description`,`trans_date`,`p_count`";	
		$insert = "'$itemcode','$category','$itemname','$transdate','$phycount'";
		$queryInsert = "INSERT INTO wms_inventory_pcount ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			echo $db->error;
		}
    }
}
if($mode == 'setintransit')
{
	$control_no = $_POST['control_no'];		
	$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results))  
		{
			$year = date("Y", strtotime($ROW['delivery_date']));
			$month = date("m", strtotime($ROW['delivery_date']));
			$day = date("d", strtotime($ROW['delivery_date']));
		}
	} else {
		print_r('
			<script>
				swal("Warning","Control Number is invalid", "warning");
			</script>
		');
		exit();
	}
	
	function updateLogistics($control_no, $db)
	{
	    $date = date("Y-m-d");
	    $queryDataUpdate = "UPDATE wms_order_request SET order_transit=1, status='In-Transit',order_transit_date=? WHERE control_no=? ORDER BY request_id DESC";
	    $stmt = $db->prepare($queryDataUpdate);
	    $stmt->bind_param("ss", $date, $control_no);	   
	 	if ($stmt->execute()) {
	        print_r('
	            <script>				
	                dr_details("' . $control_no . '");
	            </script>
	        ');
	    } else {
	        echo $stmt->error;
	    }
	    $stmt->close();
	}
	function updateToInventory($year, $month, $day, $item_code, $unit_price, $item_description, $new_quantity, $control_no, $db)
	{
	    $col = "day_" . $day;	    
	    $queryDataUpdate = "UPDATE wms_inventory_records SET $col=? WHERE item_code=? AND year=? AND month=?";
	    $stmt = $db->prepare($queryDataUpdate);
	    $stmt->bind_param("issi", $new_quantity, $item_code, $year, $month);
	    if ($stmt->execute()) {
	        updateLogistics($control_no, $db);
	        print_r('
	            <script>				
	                swal("Success", "The order has been passed to Logistics for Transit", "success");
	            </script>
	        ');
	    } else {
	        print_r('
	            <script>
	                swal("Update Error:", "' . $stmt->error . '", "warning");
	            </script>
	        ');
	    }
	    $stmt->close();
	}

	function insertToInventory($year, $month, $day, $item_code, $unit_price, $item_description, $actual_quantity, $control_no, $db)
	{
		$column = "`unit_price`,`item_code`,`item_description`,`year`,`month`,`day_" . $day . "`";
		$insert = "'$unit_price','$item_code','$item_description','$year','$month','$actual_quantity'";

		$queryInsert = "INSERT INTO wms_inventory_records ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
	        updateLogistics($control_no, $db);
	        print_r('
	            <script>
	                swal("Success", "The order has been passed to Logistics for Transit", "success");
	            </script>
	        ');
		} else {
			 print_r('
	            <script>
	                swal("Insert Error:", "' . $db->error . '", "warning");
	            </script>
	        ');
		}
	}
	function queryInventory($year, $month, $day, $unit_price, $item_code, $item_description, $actual_quantity, $control_no, $db)
	{
	    $col = "day_" . $day;    
	    $QUERYRECORDS = "SELECT * FROM wms_inventory_records WHERE item_code=? AND year=? AND month=?";
	    $stmt = $db->prepare($QUERYRECORDS);
	    $stmt->bind_param("ssi", $item_code, $year, $month);
	    $stmt->execute();
	    
	    $RECORDSRESULTS = $stmt->get_result();
	    
	    if ($RECORDSRESULTS->num_rows > 0) {
	        while ($ROWS = $RECORDSRESULTS->fetch_assoc()) {
	            $quantity = intval($ROWS[$col]);
	            $new_quantity = $quantity + $actual_quantity;
	            updateToInventory($year, $month, $day, $item_code, $unit_price, $item_description, $new_quantity, $control_no, $db);
	        }
	    } else {
	        insertToInventory($year, $month, $day, $item_code, $unit_price, $item_description, $actual_quantity, $control_no, $db);
	    }
	    $stmt->close();
	}
	function updateStock($item_code,$new_stock,$db)
	{
	    $queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand=? WHERE item_code=?";
	    $stmt = $db->prepare($queryDataUpdate);
	    $stmt->bind_param("is", $new_stock, $item_code);
	    if ($stmt->execute()) {
	    } else {
	        print_r('
	            <script>
	                swal("Update Error:", "' . $stmt->error . '", "warning");
	            </script>
	        ');
	    }
	    $stmt->close();
	}
	
	$QUERY = "SELECT * FROM wms_branch_order WHERE control_no=?";
	$stmt = $db->prepare($QUERY);
	$stmt->bind_param("s", $control_no);
	$stmt->execute();
	$RESULTS = $stmt->get_result();
	while ($ROW = $RESULTS->fetch_assoc())
	{
	    $item_code = $ROW['item_code'];
	    $actual_quantity = $ROW['actual_quantity'];
	    $item_description = $ROW['item_description'];
	    $unit_price = $function->GetUnitPrice($item_code,$db);
	    queryInventory($year, $month, $day, $unit_price, $item_code, $item_description, $actual_quantity, $control_no, $db);
	    $stock = $function->GetOnHand($item_code,$db);
	    $new_stock = ($stock - $actual_quantity);
	    updateStock($item_code,$new_stock,$db);
	}
	$stmt->close();	
}
if($mode == 'savelogisticinfo')
{
	$control_no = $_POST['control_no'];
	$delivery_date = $_POST['delivery_date'];
	$delivery_driver = $_POST['delivery_driver'];
	$plate_number = $_POST['plate_number'];
	
	$_SESSION['LOGISTIC_DRIVER'] = $delivery_driver;
	$_SESSION['LOGISTIC_PLATE'] = $plate_number;

	$queryDataUpdate = "UPDATE wms_order_request SET delivery_date=?, delivery_driver=?, plate_number=? WHERE control_no=?";
	$stmt = $db->prepare($queryDataUpdate);
	$stmt->bind_param("ssss", $delivery_date, $delivery_driver, $plate_number, $control_no);
	if ($stmt->execute())
	{
		echo $function->UpdateBranchOrderDeliveryDate($control_no,$delivery_date,$db);
	    print_r('
	        <script>
	            swal("Success", "Logistic Information has been added to the Delivery Receipt", "success");
	            dr_details("' . $control_no . '");
	        </script>
	    ');
	} else {
	    print_r('
	        <script>
	            swal("Saving Error:", "' . $stmt->error . '", "warning");
	        </script>
	    ');
	}
	$stmt->close();
}
if($mode == 'forwardtologistics')
{
	$drnumber = $function->GetDrNumber($db);
	$number  = intval($drnumber);
	$number += 1;
	$new_dr_no = str_pad($number, strlen($drnumber), '0', STR_PAD_LEFT);
	$dr_number = "DR-".$drnumber;

	$control_no = $_POST['control_no'];
	$module = $_POST['module'];
	
	$queryDataUpdate = "UPDATE wms_order_request SET logistics=1, dr_number='$dr_number' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$queryDataUpdate = "UPDATE wms_form_numbering SET dr_number='$new_dr_no' WHERE id=1";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			$log_msg = $module.' | Finalized Order | CN'.$control_no;
			print_r('
				<script>
					swal("Success", "Delivery Receipt has been Created successfuly", "success");
					orderProcess("'.$control_no.'");
				</script>
			');
			echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);
		} else {
			print_r('
				<script>
					swal("Numbering Error:", "'.$db->error.'", "warning");
				</script>
			');
		}		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}	
}
if($mode == 'preparatorremarks')
{
	$remarks = $_POST['remarks'];
	$control_no = $_POST['control_no'];

	$sqlQuery = "SELECT * FROM wms_branch_order_remarks WHERE control_no='$control_no'";
	$results = mysqli_query($db, $sqlQuery);
	if ( $results->num_rows > 0 ) 
    {
		$queryDataUpdate = "UPDATE wms_branch_order_remarks SET preparator_remarks='$remarks' WHERE control_no='$control_no'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			echo $remarks." -- ".$control_no;
		} else {
			print_r('
				<script>
					swal("Warning", "'.$db->error.'", "warning");
				</script>
			');
		}
	}
	else
	{	
		$queryInsert = "INSERT INTO wms_branch_order_remarks (`preparator_remarks`,`control_no`) VALUES ('$remarks','$control_no')";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			echo $db->error;
		}

	}
}
if($mode == 'changeactualcount')
{
	$rowid = $_POST['rowid'];
	$control_no = $_POST['control_no'];
	$item_code = $_POST['item_code'];
	$unit_price = $_POST['unit_price'];
	$actual_quantity = $_POST['actual_quantity'];
	$queryDataUpdate = "UPDATE wms_branch_order SET wh_quantity='$actual_quantity', actual_quantity='$actual_quantity', unit_price='$unit_price' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				orderProcess("'.$control_no.'");
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'preparebranchorder')
{
	$app_user = ucwords($app_user);
	$module = $_POST['module'];
	$control_no = $_POST['control_no'];
	$queryDataUpdate = "UPDATE wms_order_request SET order_preparing=1, order_preparing_by='$app_user', order_preparing_date='$date' WHERE control_no='$control_no' AND order_preparing=0";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$log_msg = $module.' | Preparing Order | CN'.$control_no;
		print_r('
			<script>
				swal("Success", "Order has been successfully unlock", "success");
				orderProcess("'.$control_no.'");
			</script>
		');		
		echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);	
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}	
}
if($mode == 'receivebranchorder')
{
	$app_user = ucwords($app_user);
	$control_no = $_POST['control_no'];
	$module = $_POST['module'];
	$queryDataUpdate = "UPDATE wms_order_request SET order_received=1, order_received_by='$app_user', order_received_date='$date' WHERE control_no='$control_no' AND order_received=0";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$log_msg = $module.' | Received Order | CN'.$control_no;
		print_r('
			<script>
				swal("Success", "Order has been successfully received", "success");
				orderProcess("'.$control_no.'");
			</script>
		');		
		echo $function->DoAuditLogs($date_time,$log_msg,$app_user,$db);	
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}	
}
if($mode == 'displaybranch')
{
	
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];
		$q = "WHERE branch LIKE '%$search%'";
	} else {
		$q = "";
	}
	$sqlQuery = "SELECT * FROM tbl_branch $q LIMIT 100";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$branch = $ITEMSROW['branch'];
			
?>
			<li onclick="setSearch('<?php echo $branch; ?>')"><?php echo $branch; ?></li>
<?php
		}
	} else {
		echo "<li>No Record.</li>";
	}
}
if($mode == 'recevingstocks')
{
	
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];
		$q = "WHERE active=1 AND item_description LIKE '%$search%' OR item_code LIKE '%$search%' OR qr_code LIKE '%$search%'";
	} else {
		$q = "WHERE active=1";
	}
	$sqlQuery = "SELECT * FROM wms_itemlist $q";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$item_code = $ITEMSROW['item_code'];
			$item = $ITEMSROW['item_description'];
			$uom = $ITEMSROW['uom'];	
			$unitprice = $ITEMSROW['unit_price'];
			$supplier_id = $ITEMSROW['supplier_id'];	
			$category = $ITEMSROW['category'];
			
?>
			<li onclick="setSearch('<?php echo $item; ?>','<?php echo $item_code; ?>','<?php echo $unitprice; ?>','<?php echo $uom; ?>','<?php echo $category; ?>')"><?php echo $item; ?></li>
<?php
		}
	} else {
		echo "<li>No Record.</li>";
	}
}
if($mode == 'transfersearch')
{
	
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];
		$q = "WHERE active=1 AND item_description LIKE '%$search%' OR item_code LIKE '%$search%' OR qr_code LIKE '%$search%'";
	} else {
		$q = "WHERE active=1";
	}
	$sqlQuery = "SELECT * FROM wms_itemlist $q LIMIT 100";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$item_code = $ITEMSROW['item_code'];
			$item = $ITEMSROW['item_description'];
			$uom = $ITEMSROW['uom'];	
			$unitprice = $ITEMSROW['unit_price'];
			$supplier_id = $ITEMSROW['supplier_id'];	
			$category = $ITEMSROW['category'];
			
?>
			<li onclick="setSearchItem('<?php echo $item; ?>','<?php echo $item_code; ?>')"><?php echo $item; ?></li>
<?php
		}
	} else {
		echo "<li>No Record.</li>";
	}
}
mysqli_close($db);
?>