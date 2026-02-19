<?php
class WMSInventory
{
	public function GetBranchOrderTotal($branch, $month, $year, $db)
	{
		$date_range = $year."-".$month;
		$sqlQueryIR = "SELECT * FROM wms_branch_order WHERE branch='$branch' AND class='OS' AND DATE_FORMAT(delivery_date, '%Y-%m') = '$date_range'";
		$irResults = mysqli_query($db, $sqlQueryIR);
		if (mysqli_num_rows($irResults) > 0) {
			$return = 0;
		    while ($IRVROW = mysqli_fetch_array($irResults))
		    {
		      	$return = $IRVROW['actual_quantity'];
		      	return $return."<br>";
		    }		    
		} 
		else
		{
			return 0;
		} 
	   /* $date_range = $year . "-" . $month;
	    $sqlQueryIR = "SELECT SUM(wil.unit_price * wbo.actual_quantity) AS total_price 
	                   FROM wms_branch_order wbo
	                   INNER JOIN wms_itemlist wil ON wbo.item_code = wil.item_code
	                   WHERE wbo.branch='$branch' 
	                   AND wbo.class='OS' 
	                   AND DATE_FORMAT(wbo.delivery_date, '%Y-%m') = '$date_range'";
	
	    $irResults = mysqli_query($db, $sqlQueryIR);
	
	    if ($irResults) {
	        $result_row = mysqli_fetch_assoc($irResults);
	        $total_price = $result_row['total_price'];
	        mysqli_free_result($irResults);
	        return $total_price;
	    } else {
	        return 0;
	    } */
	}
	public function GetBranchOrder($branch,$month,$year,$class,$db)
	{
		$date_range = $year."-".$month;
		$sqlQueryIR = "SELECT * FROM wms_branch_order WHERE branch='$branch' AND class='$class' AND (DATE_FORMAT(delivery_date, '%Y-%m') = '$date_range')";
		$irResults = mysqli_query($db, $sqlQueryIR);
		if (mysqli_num_rows($irResults) > 0) {
			$return = 0;
		    while ($IRVROW = mysqli_fetch_array($irResults))
		    {
		      	$return += $IRVROW['actual_quantity'];
		    }
		    return $return;
		} 
		else
		{
			return 0;
		} 
	}	
	public function GetAveragePrice($item_code,$month,$year,$db)
	{
		$sqlQueryIR = "SELECT * FROM wms_inventory_records WHERE item_code='$item_code' AND month='$month' AND year='$year'";
		$irResults = mysqli_query($db, $sqlQueryIR);
		if (mysqli_num_rows($irResults) > 0) {
		    while ($IRVROW = mysqli_fetch_array($irResults))
		    {
		      	return $IRVROW['unit_price'];
		    }
		 } else {
		   return 0;
		}	
	}
	public function getWHTotalOut($item_code,$month,$year,$db)
	{
		$total=0;
		$sqlQueryIR = "SELECT * FROM wms_inventory_records WHERE item_code='$item_code' AND month='$month' AND year='$year'";
		$irResults = mysqli_query($db, $sqlQueryIR);
		if (mysqli_num_rows($irResults) > 0) {
		    while ($IRVROW = mysqli_fetch_array($irResults))
		    {
		      	for ($x = 1; $x <= 31; $x++) {
			        $td = str_pad($x, 2, '0', STR_PAD_LEFT);
			        $day = $IRVROW['day_' . $td];
			        $total += $day;
			    }
		    }
		    return $total;
		 } else {
		   return 0;
		}	
	}
	public function GetUndoStatus($trans_date,$itemcode,$db)
	{
		$query = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode' AND stock_before_pcount_date='$trans_date'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			return 1;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetPcountData($itemcode,$trans_date,$column,$db)
	{
		$query = "SELECT * FROM wms_inventory_pcount WHERE item_code='$itemcode' AND trans_date='$trans_date'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
                $col = $ROW[$column];
			}
			return $col;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetExpirationDate($itemcode,$db)
	{
		$query = "SELECT * FROM wms_inventory_pcount WHERE item_code='$itemcode' AND trans_date='$transdate'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
                $qty = $ROW['p_count'];
			}
			return $qty;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetPcount($itemcode,$transdate,$db)
	{
		$query = "SELECT * FROM wms_inventory_pcount WHERE item_code='$itemcode' AND trans_date='$transdate'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
                $qty = $ROW['p_count'];
			}
			return $qty;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetMonthlyPcount($cnt_start,$cnt_end,$days_cnt,$itemcode,$month,$year,$db)
	{
		$startDate = $year."-".$month."-".$cnt_start;
		$endDate = $year."-".$month."-".$cnt_end;
		$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date BETWEEN '$startDate' AND '$endDate' AND item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$total=0;
		    while($ROW = mysqli_fetch_array($results))  
			{
				$total = $ROW['p_count'];	
			}
			return $total;
		} else {
			return 0;
		}

	}
	public function GetWeeklyPcount($cnt_start,$cnt_end,$days_cnt,$itemcode,$month,$year,$db)
	{
		$startDate = $year."-".$month."-".$cnt_start;
		$endDate = $year."-".$month."-".$cnt_end;
						
		$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date BETWEEN '$startDate' AND '$endDate' AND item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$total=0;
		    while($ROW = mysqli_fetch_array($results))  
			{
				$total += $ROW['p_count'];	
			}
			return $total;
		} else {
			return 0;
		}

	}
	public function getMonthlyIn($week,$itemcode,$month,$year,$db)
	{
	    // Define week ranges
	    $ranges = [
	        1 => [1,7],
	        2 => [8,14],
	        3 => [15,21],
	        4 => [22,28],
	        5 => [29,31],
	    ];
	
	    if (!isset($ranges[$week])) {
	        return 0;
	    }
	
	    [$cnt_start, $cnt_end] = $ranges[$week];
	
	    $startDate = sprintf("%04d-%02d-%02d", $year, $month, $cnt_start);
	    $endDate   = sprintf("%04d-%02d-%02d", $year, $month, $cnt_end);
	
	    $query = "
	        SELECT SUM(details.quantity_received) AS total
	        FROM wms_receiving_details details
	        INNER JOIN wms_receiving receiving
	            ON receiving.receiving_id = details.receiving_id
	        WHERE details.received_date BETWEEN '$startDate' AND '$endDate'
	        AND details.item_code = '$itemcode'
	        AND receiving.status = 'Closed'
	    ";
	
	    $result = mysqli_query($db, $query);
	    $row = mysqli_fetch_assoc($result);
	    return $row['total'] ?? 0;
	}
	public function getWeeklyIn($cnt_start,$cnt_end,$days_cnt,$itemcode,$month,$year,$db)
	{
		
		$startDate = $year."-".$month."-".$cnt_start;
		$endDate = $year."-".$month."-".$cnt_end;
		
		$query = "SELECT * FROM wms_receiving_details WHERE received_date BETWEEN '$startDate' AND '$endDate' AND item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$total=0;
		    while($ROW = mysqli_fetch_array($results))  
			{
				$total += $ROW['quantity_received'];	
			}
			return $total;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetInventoryOut($branch,$itemcode,$year,$month,$day,$db)
	{
		$trans_date = $year."-".$month."-".$day;
		$query = "SELECT actual_quantity FROM wms_branch_order WHERE branch='$branch' AND item_code='$itemcode' AND trans_date='$trans_date'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
                $qty = $ROW['actual_quantity'];
			}
			return $qty;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function getUpdateEnding($itemcode,$ending,$month,$year,$db)
	{
	    $queryDataUpdate = "UPDATE wms_inventory_records SET ending=? WHERE item_code=? AND year=? AND month=?";
	    $stmt = $db->prepare($queryDataUpdate);
	    $stmt->bind_param("ssss", $ending, $itemcode, $year, $month);	   
	 	
	    if ($stmt->execute()) {
	    } else {
	        return $stmt->error;
	    }
	    
	    $stmt->close();
	}
	public function getUpdateBeginning($itemcode,$beginning,$month,$year,$db)
	{
	    $queryDataUpdate = "UPDATE wms_inventory_records SET beginning=? WHERE item_code=? AND year=? AND month=?";
	    $stmt = $db->prepare($queryDataUpdate);
	    $stmt->bind_param("ssss", $beginning, $itemcode, $year, $month);	   
	 	
	    if ($stmt->execute()) {
	    } else {
	        echo $stmt->error;
	    }
	    
	    $stmt->close();
	}
	public function getDailyBeginning($itemcode,$month,$year,$day,$db)
	{
		$Walang_Ka_Date = $year."-".$month."-".$day;
		$date = new DateTime($Walang_Ka_Date);
		$date->modify('-1 days');
		$Year = $date->format('Y');
		$Month = $date->format('m');
		$Day = $date->format('d');
		$trans_date = $Year."-".$Month."-".$Day;
		$str_day = "day_".$Day;
		
		$query = "SELECT * FROM wms_inventory_pcount WHERE item_code='$itemcode' AND trans_date='$trans_date'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
                $pcount = $ROW['p_count'];
			}
			return $pcount;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function getInventoryBeginning($cnt_start,$cnt_end,$days_cnt,$col,$itemcode,$month,$year,$db)
	{
		$Walang_Ka_Date = $year . "-" . $month . "-01"; // Always include the day for proper DateTime handling
		$date = new DateTime($Walang_Ka_Date);

		$date->modify('-1 month');
		$Year = $date->format('Y');
		$BackMonth = $date->format('m');
		$startDate = $Year . "-" . $BackMonth . "-01";
		$endDate = $Year . "-" . $BackMonth . "-" . $date->format('t'); // 't' gives the number of days in the month
		
		$startDateTime = new DateTime($startDate);
		$endDateTime = new DateTime($endDate);		
//		return $startDate . " -- " . $endDate . "<br>";
/*
		$lastWeekDays = $endDateTime->format('N');		
		$startDateTime->sub(new DateInterval('P1W'));		
		$endDateTime->sub(new DateInterval('P1W' . $lastWeekDays . 'D'));		
		$newStartDate = $startDateTime->format('Y-m-d');
		$newEndDate = $endDateTime->format('Y-m-d');;
				
*/

		$query = "SELECT * FROM wms_inventory_pcount WHERE trans_date BETWEEN '$startDate' AND '$endDate' AND item_code='$itemcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$total=0;
		    while($ROW = mysqli_fetch_array($results))  
			{
				$total = $ROW['p_count'];	
			}
				return $total;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function getMonthlyBeginning($itemcode,$month,$year,$db)
	{
		$Walang_Ka_Date = $year."-".$month;
		$date = new DateTime($Walang_Ka_Date);
		$date->modify('-1 month');
		$Year = $date->format('Y');
		$Month = $date->format('m');

		$query = "SELECT * FROM wms_inventory_records WHERE item_code='$itemcode' AND month='$Month' AND year='$Year'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$total=0;
	            for ($x = 1; $x <= 31; $x++)
	            {
	                $day = $ROW['day_' . $x];
	                $total += $day;
	            }
			}
			return $total;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function removeStringFromArray($arr, $stringToRemove)
	{
	    return array_filter($arr, function ($item) use ($stringToRemove) {
	        return $item !== $stringToRemove;
	    });
	}
	public function getColumns($tableName,$db)
	{
		$sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
		$stmt = mysqli_prepare($db, $sql);
		if ($stmt)
		{
		    $databaseName = 'documents_data';
		    mysqli_stmt_bind_param($stmt, 'ss', $databaseName, $tableName);
		
		    mysqli_stmt_execute($stmt);
		    mysqli_stmt_bind_result($stmt, $columnName);
		    $columns = array();
		    while (mysqli_stmt_fetch($stmt)) {
		    	if($tableName == 'wms_inventory_records')
		    	{
			    	if($columnName != 'wid_id' && $columnName != 'supplier_id')
			    	{
			        	$columns[] = $columnName;
			        }
			    } else {
				    $columns[] = $columnName;
			    }
		    }
		    return $columns;
		} else {
		    echo "Error preparing the statement: " . mysqli_error($db);
		}
		mysqli_close($db);
	}
	public function getInventorySelection($inventory,$db)
	{
		$stats = array(
	        "Inventory Status" => "Inventory Status",
            "Inventory" => "Closed",
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
	public function GetLeadTime($lead_time,$db)
	{
		$query = "SELECT * FROM wms_inventory_leadtime";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$name = $ROW[$lead_time];
			}
			return $name;
		} else {
			return 0;
		} 
		mysqli_close($db);
	}
	public function GetDailyInventory($kwiri,$eVal,$days_count,$min_leadtime,$max_leadtime,$db)
	{
		$sqlQueryRecords = "SELECT * FROM wms_inventory_records";
		$invResults = mysqli_query($db, $sqlQueryRecords);    
	    if ( $invResults->num_rows > 0 ) 
	    {	    	
	    	while($INVENTORYROW = mysqli_fetch_array($invResults))  
			{
		    	$max_average = array();$average=0;$rol=0;
				for($x = 1; $x <= $days_count; $x++)
				{
					if($INVENTORYROW['day_'.$x] > 0)
					{
						$average += $INVENTORYROW['day_'.$x];
						$max = $INVENTORYROW['day_'.$x];					
					}
					$max_average[] = $max;
									$average_dr = round($average / $days_count);			
				$max_dr = max($max_average);			
				$safety_stocks = $max_dr * $max_leadtime - $average_dr * $min_leadtime;			
				$rol = $average_dr * $min_leadtime + $safety_stocks;

				}				
			}				
	    } else {
	    	$rol = 0;
	    }
	    if($eVal == 'rol')
	    {
	    	return $rol;
	    }
	    mysqli_close($db);
	}
}
