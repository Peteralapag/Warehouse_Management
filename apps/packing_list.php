<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
require_once $_SERVER['DOCUMENT_ROOT']."/Plugins/dompdf/autoload.inc.php";
$trans_date = $_GET['trans_date'];
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$RMShtml = '';
$RMShtml .='
<style>
body {margin: 0;padding: 0;}
.packing-wrapper {width: 7.5in;height:6in;border: 1px solid #f1f1f1;margin-bottom:10px;padding:0.25in;overflow:auto;}
.lamesa td {padding: 3px !important;font-size: 13px;border: none !important;}
</style>
	<div style="font-size:18px; text-align:center; margin-bottom:10px;font-weight:600">PICK LIST</div>
';
	$chkQuery = "
		SELECT pl.*
		FROM wms_picklist pl
		WHERE EXISTS (
		    SELECT 1
		    FROM wms_branch_order bo
		    WHERE pl.control_no = bo.control_no
		    AND pl.trans_date = bo.trans_date
		    AND pl.trans_date = '$trans_date'		    
		)
		GROUP BY pl.control_no
	";
	$chkResults = mysqli_query($db, $chkQuery);	
	if (mysqli_num_rows($chkResults) > 0)
	{
	    while ($ROWS = mysqli_fetch_assoc($chkResults))
	    {	    
			$control_no = $ROWS['control_no'];
			$branch = $ROWS['branch'];
$RMShtml .='
				<div style="border-bottom-style:dotted; border-color: grey; margin-bottom: 5px;padding-bottom:10px ">
				<div style="margin-bottom:10px">	<span style="font-weight:600;font-size:15px">'.$branch.'</span> | </span> <span style="font-size:15px">MRS No: '.$control_no.'</span></div>
';
		/* ####################################################################################################### */			
			$query = "SELECT * FROM wms_picklist WHERE control_no='$control_no' AND trans_date='$trans_date'";
			$results = mysqli_query($db, $query);
			if (mysqli_num_rows($results) > 0) {
			    $count = 0;
$RMShtml .='	    
				<table style="width:100%;margin-bottom:10px" class="lamesa">
';
			    while ($row = mysqli_fetch_assoc($results))
			    {			    
			        if ($count % 2 === 0) 
			        { 
$RMShtml .='			  <tr>';
			        }			        
$RMShtml .='
			        <td>'.$row['item_description'].' ['.$row['quantity'].' '.$row['uom'].'] ______</td>
';
			        $count++;			        
			        if ($count % 2 === 0 || $count === mysqli_num_rows($results)) {
$RMShtml .='	            </tr>';
			        }
			    }			    
$RMShtml .='
			    	</table>
			    	<hr style="border-style:dotted">
			    	<div style="margin-top:5px;font-size:14px"><strong>Remarks:</strong> '.$function->getOrderRemarks($control_no,"remarks",$db).'</div>
			    	<hr style="border-style:dotted">
			    	<div style="text-align:right">
			    		<span style="font-size:15px">Prepared By: _______________________________ Date ________________</span>
			    	</div>
			    	</div>
';			
			}
    	/* ####################################################################################################### */			
	    }
	} else {
$RMShtml .= '<div style="text-align:center;margin-top:50px">Nothing to see here for now</div>'	;
	}
$date_me_now = date('Now');
$dompdf->set_option('dpi', 120);
$dompdf->loadHtml($RMShtml);
$dompdf->setPaper('Letter', 'portrait', 'no-margin');
$dompdf->render();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="picklist_'.$date_me_now.'.pdf"');
header('Content-Length: ' . strlen($dompdf->output()));
echo $dompdf->output();
echo '
	<script>
		rms_reloaderOff();
	</script>
';
