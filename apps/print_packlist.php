<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Plugins/dompdf/autoload.inc.php";
$function = new WMSFunctions;
$control_no = $_REQUEST['control_no'];
$preparator = $_REQUEST['preparator'];
use Dompdf\Dompdf;
$dompdf = new Dompdf();

$html = '
	<div style="font-size:13px;font-family:"Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;margin-bottom:20px;">
		<strong>BRANCH:</strong>' . $function->GetOrderStatus($control_no,'branch',$db) .' ::: 
		<strong>DATE PREPARED:</strong> '. date("F d, Y @ h:m A") .'
	</div>
	<div style="height:10px;"></div>
	<div style="font-size:13px;font-family:"Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;margin-bottom:10px;">
		<strong>PREPARED BY:</strong> '.$preparator.'
	</div>
	<hr>
	<div style="display: flex">
   <table style="width:100%;font-size:12px;font-style:sego-ui">
';
			$query = "SELECT * FROM wms_branch_order WHERE control_no='$control_no'";
			$result = mysqli_query($db, $query);
			if (mysqli_num_rows($result) > 0) {
			    $count = 0;
			    while ($row = mysqli_fetch_assoc($result))
			    {			    
			        if ($count % 2 === 0)
			        {
$html  .= 		        '<tr>'; 
			        }			        
$html .= '
			        <td style="position:relative;padding:3px">
			        	<span>'.$row['item_description'].'</span> | <span style="color:blue">'.$row['quantity'].'-'.$row['uom'].'</span>
			        	<div style="position: absolute; right:10px;top:18px;border-bottom:1px solid #000;width:80px;"></span>
			        </td>
';			        
			        $count++;			        
			        if ($count % 2 === 0 || $count === mysqli_num_rows($result)) {
$html .=				'</tr>';
			        }
			    }			    
			} else {
$html .= 		'No rows found.';
			}			  
			mysqli_close($db);
$html .= '
	    </table>			
	</div>
';
// echo $html;
$dompdf->loadHtml($html);
$paperWidth = 8.5 * 72; // Letter width in points (72 points per inch)
$paperHeight = 5.7 * 72; // Custom height in points (72 points per inch)
$dompdf->setPaper($paperWidth, $paperHeight);
//$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="document.pdf"');
header('Content-Length: ' . strlen($dompdf->output()));
echo $dompdf->output();
