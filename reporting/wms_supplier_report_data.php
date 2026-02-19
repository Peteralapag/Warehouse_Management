<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$table = $_SESSION['WMS_TABLE'];
if($_POST['limit'] != '')
{
	$limit = "LIMIT ".$_POST['limit'];
} else {
	$limit = "";
}
if($_POST['columns'] == "")
{
	$string =  "*";
	print_r('
		<script>
			sessionStorage.removeItem("stringKo");
		</script>		
	');
	exit();
} 
else 
{
	$string = $_POST["columns"];
	$columns = explode(',', $string);
}
?>
<table style="width: 100%" class="table table-bordered">
	<thead>
		<tr>
<?php
	foreach ($columns as $column) {
    	$col = str_replace("_"," ", $column);
    	$col = strtoupper($col);
        echo "<th>$col</th>";
    }
?>
		</tr>
	</thead>
	<tbody>
<?php	
	$query = "SELECT $string FROM $table $limit";
	$results = mysqli_query($db, $query);    
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results))  
		{
			echo "<tr>";
			foreach ($columns as $column) {
				echo "<td>".$ROW[$column]."</td>";
			}
			echo "</tr>";
		}
	} 
	else
	{ 
		echo ""; 
	} 
?>			
	</tbody>
</table>
<script>
$(function()
{
	sessionStorage.setItem('stringKo', '<?php echo $string; ?>');
});
</script>