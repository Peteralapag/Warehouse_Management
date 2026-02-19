<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Plugins/dompdf/autoload.inc.php";
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$function = new WMSFunctions;
$control_no = $_REQUEST['control_no'];
$params = "";
$border = '';
if( $function->GetOrderStatus($control_no,'delivery_date',$db) == '' )
{
	$date = "";
} else {
	$date = date("F d, Y", strtotime($function->GetOrderStatus($control_no,'delivery_date',$db)));
}
$RMShtml ='
<style>
@page {margin: 0; padding:0; size: letter}
</style>
';
	$page = '';
	$sqlPageCnt = "SELECT * FROM wms_branch_order WHERE control_no='$control_no' AND cancelled=0";
	$result = mysqli_query($db, $sqlPageCnt);
	$totalRows = mysqli_num_rows($result);
	$itemsPerDiv = 10;
	$totalDivs = ceil($totalRows / $itemsPerDiv);	
	for ($divNumber = 1; $divNumber <= $totalDivs; $divNumber++)
	{
		$startFrom = ($divNumber - 1) * $itemsPerDiv;
$RMShtml .='
<div style="height:13cm;border-bottom-style:;position:relative;last-child:border:0;;font-family:Arial, sans-serif">
	<div style="width: 8in;height:5in;margin-left:0.25in;margin-top: 0.25in;page-break-inside: void">
		<div>
			<table style="width: 100%;border-collapse:collapse;background:dd1021;border:1px #000;height:30px">
			   <tr>
				   <td style="padding-left:10px;width:40px">
				   		<span><img alt="" src="../images/truck.png"></span>
				   </td>
				   <td style="width:5px">&nbsp;</td>
				   <td style="color:#000;font-weight:600">Rose Bakeshop</td>
				   
				   <td style="text-align:right;color:#000;width:100px" valign="middle">
';				   
					if($totalRows > $itemsPerDiv)
					{
$RMShtml .='			<span style="font-size:16px;margin-right:20px;line-height:30px">P - '.$divNumber.'</span>';
					}
$RMShtml .='					
				   </td>
			   </tr>
		   </table>
		</div>
		<div style="padding: 7px;text-align:center;border:1px #d9d9d9;color:#0f243f;font-size: 16px;padding:5px;letter-spacing:10px">DELIVERY RECEIPT</div>
		<div class="dr-info">			
			<table style="width: 100%">
				<tr style="font-family: Verdana, Geneva, Tahoma, sans-serif;font-size:16px">
					<td style="width:100px;font-weight:600;white-space:nowrap;padding:2px">Delivery Date: </td>
					<td style="border-bottom:1px solid #232323;padding:2px">'.$date.'</td>
					<td style="width:1in;text-align:right;font-weight:600;white-space:nowrap;padding:2px">Control No.: </td>
					<td style="border-bottom:1px solid #232323;width: 1in;color:red;text-align:center;padding:2px">'.$function->GetOrderStatus($control_no,'dr_number',$db).'</td>
				</tr>
				<tr style="font-family: Verdana, Geneva, Tahoma, sans-serif;font-size:16px">
					<td style="width:100px;font-weight:600;white-space:nowrap;padding:2px">Branch: </td>
					<td style="border-bottom:1px solid #232323;padding:2px">'.$function->GetOrderStatus($control_no,'branch',$db).'</td>
					<td style="width:1in;text-align:right;font-weight:600;padding:2px">Ref No.: </td>
					<td style="border-bottom:1px solid #232323;width: 1in;text-align:center;padding:2px">'.$function->GetOrderStatus($control_no,'control_no',$db).'</td>
				</tr>
			</table>			
		</div>
		<div class="dr-data" style="margin-top:8px">			
			<table style="width: 100%; border-collapse:collapse;font-weight:normal">
				<tr style="font-size:15px;background:aeaeae;padding:3px">
					<td style="width:30px;text-align:center;border:1px solid #333;padding:3px;border-bottom:3px solid #232323">#</td>
					<td style="width:90px;border:1px solid #333;padding:3px;text-align:center;border-bottom:3px solid #232323">ITEM CODE</td>
					<td style="text-align:center;border:1px solid #333;border-bottom:3px solid #232323">ITEM DESCRIPTION</td>
					<td style="text-align:center;width:80px;white-space:nowrap;border:1px solid #333;padding:3px;border-bottom:3px solid #232323">UNITS (UOM)</td>
					<td style="width:80px;white-space:nowrap;border:1px solid #333;padding:3px;text-align:center;border-bottom:3px solid #232323">Recq. Qty.</td>
					<td style="width:80px;white-space:nowrap;border:1px solid #333;padding:3px;text-align:center;border-bottom:3px solid #232323">Act. Qty.</td>
				</tr>
';
		$sqlQuery = "SELECT * FROM wms_branch_order WHERE control_no='$control_no' AND cancelled=0 LIMIT $startFrom, $itemsPerDiv";
		$results = mysqli_query($db, $sqlQuery);
		$x=0;
		while($ROWS = mysqli_fetch_array($results))  
		{
			$x++;
			$rowid = $ROWS['id'];
			$branch = $ROWS['branch'];
			$uom = $ROWS['uom'];
			$item = $ROWS['item_description'];
			$item_code = $ROWS['item_code'];
			$quantity = $ROWS['quantity'];
			$actual_quantity = $ROWS['actual_quantity'];
$RMShtml .='
				<tr style="font-family:"Fixedsys;font-size:13px">
					<td style="text-align:center;background:aeaeae;border:1px solid #333;padding:3px">'.$x.'</td>
					<td style="text-align:center;border:1px solid #333;padding:3px">'.$item_code.'</td>
					<td style="padding-left:10px;border:1px solid #333;padding:3px">'.$item.'</td>
					<td style="text-align:center;border:1px solid #333;padding:3px">'.$uom.'</td>
					<td style="padding:3px;padding-right:10px;text-align:right;border:1px solid #333">'.$quantity.'</td>
					<td style="padding:3px;padding-right:10px;text-align:right;border:1px solid #333">'.$actual_quantity.'</td>
				</tr>
';				
		}
$RMShtml .='		 
			</table>
			<div style="font-family:Lucida Sans,Geneva,Verdana, sans-serif;padding:3px;font-size:12px;margin-bottom:6px">
				<strong>PREP. REMARKS:</strong> <span style="font-style:italic">'.$function->getOrderRemarks($control_no,"preparator_remarks",$db).'</span>
			</div>
		</div>
';
$RMShtml .='		
		<div style="'.$border.';bottom:14.5cm">			
			<table style="width: 100%">
				<tr>
					<td colspan="6"></td>
					<td colspan="1" style="text-align:center;font-size:14px;font-family:Lucida Sans, Geneva, Verdana, sans-serif">
						<div style="margin:0 auto;width:180px">Receive the above Merchandise in good order and condition</div>
					</td>
				</tr>
				<tr>
					<td colspan="8" style="height:20px;"></td>
				</tr>
				<tr style="font-size:14px;font-family:Lucida Sans, Geneva, Verdana, sans-serif">
					<th style="width:0.5in">Driver: </th>
					<td style="width:1.80in;border-bottom:1px solid #232323;text-align:center">'.$function->GetOrderStatus($control_no,'delivery_driver',$db).'</td>
					<td>&nbsp;</td>
					<th style="width:0.50in">Plate No.</th>
					<td style="border-bottom:1px solid #232323;width:0.75in;text-align:center">'.$function->GetOrderStatus($control_no,'plate_number',$db).'</td>
					<td>&nbsp;</td>
					<th style="width:1in;">Received By:</th>
					<td style="width:1.80in;border-bottom:1px solid #232323">&nbsp;</td>
				</tr>
				<tr style="font-size:11px;font-family:Lucida Sans, Geneva, Verdana, sans-serif">
					<th></th>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td style="text-align:center;font-size:12px">Name / Signature / Date</td>
				</tr>
			</table>
';			
$RMShtml .='			
		</div>		
	</div>
</div>
';
}
//echo $RMShtml;
//exit();
$date_me_now = date('Now');
$dompdf->set_option('dpi', 120);
$dompdf->loadHtml($RMShtml);
$dompdf->setPaper('Letter', 'portrait', 'no-margin');
$dompdf->render();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="dr_document_'.$date_me_now.'.pdf"');
header('Content-Length: ' . strlen($dompdf->output()));
echo $dompdf->output();
