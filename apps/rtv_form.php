<?PHP
if(isset($_SESSION['WMS_RTV_SEARCH']))
{
	$po_no = $_SESSION['WMS_RTV_SEARCH'];
} else {
	$po_no = '';
}
?>
<style>
.vendor-data-wrapper {
	max-height: calc(100vh - 200px);
	margin-bottom: 10px;
	overflow:auto;
}
.search-vendor {
	width:300px;
	margin-bottom: 10px;
}
.rtv-table th, .rtv-table td {
	padding: 3px 5px 3px 5px !important;
	font-size: 14px !important;
	
}
.InfoFixHead {overflow: auto;max-height: calc(100vh - 200px) !important;width: 100%;}
.InfoFixHead thead th,
.InfoFixHead tfoot th {position: sticky;background: #0091d5; color: #fff;z-index: 1;}
.InfoFixHead thead th {top: 0;}
.InfoFixHead tfoot th {bottom: 0;}
.InfoFixHead table {border-collapse: collapse;}
.InfoFixHead th, .InfoFixHead td {font-size: 14px; white-space: nowrap;vertical-align:middle !important}

</style>
<div class="search-vendor">
	<div class="input-group">
		<input id="searchpo" type="text" class="form-control form-control-sm" placeholder="Enter PO No." autocomplete="off">
		<button class="btn btn-primary btn-sm btn-loadrtv" type="button" onclick="loadRTVInfo()">Search</button>
	</div>
</div>
<div class="vendor-data-wrapper InfoFixHead" id="vendordata"></div>
<script>
$(function() {
    $('#searchpo').on('keypress', function(event) {
        if (event.which === 13) {
            $('.btn-loadrtv').click();
        }
    });
});function loadRTVInfo()
{
	var search_term = $('#searchpo').val();
	if(search_term === '')
	{
		swal("Invalid Term","Please enter your search term Date or PO Number", "error");
		return false;
	}
	rms_reloaderOn('Loading...');
	$.post("./Modules/Warehouse_Management/includes/rtv_info.php", {  search_term: search_term },
	function(data) {		
		$('#vendordata').html(data);
		rms_reloaderOff();
	});
}
</script>