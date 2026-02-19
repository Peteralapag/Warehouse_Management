<?php
session_start();
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$function = new WMSFunctions;
$inventory = new WMSInventory;
$_SESSION['WMS_INVSUBMENU'] = $_POST['invpage'];
$_SESSION['WMS_MONTH'] = $_POST['months'];
$_SESSION['WMS_YEAR'] = $_POST['year'];

$month = $_POST['months'];
$year = $_POST['year'];
if($_POST['limit'] != '' && $_POST['category'] != '')
{
	$_SESSION['WMS_ITEMCATEGORY'] = $_POST['category'];	
	$cat = $_POST['category'];
	$limit = $_POST['limit'];
	$q = "WHERE month='$month' AND year='$year' AND category='$cat' LIMIT $limit";
}
else if($_POST['limit'] != '' && $_POST['category'] == '')
{	
	unset($_SESSION['WMS_ITEMCATEGORY']);
	$limit = $_POST['limit'];
	$q = "WHERE month='$month' AND year='$year' LIMIT $limit";
}
else if($_POST['limit'] == '' && $_POST['category'] == '')
{	
	unset($_SESSION['WMS_ITEMCATEGORY']);
	$limit = $_POST['limit'];
	$q = "WHERE $month='$month' AND year='$year'";
}
?>
<table style="width: 100%" class="table table-bordered table-striped table-hover">
	<thead>
	<tr>
		<th style="text-align:center;width:70px !important">#</th>
		<th>ITEM CODE</th>
		<th>ITEM DESCRIPTION</th>
		<th>YEAR</th>
		<th>MONTH</th>
		<th>BEGINNING</th>
		<?php
			for($th = 1; $th <= 31; $th++)
			{
				echo '<th style="text-align:center;width:50px">Day '.$th.'</th>';
			}
		?>
		<th style="text-align:center">TOTAL</th>
	</tr>
	</thead>
	<tbody>
<?php
$sqlQuery = "SELECT * FROM wms_inventory_records $q";
$results = mysqli_query($db, $sqlQuery);
if ($results->num_rows > 0) {
    $i = 0;
    while ($INVROW = mysqli_fetch_array($results))
    {
	    $i++;
	    $itemcode = $INVROW['item_code'];
	    $beginning = $inventory->getMonthlyBeginning($itemcode,$month,$year,$db);	   
		echo $inventory->getUpdateBeginning($itemcode,$beginning,$month,$year,$db);
?>
        <tr>
        	<td style="text-align;"><?php echo $i; ?></td>
            <td style="text-align:center"><?php echo $INVROW['item_code']; ?></td>
            <td><?php echo $INVROW['item_description']; ?></td>
            <td style="text-align:center"><?php echo $INVROW['year']; ?></td>
            <td style="text-align:center"><?php echo $INVROW['month']; ?></td>
            <td style="text-align:right;padding-right:15px;font-weight:600;color:green"><?php echo number_format($beginning,2); ?></td>
            <?php
            $total=0;
            for ($x = 1; $x <= 31; $x++)
            {
	            $td = str_pad($x, 2, '0', STR_PAD_LEFT);
                $day = $INVROW['day_' . $td];
                echo '<td style="text-align:center;width:50px">' . $day . '</td>';
                $total += $day;
            }
	        echo $inventory->getUpdateEnding($itemcode,$total,$month,$year,$db);    		        
?>
            <td style="text-align:right;font-weight:600;color:red"><?php echo $INVROW['ending']; ?></td>
        </tr>
    <?php }
} else { ?>
    <tr>
        <td style="text-align:left" colspan="38"><i class="fa fa-bell color-orange"></i>&nbsp;&nbsp;No Records for the Month of <strong> <?php echo date("F", strtotime($month)); ?></strong></td>
    </tr>
<?php } ?>	</tbody>
</table>

