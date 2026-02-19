<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;

if($_POST['search'] != '')
{
	$search = $_POST['search'];
	$s = "WHERE branch LIKE '%$search%'";
} else {
	$s = '';
}

	$query = "SELECT * FROM tbl_branch $s ORDER BY branch ASC";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
		echo '<li onclick="setBranch()">CLEAR BRANCH &times</li>';
	    while($ROW = mysqli_fetch_array($results))  
		{
			$branch_name = $ROW['branch'];
?>	
			<li onclick="setBranch('<?php echo $branch_name; ?>')"><?php echo $branch_name; ?></li>
<?php
		}
	} else {
		echo '<li>No Records</li>';
	}
?>			
	