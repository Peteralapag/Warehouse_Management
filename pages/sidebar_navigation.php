<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
if(isset($_SESSION['wms_userlevel']))
{
	$user_level = $_SESSION['wms_userlevel'];
} else {
	$user_level = 0;
}
?>
<style>
.sidebar-nav { list-style-type:none; margin:0;padding:0 }
.navpadleft {margin-left:10px;cursor:pointer; width:100%;}
.sidebar-nav li { display: flex; padding:5px 5px 5px 5px;border-bottom: 1px solid #aeaeae; width:100%; gap: 15px;cursor:pointer}
.sidebar-nav li:hover {background:#e7e7e7;}
.sidebar-nav .nav-icon {width:30px;text-align:center;font-size:18px;}
.sidebar-nav span {right: 0;}
.sidebar-nav .caret-right {margin-left: auto;}
.active-nav {background: #dcdfe0;}
.active {border: 1px solid blue;}
.nav-bottom-btn {
	position:absolute;
	bottom: 2px;
	margin-left:3px;
	width: 98%;
	display:flex;
	gap:6px;
}
.nav-bottom-btn .btn {
	font-size:12px;
	white-space:nowrap;
}
.btn-masterdata {
	background:#0f766e;
	color:#fff;
	border-color:#0f766e;
}
.btn-masterdata:hover {
	background:#0d5f59;
	color:#fff;
	border-color:#0d5f59;
}
</style>
<ul class="sidebar-nav">
<?php
$sqlMenu = "SELECT * FROM wms_navigation WHERE active=1 ORDER BY ordering ASC";
$MenuResults = mysqli_query($db, $sqlMenu);    
if ( $MenuResults->num_rows > 0 ) 
{
	$m=0;
	while($MENUROW = mysqli_fetch_array($MenuResults))  
	{
		$m++;
?>
	<li id="nav<?php echo $m; ?>" data-nav="nav<?php echo $m; ?>" onclick="Check_Permissions('p_view',openMenuGranted,'<?php echo $MENUROW['page_name']; ?>','<?php echo $MENUROW['menu_name']; ?>')">
		<div class="nav-icon"> <i class="<?php echo $MENUROW['icon_class']; ?>"></i></div> <span><?php echo $MENUROW['menu_name']; ?></span>
	</li>
<?php } } else { echo "<li>Menu is Empty.</li>"; }?>
</ul>
<div id="resultsdata"></div>
<div class="btn-group nav-bottom-btn" role="group" aria-label="Ronan Sarbon">
<?php
// Check tbl_system_permission for master data privilege
$showMasterData = false;
if(isset($_SESSION['wms_appnameuser'])) {
	$user = $_SESSION['wms_appnameuser'];
	$sql = "SELECT * FROM tbl_system_permission WHERE username='".$db->real_escape_string($user)."' LIMIT 1";
	$result = $db->query($sql);
	if($result && $result->num_rows > 0) {
		$showMasterData = true;
	}
}
if($showMasterData) {
?>
   <button class="btn btn-masterdata" onclick="openMasterDataManagement()">Master Data Management <i class="fa-solid fa-database"></i></button>
<?php } ?>
	<button class="btn btn-secondary" onclick="wmsSettings()">Settings <i class="fa-solid fa-gear"></i></button>
	<button class="btn btn-danger" onclick="closeApps()">Exit Application <i class="fa-solid fa-right-from-bracket"></i></button>
</div>
<script>
function wmsSettings()
{
	var user_level = '<?php echo $user_level; ?>';
	if(user_level >= 80)
	{
		$.post("./Modules/Warehouse_Management/pages/wms_settings.php", { },
		function(data) {
			$('#contents').html(data);
		});
	} else {
		
	}
}
function openMasterDataManagement()
{
	var user_level = '<?php echo $user_level; ?>';
	if(user_level >= 80)
	{
		$.post("./Modules/Warehouse_Management/includes/item_masterlist_settings.php", { },
		function(data) {
			$('#contents').html(data);
		});
	}
	else
	{
		swal("Access Denied", "Only authorized analysts can modify master data settings.", "warning");
	}
}
function openMenuGranted(page)
{
	$.post("./Modules/Warehouse_Management/pages/menu_pages.php", { page: page },
	function(data) {
		$('#contents').html(data);
	});
}
$(function()	
{	
	if(sessionStorage.navwms !== 'null')
	{
		$("#"+sessionStorage.navwms).addClass('active-nav');
		$("#"+sessionStorage.navwms).trigger('click');
	}
	$('.sidebar-nav li').click(function()
	{
		var tab_id = $(this).attr('data-nav');
		sessionStorage.setItem("navwms",tab_id);
		$('.sidebar-nav li').removeClass('active-nav');
		$(this).addClass('sidebar-nav');
		$("#"+tab_id).addClass('active-nav');	
	});
});
function closeApps()
{
	dialogue_confirm("Warning","Are you sure to close Warehouse Management System!","warning","closeAppsYes","","red");
}
function closeAppsYes()
{
	$.post("./Modules/Warehouse_Management/actions/close_applications.php", { },
	function(data) {
		$('#contents').html(data);
	});
}
</script>
<script src="../Modules/Warehouse_Management/scripts/script.js"></script>
