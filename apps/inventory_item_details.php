<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.inventory.php";
$inventory = new WMSInventory;
$item_code = $_POST['item_code'];
$item = $_POST['item'];
?>
<style>
.item-details {
    width: auto;
}
.item-name {
    background: #f1f1f1;
    border: 1px solid #aeaeae;
    padding: 5px;
    border-radius: 5px;
    margin-bottom: 10px;
    text-align: center;
}
.lamesa th {
    white-space: nowrap;
    background: #aeaeae !important;
    color: #fff;
    padding: 3px 5px 3px 5px;
}
.lamesa td {
    white-space: nowrap;
    padding: 3px 5px 3px 5px;
}
</style>
<div class="item-details">
    <div class="item-name"><strong><?php echo strtoupper($item); ?></strong></div>
    <table style="width:100%" class="table table-bordered table-striped lamesa">
        <tr>            
            <th>SUPPLIER ID</th>
            <th>QUANTITY</th>            
            <th>UNIT PRICE</th>            
            <th>EXPIRATION DATE</th>
            <th>RECEIVED DATE</th>
        </tr>
<?php
    $curdate = date("Y-m-d");
    $sqlQuery = "SELECT * FROM wms_receiving_details WHERE item_code='$item_code' AND expiration_date > '$curdate'";    
    $results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
        $i=0;
        while($ITEMROWS = mysqli_fetch_array($results))  
        {
            if($ITEMROWS['expiration_date'] != '')
            {
                $expire = date("F d, Y", strtotime($ITEMROWS['expiration_date']));
            } else {
                $expire = "No Exipration";
            }
            if($ITEMROWS['received_date'] != '')
            {
                    $received_date = date("F d, Y", strtotime($ITEMROWS['received_date']));;
            } else {
                $received_date = "";
            }
?>        
        <tr>            
            <td style="text-align:center"><?php echo $ITEMROWS['supplier_id']; ?></td>
            <td style="text-align: right; padding-right: 10px"><?php echo $ITEMROWS['quantity_received']; ?></td>
            <td style="text-align: right; padding-right: 10px"><?php echo $ITEMROWS['unit_price']; ?></td>
            <td><?php echo $expire; ?></td>
            <td><?php echo $received_date; ?></td>
        </tr>
<?php } } else { ?>        
        <tr>            
            <td colspan="5" style="text-align: center">No Records</td>
        </tr>
<?php } ?>
    </table>
</div>