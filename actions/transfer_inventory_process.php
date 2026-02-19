<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once($_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php");
$function = new WMSFunctions;

$created_date = date("Y-m-d H:i:s");
/*
	Insert sa details dapat may check muna if existing sya sa
*/
$row_id = $_POST['rowid'];
$sqlQuery = "SELECT * FROM wms_warehouse_transfer WHERE id='$row_id'";
$results = mysqli_query($db, $sqlQuery);    
if ( $results->num_rows > 0 ) 
{
	$data = array();
	while($RECVROW = mysqli_fetch_array($results))  
	{
		$rowid = $RECVROW['id'];		
		$receiving_id = $RECVROW['id'];		
		$po_no = 'WH2WH';
		$si_no = 'WH2WH';
		$item_code = $RECVROW['item_code'];
		$takeout_amount = $RECVROW['takeout_amount'];
		$transaction_type = "Receiving";
		$category = $RECVROW['category'];
		$item_description = $RECVROW['item_description'];
		$itemcode = $RECVROW['itemcode'];
		$quantity_received = $RECVROW['convert_amount'];
		$uom = $RECVROW['convert_uom'];
		$received_by = $RECVROW['created_by'];
		$received_date = $RECVROW['transaction_date'];
		$expiration_date = NULL;
		$control_no = NULL;
		$date_created = $created_date;
		$unit_price = 0;
		$total_cost = 0;
		$supplier_id = $function->GetItemInfo('supplier_id',$itemcode,$db);
		
		$data = "
			'$supplier_id','$po_no','$si_no','$transaction_type','$category','$item_description','$itemcode','$quantity_received',
			'$uom','$received_by','$received_date','$expiration_date','$control_no','$date_created','$unit_price','$total_cost'
		";
		// insertToDetails($rowid,$data,$itemcode,$item_code,$quantity_received,$takeout_amount,$function,$db);
		insertToReceiving($rowid,$supplier_id,$po_no,$si_no,$itemcode,$received_by,$received_date,$data,$quantity_received,$takeout_amount,$item_code,$function,$db);
	}
} else {
	echo "No records we're found";
}
function insertToReceiving($rowid,$supplier_id,$po_no,$si_no,$itemcode,$received_by,$received_date,$data,$quantity_received,$takeout_amount,$item_code,$function,$db)
{
	$supplier_id = $supplier_id;
	$po_no = $po_no;
	$si_no = $si_no;
	$total_cost = 0;
	$created_by = $received_by;
	$date_created = $received_date;
	$receiving_status = 'Full';
	$status = 'Open';
	
	$column = "`supplier_id`,`po_no`,`si_no`,`total_cost`,`created_by`,`date_created`,`delivery_status`,`status`,`receiving_status`";
	$insert = "'$supplier_id','$po_no','$si_no','0','$created_by','$date_created',NULL,'Closed','$receiving_status'";
	
	$queryInsert = "INSERT INTO wms_receiving ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		$receiving_id = $db->insert_id;
		insertToDetails($receiving_id,$rowid,$data,$itemcode,$quantity_received,$takeout_amount,$item_code,$function,$db);
	} else {
		echo $db->error;
	}

}
function insertToDetails($receiving_id,$rowid,$data,$itemcode,$quantity_received,$takeout_amount,$item_code,$function,$db)
{
	$column = "`receiving_id`,`supplier_id`,`po_no`,`si_no`,`transaction_type`,`category`,`item_description`,`item_code`,`quantity_received`,`uom`,`received_by`,`received_date`,`expiration_date`,`control_no`,`date_created`,`unit_price`,`total_cost`";
	$insert = "'$receiving_id',".$data;
	$queryInsert = "INSERT INTO wms_receiving_details ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		executeInventory($itemcode,$rowid,$quantity_received,$takeout_amount,$item_code,$function,$db);
	} else {
		echo $db->error;
	}
}
function executeInventory($itemcode,$rowid,$quantity_received,$takeout_amount,$item_code,$function,$db)
{
    $supplier_id = $function->GetItemInfo('supplier_id', $itemcode, $db);
    $category = $function->GetItemInfo('category', $itemcode, $db);
    $item_description = $function->GetItemInfo('item_description', $itemcode, $db);
    $uom = $function->GetItemInfo('uom', $itemcode, $db);
	$unit_price = $function->GetUnitPrice($itemcode,$db)
	
    $sqlQueryChk = "SELECT * FROM wms_warehouse_transfer WHERE itemcode='$itemcode'";
    $chkResults = mysqli_query($db, $sqlQueryChk);
    if ($chkResults->num_rows > 0) {
        $current_stock = $function->GetOnHand($itemcode, $db);
        while ($STKROW = mysqli_fetch_array($chkResults)) {
            $convert_amount = $STKROW['convert_amount'];
        }
    } else {
    	echo "Something is Wrong";
    }
    
    $current_stock = $function->GetOnHand($itemcode, $db);
    $new_stock = ($current_stock + $convert_amount);
    $deduct_stock = ($function->GetOnHand($item_code, $db)-$takeout_amount);
    
    $sqlQueryStk = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
    $stkResults = mysqli_query($db, $sqlQueryStk);
    if ($stkResults->num_rows > 0)
    {
		$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$new_stock' WHERE item_code='$itemcode'";
        if ($db->query($queryDataUpdate) === TRUE) {
	        deduct_stock($item_code,$deduct_stock,$db);
            closeTransfer($rowid, $itemcode, $db); // Pass rowid and itemcode as a parameters
             ($unit_price, $item_code, $item_description, $actual_quantity, $control_no, $db);
        } else {
            echo $db->error;
        }
    } 
    else
    {
		$column = "supplier_id,item_code,category,item_description,stock_in_hand,uom";
        $insert = "'$supplier_id','$itemcode','$category','$item_description','$quantity_received','$uom'";

        $queryInsert = "INSERT INTO wms_inventory_stock ($column) VALUES ($insert)";
        if ($db->query($queryInsert) === TRUE) {
        	deduct_stock($item_code,$deduct_stock,$db);
        	queryInventory($unit_price, $item_code, $item_description, $actual_quantity, $control_no, $db);

        	// closeTransfer($rowid, $itemcode, $db); // Pass rowid and itemcode as a parameters
        	
        } else {
            echo $db->error;
        }
	}
}
function deduct_stock($item_code,$deduct_stock,$db)
{
	$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$deduct_stock' WHERE item_code='$item_code'";
    if ($db->query($queryDataUpdate) === TRUE) {
        closeTransfer($rowid, $itemcode, $db); // Pass rowid and itemcode as a parameters
    } else {
        echo $db->error;
    }
}
function closeTransfer($rowid, $itemcode, $db)
{
    $queryDataUpdate = "UPDATE wms_warehouse_transfer SET status='Closed' WHERE id='$rowid'";
    if ($db->query($queryDataUpdate) === TRUE) {
        print_r('
            <script>
                stockList();
                var rowid = "'.$rowid.'";
                getItemToFormID("edit", rowid);                
                swal("Success", "Item has been Added into Into inventory", "success");
            </script>
        ');
    } else {
        echo $db->error;
    }
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
function queryInventory($unit_price, $item_code, $item_description, $actual_quantity, $control_no, $db)
{
	$year = date("Y");
	$month = date("m");
	$day = date("Y");
	
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
