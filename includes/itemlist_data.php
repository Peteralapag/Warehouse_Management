<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
$conversion_table = 'wms_itemlist_conversion';
$checkConvTable = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_conversion'");
if(!$checkConvTable || $checkConvTable->num_rows == 0)
{
	$checkConvTable2 = mysqli_query($db, "SHOW TABLES LIKE 'wms_itemlist_converssion'");
	if($checkConvTable2 && $checkConvTable2->num_rows > 0)
	{
		$conversion_table = 'wms_itemlist_converssion';
	}
}
$module_code = isset($_POST['module_code']) && trim($_POST['module_code']) != '' ? trim($_POST['module_code']) : 'Warehouse_Management';
$module_code_esc = mysqli_real_escape_string($db, $module_code);
$module_visibility_filter = "(NOT EXISTS (SELECT 1 FROM wms_item_module_visibility mv0 WHERE mv0.item_id = wi.id AND mv0.active=1) OR EXISTS (SELECT 1 FROM wms_item_module_visibility mv1 WHERE mv1.item_id = wi.id AND mv1.module_code='$module_code_esc' AND mv1.active=1))";
$limitValue = isset($_POST['limit']) ? (int)$_POST['limit'] : 0;
$limitSql = $limitValue > 0 ? " LIMIT $limitValue" : "";

$search = isset($_POST['search']) ? trim((string)$_POST['search']) : '';
$category = isset($_POST['category']) ? trim((string)$_POST['category']) : '';
$recipient = isset($_POST['recipient']) ? trim((string)$_POST['recipient']) : '';

$whereParts = array();
$whereParts[] = "wi.active=1";
$whereParts[] = $module_visibility_filter;

if($category !== '')
{
	$categoryEsc = mysqli_real_escape_string($db, $category);
	$whereParts[] = "wi.category='$categoryEsc'";
}

if($recipient !== '')
{
	$recipientEsc = mysqli_real_escape_string($db, $recipient);
	$whereParts[] = "wi.recipient='$recipientEsc'";
}

if($search !== '')
{
	$searchEsc = mysqli_real_escape_string($db, $search);
	$whereParts[] = "(wi.item_description LIKE '%$searchEsc%' OR wi.item_code LIKE '%$searchEsc%' OR wi.qr_code LIKE '%$searchEsc%')";
}

