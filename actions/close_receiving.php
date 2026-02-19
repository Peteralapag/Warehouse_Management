<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$receiving_id = $_POST['rid'];

if(isset($_SESSION['wms_appnameuser']))
{
	$app_user = strtolower($_SESSION['wms_appnameuser']);
}
$date = date("Y-m-d");
$sqlQuery = "SELECT * FROM wms_receiving_details WHERE receiving_id='$receiving_id' AND in_stock=0";
$results = mysqli_query($db, $sqlQuery);    
while($ROW = mysqli_fetch_array($results))  
{
	$supplier_id = $ROW['supplier_id'];
	$item_code = $ROW['item_code'];
	$uom = $ROW['uom'];
	$quantity_received = $ROW['quantity_received'];
	$category = $ROW['category'];
	$item_description = $ROW['item_description'];
	$new_in_hand = ($quantity_received + $function->GetOnHand($item_code,$db));	
	update_stock($supplier_id,$item_code,$uom,$category,$item_description,$new_in_hand,$app_user,$date,$receiving_id,$db);

}

function update_stock($supplier_id,$item_code,$uom,$category,$item_description,$new_in_hand,$app_user,$date,$receiving_id,$db)
{
	$query = "SELECT * FROM wms_inventory_stock WHERE item_code='$item_code'";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{		
		$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand=? WHERE item_code=?";
		$stmt = $db->prepare($queryDataUpdate);			
		if ($stmt) {
		    $stmt->bind_param("ss", $new_in_hand, $item_code);
		    if ($stmt->execute())
		    {
		        /* ############################################################################## */
				$queryDataUpdate = "UPDATE wms_receiving SET status='Closed', closed_by=?, close_date=? WHERE receiving_id=?";
				$stmt = $db->prepare($queryDataUpdate);			
				if ($stmt) {
				    $stmt->bind_param("sss", $app_user, $date, $receiving_id);
				    if ($stmt->execute())
				    {
					    $stmt->bind_param("sss", $app_user, $date, $receiving_id);
					    if ($stmt->execute())			
					    {
					        print_r('
					            <script>
					                swal("Success Closing", "Receiving has been closed and Inventory stock has been adjusted to '.$new_in_hand.'", "warning");
					                load_data();
					            </script>
					        ');
					    } else {
					        echo "Error executing the statement: " . $stmt->error;
					    }
				    } else {
				        echo "Error executing the statement: " . $stmt->error;
				    }
				} else {
				    echo "Error in preparing the statement.";
				}
			    /* ############################################################################## */
		    } else {
		        echo "Error executing the statement: " . $stmt->error;
		    }
		    
		} else {
		    echo "Error in preparing the statement.";
		}
	} else {
		/**********************************************************************/
		$column = "`supplier_id`, `item_code`,`category`, `item_description`, `stock_in_hand`,`uom`";
	    $insert = "?,?,?,?,?,?";
	    $queryInsert = "INSERT INTO wms_inventory_stock ($column) VALUES ($insert)";
	    $stmt = $db->prepare($queryInsert);
	    $stmt->bind_param("ssssds", $supplier_id,$item_code,$category,$item_description,$new_in_hand,$uom);
	    if ($stmt->execute())
	    {
		    /* ############################################################################## */
			$queryDataUpdate = "UPDATE wms_receiving SET status='Closed', closed_by=?, close_date=? WHERE receiving_id=?";
			$stmt = $db->prepare($queryDataUpdate);			
			if ($stmt)
			{
			    $stmt->bind_param("sss", $app_user, $date, $receiving_id);
			    if ($stmt->execute())			
			    {
			        print_r('
			            <script>
			                swal("Success Closing", "Receiving has been closed and Inventory stock has been adjusted to '.$new_in_hand.'", "warning");
			                load_data();
			            </script>
			        ');
			    } else {
			        echo "Error executing the statement: " . $stmt->error;
			    }
			} else {
			    echo "Error in preparing the statement.";
			}
		    /* ############################################################################## */
	    } else {
	        print_r('
	            <script>
	                swal("Insert Error:", "' . $stmt->error . '", "warning");
	            </script>
	        ');
	    }
	    $stmt->close();
		/**********************************************************************/		
	} 
}