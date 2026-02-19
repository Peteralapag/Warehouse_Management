<?php
class WMSStoreFunctions
{
	public function GetReportTarget($target)
	{
		$_target = array("RAWMATS"=>"RAWMATS","FINISH GOODS"=>"FINISH GOODS");
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
	public function GenerateRawmatsSumData($year,$month,$branch,$branch_data,$conn)
	{
	    $date_range = $year."-".$month;
		$rmQueryS = "SELECT * FROM store_rm_summary_data WHERE DATE_FORMAT(`report_date`, '%Y-%m')='$date_range' AND  `branch`='$branch'";
		$RESULTAS = mysqli_query($conn, $rmQueryS);				
		$data=array();$total_stock_in = 0;$total_receiving_in=0;$total_transfer_in=0;$total_beginning=0;$total_stock_out=0;$total_actual_count=0;			
		if (mysqli_num_rows($RESULTAS) > 0)
		{
		    while ($ROWS = mysqli_fetch_array($RESULTAS))
		    {
		    	$report_date = $ROWS['report_date'];
		        $total_stock_in += $ROWS['stock_in'];
		        $total_receiving_in += $ROWS['receiving_in'];
		        $total_transfer_in += $ROWS['transfer_in'];
		        $total_beginning += $ROWS['beginning'];
		
		        $total_in = $total_stock_in + $total_receiving_in + $total_transfer_in; // Calculate total in loop
		
		        $branch_data[] = [
		            'branch' => $branch,
		            'trans_date' => $date_range,
		            'report_date' => $report_date,
		            'beginning' => $total_beginning,
		            'stock_in' => $total_in,
		            'transfer_out' => $total_stock_out += $ROWS['transfer_out'], // Update total_stock_out directly
		            'ending' => $total_actual_count += $ROWS['actual_count'] // Update total_actual_count directly
		        ];
		    }
		} else {
		    echo "Something is Wrong";
		}
	}
	public function GetRawmatsSumData($column, $year, $month, $branch, $db)
	{
		$date_range = $year."-".$month;
		$rmQueryS = "SELECT $column FROM wms_report_cache WHERE branch='$branch' AND report_date='$date_range'";
		$RESULTAS = mysqli_query($db, $rmQueryS);				
		if (mysqli_num_rows($RESULTAS) > 0)
		{
		    while ($ROWS = mysqli_fetch_array($RESULTAS))
		    {
		    	$col = $ROWS[$column];
		    }
		    return $col;
		} else {
		    return 0;
		}
	}
	public function GetStoreUnitPrice($item_id,$conn)
	{
		$query = "SELECT * FROM store_items WHERE id='$item_id'";
		$results = $conn->query($query);			
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
}