$q = "WHERE ".implode(' AND ', $whereParts)." ORDER BY wi.active DESC, wi.item_description ASC".$limitSql;
?>
<style>
.itemlist-panel {
	background: #ffffff;
	border: 1px solid #e5e7eb;
	border-radius: 10px;
	padding: 10px;
	box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.itemlist-table-wrap {
	max-height: 68vh;
	overflow: auto;
	border-radius: 8px;
	border: 1px solid #edf0f4;
}
.itemlist-table {
	margin-bottom: 0;
	font-size: 13px;
	min-width: 1320px;
	background: #ffffff;
}
.itemlist-table thead th {
	position: sticky;
	top: 0;
	z-index: 2;
	background: #f8fafc;
	color: #334155 !important;
	border-bottom: 1px solid #e2e8f0 !important;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: .02em;
	white-space: nowrap;
	padding: 9px 8px !important;
	vertical-align: middle;
}
.itemlist-table tbody td {
	background: #ffffff;
	color: #0f172a;
	padding: 7px 8px !important;
	vertical-align: middle;
	border-color: #eff2f6;
}
.itemlist-table tbody tr:nth-child(even) td {
	background: #fcfdff;
}
.itemlist-table tbody tr {
	cursor: pointer;
	transition: background-color .15s ease;
}
.itemlist-table tbody tr:hover td {
	background: #f4f8ff;
}
.item-code {
	font-weight: 600;
	color: #0f172a;
}
.status-badge {
	display: inline-block;
	padding: 2px 10px;
	border-radius: 999px;
	font-size: 11px;
	font-weight: 600;
	line-height: 1.4;
	min-width: 80px;
	text-align: center;
}
.status-active {
	background: #e8f8ef;
	color: #107a3f;
	border: 1px solid #c4ebd5;
}
.status-inactive {
	background: #fff0f0;
	color: #a11b1b;
	border: 1px solid #ffd4d4;
}
.btn-edit-item {
	width: 100%;
	font-size: 12px;
	font-weight: 600;
	padding: 4px 10px;
	border-radius: 6px;
	border: 1px solid #f5c46a;
	background: #fff8e7;
	color: #7a5200;
}
.btn-edit-item:hover {
	background: #ffefc6;
}
.empty-state {
	padding: 24px 8px !important;
	text-align: center;
	color: #64748b;
	font-size: 13px;
}
.col-center {
	text-align: center;
}
.col-right {
	text-align: right;
	padding-right: 14px !important;
}
.itemlist-table-wrap::-webkit-scrollbar {
	height: 10px;
	width: 10px;
}
.itemlist-table-wrap::-webkit-scrollbar-thumb {
	background: #cbd5e1;
	border-radius: 10px;
}
</style>

<div class="itemlist-panel">
<div class="itemlist-table-wrap">
<table style="width: 100%" class="table table-bordered table-hover itemlist-table">
	<thead>
		<tr>
			<th class="col-center" style="width:50px;">#</th>
			<th>RECIPIENT</th>
			<th>ITEM LOCATION</th>
			<th>ITEM CODE</th>
			<th>CATEGORY</th>
			<th>ITEM DESCRIPTION</th>
			<th>UNIT PRICE</th>
			<th>UOM</th>
			<th>CONVERSION</th>
			<th>ADDED BY</th>
			<th>DATE ADDED</th>
			<th>STATUS</th>			
			<th>MIN. LEADTIME</th>
			<th>MAX. LEADTIME</th>
			<th>ACTION</th>
		</tr>
	</thead>
	<tbody>
<?PHP
	$sqlQuery = "SELECT wi.*, wc.uom_from, wc.uom_to, wc.factor FROM wms_itemlist wi LEFT JOIN $conversion_table wc ON wc.item_id = wi.id $q";
	$results = mysqli_query($db, $sqlQuery);    
    if ( $results->num_rows > 0 ) 
    {
    	$sp=0;
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$sp++;
			$rowid = $ITEMSROW['id'];
			$conv_text = '';
			if(trim((string)$ITEMSROW['uom_from']) != '' && trim((string)$ITEMSROW['uom_to']) != '' && trim((string)$ITEMSROW['factor']) != '')
			{
				$conv_text = $ITEMSROW['uom_from'].' => '.$ITEMSROW['uom_to'].' ('.$ITEMSROW['factor'].')';
			}
			$item_description = mb_strimwidth($ITEMSROW['item_description'], 0, 40, "...");
			if($ITEMSROW['active'] == 1)
			{
				$status = "Active";
				$status_class = 'status-active';
			} else {
				$status = "In-Active";
				$status_class = 'status-inactive';
			}
			if($ITEMSROW['date_added'] != '')
			{
				$date_added = date("F d, Y @h:i A", strtotime($ITEMSROW['date_added']));
			} else {
				$date_added = "--|--";
			}
?>
		<tr ondblclick="itemlistFormEdit('edit','<?php echo $rowid; ?>')">
			<td class="col-center"><?php echo $sp;?></td>			
			<td class="col-center"><?php echo htmlspecialchars((string)$ITEMSROW['recipient']);?></td>
			<td class="col-center"><?php echo htmlspecialchars((string)$ITEMSROW['item_location']);?></td>
			<td class="col-center item-code"><?php echo htmlspecialchars((string)$ITEMSROW['item_code']);?></td>
			<td><?php echo htmlspecialchars((string)$ITEMSROW['category']);?></td>
			<td title="<?php echo htmlspecialchars((string)$ITEMSROW['item_description']); ?>"><?php echo htmlspecialchars((string)$item_description);?></td>			
			<td class="col-right"><?php echo number_format((float)$ITEMSROW['unit_price'], 2);?></td>
			<td><?php echo htmlspecialchars((string)$ITEMSROW['uom']);?></td>
			<td><?php echo $conv_text;?></td>
			<td><?php echo htmlspecialchars((string)$ITEMSROW['added_by']);?></td>
			<td><?php echo htmlspecialchars((string)$date_added);?></td>
			<td class="col-center"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status;?></span></td>
			<td class="col-center"><?php echo htmlspecialchars((string)$ITEMSROW['average_leadtime']);?></td>
			<td class="col-center"><?php echo htmlspecialchars((string)$ITEMSROW['max_leadtime']);?></td>
			<td>
				<button type="button" class="btn-edit-item" onclick="itemlistFormEdit('edit','<?php echo $rowid; ?>')">Edit</button>
			</td>
		</tr>
<?PHP 	} } else { ?>
		<tr>
			<td colspan="15" class="empty-state"><i class="fa fa-bell"></i>&nbsp;&nbsp;No records found</td>
		</tr>
<?PHP } ?>		
	</tbody>		
</table>
</div>
</div>
<script>
function itemlistFormEdit(params,rowid)
{
	$('#modaltitle').html("UPDATE ITEMLIST");
	$.post("./Modules/Warehouse_Management/apps/itemlist_form.php", { params: params, rowid: rowid },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}
</script>
