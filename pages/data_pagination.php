<style>
.page-wrapper {display:flex;gap: 5px;}
.page-wrapper select {width:60px}
label {margin:0;	padding:0}
</style>
<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	
require $_SERVER['DOCUMENT_ROOT']."/Modules/Warehouse_Management/class/Class.functions.php";
$function = new WMSFunctions;
$table = 'wms_branch_order';
$records_per_page = 100;
$page = 1;
?>
<div class="page-wrapper">
	<label>Page(s)</label>
	<select id="pages" class="form-control form-control-sm" onchange="load_me('pages')">
		<?php echo $function->loadPagesCount($table,$records_per_page,$page,$db) ?>
	</select>
	<span>|</span>
	<label>Rows/Page</label>
	<select id="records_per_page" class="form-control form-control-sm" onchange="load_me('rows')">
		<?php echo $function->GetRowLimit($records_per_page) ?>
	</select>
</div>
<script>
function load_me(params) {
	if(params == 'pages')
	{
	    var pages = $('#pages').val();
	    var records_per_page = $('#records_per_page').val();
	}
	else if(params == 'rows')
	{
	    var pages = 1;
	    var records_per_page = $('#records_per_page').val();
	}
	var category = $('#category').val();
	var data_page = '<?php echo $data_page ?>';
	rms_reloaderOn("Loading...");
    var formData = {
    	category: category,
        pages: pages,
        records_per_page: records_per_page
    };
    $.ajax({
        url: './Modules/Warehouse_Management/includes/' + data_page + '.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            $('#smnavdata').html(response);
            rms_reloaderOff();
        },
        error: function(xhr, status, error) {
            $('#smnavdata').html('<p>An error occurred: ' + error + '</p>');
        }
    });
}
</script>