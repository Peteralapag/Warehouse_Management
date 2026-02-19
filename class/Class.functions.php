<?php
class WMSFunctions
{

	public function generateUniquePrNumber($db)
	{
	    $datePrefix = date('Ymd');
	    $source = 'WH';
	
	    try {
	        $db->begin_transaction();
	
	        $sql = "SELECT pr_number
	                FROM purchase_request
	                WHERE pr_number LIKE ?
	                ORDER BY pr_number DESC
	                LIMIT 1
	                FOR UPDATE";
	
	        $like = "$source-$datePrefix-%";
	        $stmt = $db->prepare($sql);
	        $stmt->bind_param("s", $like);
	        $stmt->execute();
	        $stmt->bind_result($lastPr);
	        $stmt->fetch();
	        $stmt->close();
	
	        $lastNumber = $lastPr
	            ? (int) substr($lastPr, -4)
	            : 0;
	
	        $newNumber = $lastNumber + 1;
	
	        $newPr = "$source-$datePrefix-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
	
	        $db->commit();
	        return $newPr;
	
	    } catch (Exception $e) {
	        $db->rollback();
	        throw $e;
	    }
	}


	public function getRTVData($filters)
	{
		$_target = array("Pending"=>"Pending","Approved"=>"Approved","Rejected"=>"Rejected");
		$return = '';
        foreach ( $_target as $key => $value )
        {
        	$selected = "";
        	if($value == $filters)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetWHOut($branch,$item_code,$date_from,$date_to,$db)
	{
		$query = "SELECT *, SUM(actual_quantity) AS total FROM wms_branch_order WHERE branch='$branch' AND delivery_date BETWEEN '$date_from' AND '$date_to'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['total'];
			}
	    } else {
	    	return 0;
	    }
	}
	public function GetBranchCount($cluster,$db)
	{
		$query = "SELECT *, COUNT(branch) AS total FROM tbl_branch";
		$results = $db->query($query);			
		$row = mysqli_fetch_assoc($results);
	    $totalRows = $row['total'];
	    return $totalRows;
	}
	public function GetItemFilters($filters)
	{
		$_target = array("ITEM DESCRIPTION"=>"item_description","CONTROL No."=>"control_no");
		$retur = '';
        foreach ( $_target as $key => $value )
        {
        	$selected = "";
        	if($value == $filters)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetOrderDate($control_no,$branch,$db)
	{
		$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no' AND branch='$branch'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['date_created'];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}

	public function GetOrderData($control_no,$db)
	{
		$query = "SELECT * FROM wms_branch_order_unlisted";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
	    	
		}
	}
	public function loadPagesCount($table,$records_per_page,$offset,$db)
	{
	    $sqlQuery = "SELECT COUNT(*) as total FROM $table";
	    $result = mysqli_query($db, $sqlQuery);
	    $row = mysqli_fetch_assoc($result);
	    $totalRows = $row['total'];
	
	    $totalPages = ceil($totalRows / $records_per_page);
	    
	    
	    $paginationHTML = '';
	    for ($i = 1; $i <= $totalPages; $i++)
	    {
	    	$selected = '';
	    	if($offset == $i)
	    	{
	    		$selected = 'selected';
	    	}
	        $paginationHTML .= "<option $selected value='$i'>$i</option>";
	    }	
	
	   return $paginationHTML;
	}
	
	public function GetPageCount($table,$records_per_page,$offset,$page,$db)
	{
		if($active == 1)
		{
			$q = "WHERE active=1";
		} else {
			$q='';
		}
	    $sqlQuery = "SELECT COUNT(*) as total FROM $table $q";
	    $result = mysqli_query($db, $sqlQuery);
	    $row = mysqli_fetch_assoc($result);
	    $totalRows = $row['total'];
	
	    $totalPages = ceil($totalRows / $records_per_page);
	}
	public function GetUnlistedName($db)
	{
		$query = "SELECT * FROM wms_itemlist";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
	    	$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$items = $ROW['item_description'];
				$return .= '<option value="'.$items.'">'.$items.'</option>';
			}
			return $return;
	    } else {
	    	return 'No Item found';
	    }
	    mysqli_close($db);
	}
	public function GetClassifications($class,$db)
	{
		$query = "SELECT class FROM wms_itemlist GROUP BY class";
		$results = $db->query($query);
		if($results->num_rows > 0)
	    {	$return = '<option value="">-SELECT CLASS-</option>';
	    	while($ROW = mysqli_fetch_array($results))  
			{
				$class = $ROW['class'];
				$return .= '<option value="'.$class.'">'.$class.'</option>';
			}
			return $return;
		} else {
		    return '<option value="">No Classification</option>';
		}
		mysqli_close($db);
	}
	public function GetReportCluster($rec_cluster,$db)
	{
		$query = "SELECT * FROM tbl_cluster ORDER BY cluster ASC";
		$results = $db->query($query);
		if($results->num_rows > 0)
	    {	$return = '<option value="">-SELECT CLUSTER-</option>';
	    	while($ROW = mysqli_fetch_array($results))  
			{
				$cluster = $ROW['cluster'];
				$selected = '';
				if($cluster == $rec_cluster)
				{
					$selected = 'selected';
				}
				$return .= '<option '.$selected.' value="'.$cluster.'">'.$cluster.'</option>';
			}
			return $return;
		} else {
		    return '<option value="">No Cluster</option>';
		}
		mysqli_close($db);
	}
	public function GetReportTarget($target)
	{
		$_target = array("WAREHOUSES"=>"WAREHOUSES","BRANCHES"=>"BRANCHES");
        $return = '<option value="">-SELECT REPORT-</option>';
        foreach ( $_target as $key => $value )
        {
        	$selected = "";
        	if($value == $target)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function UpdateBranchOrderDeliveryDate($control_no,$delivery_date,$db)
	{
		$queryDataUpdate = "UPDATE wms_branch_order SET delivery_date='$delivery_date' WHERE control_no='$control_no'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
		} else {
			return $db->error;
		}
	}

	public function GetItemClassData($column,$item_code,$db)
	{
		$QUERY = "SELECT * FROM wms_classifications_records WHERE item_code='$item_code' AND flag=0";
		$RESULTS = mysqli_query($db, $QUERY);		
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$retVal += $ROW[$column];
			}
			return $retVal;
		} else {
			return 0;
		}
	}
	public function GetClassificationData($item_code,$db)
	{
		$QUERY = "SELECT * FROM wms_order_request WHERE item_code='$item_code'";
		$RESULTS = mysqli_query($db, $QUERY);		
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$logistics = $ROW['logistics'];
				$transit = $ROW['order_transit'];
			}
			if($logistics == 1 AND $transit == 1)
			{
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	public function GetClassification($class)
	{
		$classfication = array("Bad Order"=>"Bad Order","Damaged"=>"Damaged","Seconds"=>"Seconds","Disposal"=>"Disposal","Re-Classification"=>"Re-Classification");
       	$return = '<option value="">--- Classifications ---</option>';
        foreach ( $classfication as $key => $value )
        {
        	$selected = '';
        	if($value == $class)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function checkInTransit($control_no,$db)
	{
		$QUERY = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$RESULTS = mysqli_query($db, $QUERY);		
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$logistics = $ROW['logistics'];
				$transit = $ROW['order_transit'];
			}
			if($logistics == 1 AND $transit == 1)
			{
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	public function getDeliveryDate($control_no,$db)
	{
		$QUERY = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$RESULTS = mysqli_query($db, $QUERY);		
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$delDate = $ROW['delivery_date'];
			}
			return $delDate;
		} else {
			return 0;
		}
	}
	public function getDeliveryMonth($control_no,$db)
	{
		$cur_date = date("m");
		$QUERY = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$RESULTS = mysqli_query($db, $QUERY);		
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$delDate = date("m", strtotime($ROW['delivery_date']));
			}
			if($cur_date == $delDate)
			{
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	public function reOpenOrder($item_code,$control_no,$actual_qty,$app_user,$db)
	{
		$queryDataUpdate = "UPDATE wms_order_request SET status='Submitted', logistics=0, order_transit=0 WHERE control_no='$control_no'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			//echo "SUCCESS";
		} else {
			echo $db->error;
		}
	}
	public function cancelOrderDeduction($item_code,$control_no,$actual_qty,$app_user,$db)
	{
		$QUERY = "SELECT * FROM wms_inventory_stock WHERE item_code='$item_code'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$new_stock = ($ROW['stock_in_hand'] + $actual_qty);				
			/* ------------------------------------------------------------------------------------------- */
				$queryDataUpdate = "UPDATE wms_inventory_stock SET stock_in_hand='$new_stock' WHERE item_code='$item_code'";
				if ($db->query($queryDataUpdate) === TRUE)
				{
				/* ##################################################################### */
					$queryDataUpdate = "UPDATE wms_order_request SET status='Submitted', logistics=0, order_transit=0 WHERE control_no='$control_no'";
					if ($db->query($queryDataUpdate) === TRUE)
					{
					} else {
						echo $db->error;
					}
				/* ##################################################################### */
				} else {
					echo $db->error;
				}
			/* ------------------------------------------------------------------------------------------- */
			}
			return $new_stock;
		} else {
			return 'Invalid';
		}
	}
	public function GetPickList($control_no,$db)
	{
		$queryBO = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
		$resultBO = mysqli_query($db, $queryBO);		
		if ($resultBO)
		{
		    $boCount = mysqli_num_rows($resultBO);
		} else {

		    $boCount = 0;
		}
		$queryPL = "SELECT * FROM wms_picklist WHERE control_no='$control_no'";
		$resultPL = mysqli_query($db, $queryPL);		
		if ($resultPL)
		{
		    $plCount = mysqli_num_rows($resultPL);
		} else {

		    $plCount = 0;
		}
		
		if($boCount == $plCount)
		{
			return 1;
		} else {
			return 0;
		}
	}
	public function getPOSI($receiving_id,$column,$db)
	{
		$QUERY = "SELECT * FROM wms_receiving WHERE receiving_id='$receiving_id'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			$return='';
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$return = $ROW[$column];
			}
			return $return;
		} else {
			return 'Invalid';
		}
	}
	public function closeReceiving($receiving_id,$db)
	{
		$query = "SELECT * FROM wms_receiving WHERE receiving_id='$receiving_id' AND status='Closed'";
		$result = mysqli_query($db, $query);		
	    if ( $result->num_rows > 0 ) 
	    {
	    	return 1;
		} else {
			return 0;
		}
	}
	public function GetPickCount($branch,$control_no,$db)
	{
		$queryBO = "SELECT * FROM wms_branch_order WHERE control_no='$control_no' AND branch='$branch'";
		$resultBO = mysqli_query($db, $queryBO);		
		if ($resultBO)
		{
		    $boCount = mysqli_num_rows($resultBO);
		} else {

		    $boCount = 0;
		}
		$queryPL = "SELECT * FROM wms_picklist WHERE control_no='$control_no'";
		$resultPL = mysqli_query($db, $queryPL);		
		if ($resultPL)
		{
		    $plCount = mysqli_num_rows($resultPL);
		} else {

		    $plCount = 0;
		}
		
		if($boCount == $plCount)
		{
			return 1;
		} else {
			return 0;
		}
	}
	public function GetModulePermission($username,$userlevel,$module,$permission,$db)
	{
		if($userlevel >= 80)
		{
			return 1;
		} elseif($userlevel < 80) {
			$checkPolicy = "SELECT * FROM tbl_system_permission WHERE username='$username' AND modules='$module' AND $permission=1";
			$pRes = mysqli_query($db, $checkPolicy);    
		    if ( $pRes->num_rows > 0 ) 
		    {
		    	return 1;
			} else {
				return 0;
			}
		}
		mysqli_close($db);
	}
	public function GetOrderReceiving($ord)
	{
		$stats = array("Process Order"=>"Process Order","Closed Order"=>"Closed Order");
        $return = "";
        foreach ( $stats as $key => $value )
        {
        	$selected = "";
        	if($value == $ord)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetItemStock($itemcode,$db)
	{
		$QUERY = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			$return='';
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$return = $ROW['stock_in_hand'];
			}
			return $return;
		} else {
			return 0;
		}
	}
	public function GetWMSReports($report,$db)
	{
		$query = "SELECT * FROM wms_reporting_option ORDER BY ordering ASC";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">-- SELECT REPORT --</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$reporting = $ROW['reporting_option'];
				$selected = '';
				if($report == $reporting)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$reporting.'">'.$reporting.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO DATA ---</option>';
		}
		mysqli_close($db);
	}
	public function DoAuditLogs($log_date,$activity,$log_by,$db)
	{
		$column = "`log_date`,`activity`,`log_by`";	
		$insert = "'$log_date','$activity','$log_by'";
		$queryInsert = "INSERT INTO wms_audit_logs ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			echo $db->error;
		}
	}
	public function GetItemLocation($location,$db)
	{
		$query = "SELECT * FROM wms_item_location WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">-- ITEM LOCATION --</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$item_location = $ROW['item_location'];
				$selected = '';
				if($location == $item_location)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$item_location.'">'.$item_location.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO LOCATION ---</option>';
		}
		mysqli_close($db);
	}
	public function GetItemName($itemcode,$db)
	{
		$query = "SELECT * FROM wms_itemlist WHERE item_code='$itemcode'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['item_description'];
			}
	    } else {
	    	return 'No Item found on that Itemcode '. $itemcode;
	    }
	    mysqli_close($db);
	}
	public function GetTotalBranch($db)
	{
		$query = "SELECT * FROM tbl_branch";
		$results = $db->query($query);
		if($results->num_rows > 0)
	    {
		    return mysqli_num_rows($results);echo "A: ". $itemcode;
		} else {
		    return 0;
		}
		mysqli_close($db);
	}
	public function GetRemarksDateQuery($itemcode,$transdate,$column,$db)
	{
		$query = "SELECT * FROM wms_inventory_pcount WHERE item_code='$itemcode' AND trans_date='$transdate'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW[$column];
			}
	    } else {
	    	return '-';
	    }
	    mysqli_close($db);
	}
	public function GetAnyQuery($table,$column,$column_value,$query_col,$db)
	{
		$query = "SELECT * FROM $table WHERE $column='$column_value'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW[$query_col];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function GetUnitPriceRecords($itemcode,$year,$month,$db)
	{
		$query = "SELECT * FROM wms_inventory_records WHERE item_code='$itemcode' AND month='$month' AND year='$year'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['unit_price'];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function GetUnitPrice($itemcode,$db)
	{
		$query = "SELECT * FROM wms_itemlist WHERE item_code='$itemcode'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['unit_price'];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function CountBranch($cluster,$db)
	{
		$query = "SELECT * FROM tbl_branch WHERE location='$cluster'";
		$results = $db->query($query);			
	    $row_count = $results->num_rows;
	    if($results->num_rows > 0)
	    {
		    return $results->num_rows;
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function CountRows($table,$db)
	{
		$query = "SELECT id FROM $table WHERE active=1";
		$results = $db->query($query);			
	    $row_count = $results->num_rows;
	    if($results->num_rows > 0)
	    {
		    return $results->num_rows;
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function GetCluster($cluster,$db)
	{
		$query = "SELECT * FROM tbl_cluster ORDER BY cluster ASC";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">-- CLUSTER --</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$cluster_name = $ROW['cluster'];
				$selected = '';
				if($cluster == $cluster_name)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$cluster_name.'">'.$cluster_name.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO CLUSTER ---</option>';
		}
		mysqli_close($db);
	}
	public function GetBranch($branch,$db)
	{
		$query = "SELECT * FROM tbl_branch ORDER BY branch ASC";
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
			return $return;
		} else {
			return '<option value="">--- NO BRANCH ---</option>';
		}
		mysqli_close($db);
	}
	public function GetYear($year)
	{
		for($i=2023; $i <= 2030; $i++)
		{
			$yearsArray[] = $i;
		}
		$return = '';
		foreach($yearsArray as $years)
		{
			$selected='';
			if($years == $year)
			{
				$selected = 'selected';
			}
			$return .= '<option '.$selected.' value="'.$years.'">'.$years.'</option>'; 
		}
		return $return;
		mysqli_close($db);
	}
	public function GetWeekOfMonth($week)
	{
		// $stats = array("Week 1 (1-7)"=>"1","Week 2 (8-14)"=>"2","Week 3 (15-21)"=>"3","Week 4 (22-28)"=>"4","Week 5 (29-31)"=>"5","Monthly (1-31)"=>"0");
		$stats = array("Monthly (1-31)"=>"0");
        $return = "";
        foreach ( $stats as $key => $value )
        {
        	$selected = "";
        	if($value == $week)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	public function GetMonths($month)
	{
		$stats = array("January"=>"01","February"=>"02","March"=>"03","April"=>"04","May"=>"05","June"=>"06","July"=>"07","August"=>"08","September"=>"09","October"=>"10","November"=>"11","December"=>"12");
        $return = "";
        foreach ( $stats as $key => $value )
        {
        	$selected = "";
        	if($value == $month)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	public function GetDays($day,$day_cnt)
	{
		$values = array();
		for ($i = 1; $i <= 31; $i++) {
		    $value = str_pad($i, 2, "0", STR_PAD_LEFT);
		    $values[] = $value;
		}
		foreach ( $values as $key => $value )
		{
			$selected = "";
        	if($value == $day)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$value.'</option>';  
		}
		return $return;
		mysqli_close($db);
	}
	public function getWMSSubReport($table,$submenu,$db)
	{
		$query = "SELECT * FROM wms_reporting_option_sub WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$menu_name = $ROW['reporting_sub'];
				$value = $ROW['page_name'];
				$selected = '';
				if($value == $submenu)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$value.'">'.$menu_name.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NOTHING TO REPORT ---</option>';
		}
		mysqli_close($db);
	}
	public function getWMSReport($table,$db)
	{
		$query = "SELECT * FROM wms_reporting_option WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT MODULE ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$menu_name = $ROW['reporting_option'];
				$value = $ROW['table_name'];
				$selected = '';
				if($value == $table)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$value.'">'.$menu_name.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NOTHING TO REPORT ---</option>';
		}
		mysqli_close($db);
	}
	public function GetOnHand($itemcode,$db)
	{
		$query = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$stock = $ROW['stock_in_hand'];
			}
			return $stock;
		} else {
			return '0';
		}
		mysqli_close($db);
	}
	public function GetDrNumber($db)
	{
		$query = "SELECT * FROM wms_form_numbering WHERE id=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$numbering = $ROW['dr_number'];
			}
			return $numbering;
		} else {
			return 'Numbering is not configured';
		}
		mysqli_close($db);
	}
	public function GetOrderStatus($control_no,$column,$db)
	{
		$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$retVal = $ROW[$column];
			}
			return $retVal;
		}
		mysqli_close($db);
	}
	public function getOrderRemarks($mrs_no,$column,$db)
	{
		$QUERY = "SELECT * FROM wms_branch_order_remarks WHERE control_no='$mrs_no'";
//		return $QUERY;
//		exit();
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				return $ROW[$column];
			}
		} else {
			return "";
		}
		mysqli_close($db);
	}
	public function shortenText($text, $maxLength)
	{
		if (strlen($text) <= $maxLength)
		{
			return $text;
		} else {
		    $shortenedText = substr($text, 0, $maxLength);
		    $shortenedText .= '...';
		    return $shortenedText;
		}
		mysqli_close($db);
	}
	public function GetRequestStatus($status)
	{
		$stats = array(
	        "Open" => "Open",
            "Closed" => "Closed",
            "Cancelled" => "Cancelled"
        );
        $return = "";
        foreach ( $stats as $key => $value )
        {
        	$selected = "";
        	if($value == $status)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	public function GetPriority($priority)
	{
        $priority = array(
	        "Normal" => "Normal",
            "Urgent" => "Urgent"                        
        );
        foreach ( $priority as $key => $value )
        {
        	$selected = "";
        	if($value == $transtype)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	
	public function GetLocation($item_location,$db)
	{
		$query = "SELECT * FROM wms_item_location WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT ITEM LOCATION ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$location = $ROW['item_location'];
				$selected = '';
				if($location == $item_location)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$location.'">'.$location.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- Recipient Not Configured ---</option>';
		}
		mysqli_close($db);
	}
	public function GetRecipient($recipient,$db)
	{
		$query = "SELECT * FROM wms_recipient WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT RECIPIENT ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$homopient = $ROW['recipient'];
				$selected = '';
				if($homopient == $recipient)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$homopient.'">'.$homopient.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- Recipient Not Configured ---</option>';
		}
		mysqli_close($db);
	}
	public function GetMRSNumber($db)
	{
		$query = "SELECT * FROM wms_form_numbering WHERE id=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$numbering = $ROW['mrs_number'];
			}
			return $numbering;
		} else {
			return 'Numbering is not configured';
		}
		mysqli_close($db);
	}
	public function GetTransType($transtype)
	{
        $transaction = array(
	        "Receiving" => "Receiving",
            "Return" => "Return"                        
        );
        $return = '';
        foreach ( $transaction as $key => $value )
        {
        	$selected = "";
        	if($value == $transtype)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}

	public function GetDeliverytatust($status)
	{
        $delstatus = array(
	        "Full" => "Full",
            "Partial" => "Partial"                        
        );
        foreach ( $delstatus as $key => $value )
        {
            $return .= '<option value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	public function GetOrderStatust($status)
	{
        $ordstatus = array(
	        "Full" => "Full",
            "Partial" => "Partial"                        
        );
        $return = '';
        foreach ( $ordstatus as $key => $value )
        {
            $return .= '<option value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
        mysqli_close($db);
	}
	public function GetSupplierName($supplier_id,$db)
	{
		$query = "SELECT * FROM wms_supplier WHERE id='$supplier_id'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$name = $ROW['name'];
			}
			return $name;
		} else {
			return 'Supplier not exists';
		}
		mysqli_close($db);
	}
	public function GetItemInfo($column,$itemcode,$db)
	{
		$query = "SELECT * FROM wms_itemlist WHERE item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW[$column];
			}
		} else {
			return NULL;
		}
		mysqli_close($db);
	}
	public function GetSupplier($supplier_id,$db)
	{
		$query = "SELECT * FROM wms_supplier";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT SUPPLIER ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$name = $ROW['name'];
				$value = $ROW['id'];
				$selected = '';
				if($value == $supplier_id)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$value.'">'.$name.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO SUPPLIER ---</option>';
		}
		mysqli_close($db); 
	}
	public function GetUOM($uom,$db)
	{
		$query = "SELECT * FROM wms_units_measures";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT UOM ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$units = $ROW['unit_name'];
				$selected = '';
				if($units == $uom)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$units.'">'.$units.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO UOM ---</option>';
		}
		mysqli_close($db);
	}
	public function GetItemCategory($cat,$db)
	{
		$query = "SELECT * FROM wms_item_category WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT CATEGORY ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$category = $ROW['category'];
				$selected = '';
				if($category == $cat)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$category.'">'.$category.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO CATEGORY ---</option>';
		} 
		mysqli_close($db);
	}
	public function GetRowLimit($showlimit)
	{
        $limit = array(            
	        "25" => "25",            
            "50" => "50",            
            "100" => "100",
			"250" => "250",
			"500" => "500",
			"1000" => "1000"
        );
        $return = '';
        foreach ( $limit as $key => $value )
        {
        	$selected = "";
        	if($value == $showlimit)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function limitString($string, $limit)
	{
		if (strlen($string) > $limit) {
		    $string = substr($string, 0, $limit) . "...";
		}
		return $string;
	}
	public function checkPolicy($username,$module,$permission,$user_level,$db)
	{
		if($user_level >= 80)
		{
			return 1;
		} 
		else
		{
			$checkPolicy = "SELECT * FROM tbl_system_permission WHERE username='$username' AND modules='$module' AND $permission=1";
			$pRes = mysqli_query($db, $checkPolicy);    
		    if ( $pRes->num_rows > 0 ) 
		    {
		    	return 1;
			} else {
				return 0;
			}
		}
	}
}