<?php
//error_reporting();
require_once '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require_once '../con_init.php';
$conn = new mysqli(CONN_HOST, CONN_USER, CONN_PASSWORD, CONN_NAME);
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
}
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.store_functions.php";
$function = new WMSFunctions;
$storeFunction = new WMSStoreFunctions;

if(isset($_POST['mode'])) {
    $mode = $_POST['mode'];
} else {
    print_r('
        <script>
            app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
        </script>
    ');
    exit();
}

if(isset($_SESSION['wms_appnameuser'])) {
    $app_user = strtolower($_SESSION['wms_appnameuser']);
    $app_user = ucwords($app_user);
}

$date = date("Y-m-d");
$date_time = date("Y-m-d H:i:s");

if($mode == 'clearcache')
{
	$year = $_POST['year'];
    $month = $_POST['month'];
	$date_range = $year."-".$month;
	if($_POST['cache'] == 'current')
	{
		$q = "WHERE report_date='$date_range' AND user='$app_user'";
	} 
	else if($_POST['cache'] == 'all')
	{
		$q = "WHERE user='$app_user'";
	} 	
	$qDelete = "DELETE FROM wms_report_cache $q";	
	if ($db->query($qDelete) === TRUE)
	{
		$deyt = date("F Y", strtotime($date_range));
		echo '
			<script>
				swal("Success","The '.$deyt.' has been cleared","success");
				loadReport();
			</script>
		';
	} 
	else {echo $db->error;}
	
}
if($mode == 'generaterawmatsdata')
{
    $target = $_POST['target'];
    $year = $_POST['year'];
    $month = $_POST['month'];
    $date_range = $year."-".$month;

	$QueryCheckBranch = "SELECT * FROM wms_report_cache WHERE report_date='$date_range'";
    $branchResults = mysqli_query($db, $QueryCheckBranch); 
    if ($branchResults->num_rows > 0)
    {
    	
    	$deyt = date("F Y", strtotime($date_range));
    	echo '
    		<script>
    			swal("System Message","The '.$deyt.' already exist. Clear it and regenerate it again","warning");
    		</script>
    	';
    	exit();
	}

    $data = array(); // Initialize the data array to hold data for all branches
    $QueryBranch = "SELECT * FROM tbl_branch";
    $branchResults = mysqli_query($db, $QueryBranch); 
    if ($branchResults->num_rows > 0)
    {
    	$p=0;$total_amount=0;
        while($BROWS = mysqli_fetch_array($branchResults)) {
            $p++;
            $branch = $BROWS['branch'];
            $rmQueryS = "
            	SELECT * FROM store_rm_summary_data RMD
				INNER JOIN store_items SI
				ON RMD.item_id = SI.id
				WHERE DATE_FORMAT(RMD.report_date, '%Y-%m')='2024-03' AND  RMD.branch='$branch'
			";
            $RESULTAS = mysqli_query($conn, $rmQueryS);                
			
			$total_stock_in = 0;
            $total_receiving_in = 0;
            $total_transfer_in = 0;
            $total_beginning = 0;
            $total_stock_out = 0;
            $total_actual_count = 0;
            $total_in = 0;

            $stock_in_amount = 0;
            $beginning_amount = 0;
            $transfer_out_amount = 0;
            $ending_amount = 0;
            
            if (mysqli_num_rows($RESULTAS) > 0) {
                while ($ROWS = mysqli_fetch_array($RESULTAS))
                {
                	$item_id = $ROWS['item_id'];
                    $total_stock_in += $ROWS['stock_in'];
                    $total_receiving_in += $ROWS['receiving_in'];
                    $total_transfer_in += $ROWS['transfer_in'];
                    $total_beginning += $ROWS['beginning']; // Add to total beginning
                    $total_in = $total_stock_in + $total_receiving_in + $total_transfer_in; // Calculate total in loop                    
                    $total_stock_out += $ROWS['transfer_out'];
                    $total_actual_count += $ROWS['actual_count'];                    

                	$unit_price = $ROWS['unit_price'];
                	
                	$beginning_amt = $ROWS['beginning'] * $unit_price;
                	$stock_in_amt = $ROWS['stock_in'] + $ROWS['stock_in'] + $ROWS['transfer_in'] * $unit_price;
                	$stock_out_amt = $ROWS['transfer_out'] * $unit_price;
                	$ending_amt = $ROWS['actual_count'] * $unit_price;
                	
                	$beginning_amount += $beginning_amt;
                	$stock_in_amount += $stock_in_amt;
                	$transfer_out_amount += $stock_out_amt;
                	$ending_amount += $ending_amt;                	
                }
            } 
                  
	        $data[] = [
	        	'user' => $app_user,
	        	'date_created' => $date,
	            'branch' => $branch,
	            'trans_date' => $date_range,
	            'beginning' => $total_beginning,
	            'stock_in' => $total_in,
	            'transfer_out' => $total_stock_out,
	            'ending' => $total_actual_count,	            
	            'beginning_amount' => $beginning_amount,
	            'stock_in_amount' => $stock_in_amount,
	            'transfer_out_amount' => $transfer_out_amount,
	            'ending_amount' => $ending_amount,
	        ];
		}
		
        if (!empty($data))
        {
        	saveJson($data,$date_range,$db);
        } else {
            echo "No data to save.";
        }
    } else {
        echo "NO DATA";
    }
}

function saveJson($data,$date_range,$db)
{
	$values = [];
	foreach ($data as $item) {
	    $values[] = "(
	    	'" . $item['user'] . "',
	    	'" .$item['date_created']. "',
	    	'" . $item['branch'] . "',
	    	'" . $item['trans_date'] . "',
	    	" . $item['beginning'] . ",
	    	" . $item['stock_in'] . ",
	    	" . $item['transfer_out'] . ",
	    	" . $item['ending'] . ",
	    	" . $item['beginning_amount'] . ",
	    	" . $item['stock_in_amount'] . ",
	    	" . $item['transfer_out_amount'] . ",
	    	" . $item['ending_amount'] . "
	    )";
	}	
	$query = "INSERT INTO wms_report_cache (`user`,`date_created`,`branch`,`report_date`,`beginning`,`stock_in`,`transfer_out`,`ending`,`beginning_amount`,`stock_in_amount`,`transfer_amount`,`ending_amount`)";
	$query .= " VALUES " . implode(', ', $values);
	
	if ($db->query($query) === TRUE)
	{
		$deyt = date("F Y", strtotime($date_range));
		echo '
    		<script>
    			swal("System Message","The report cache now contains the '.$deyt.' information.","success");
    			loadReport();
    		</script>
    	';
    	exit();    
	} else {
	    echo $db->error;
	}
	mysqli_close($db);
}
mysqli_close($conn);
?>
