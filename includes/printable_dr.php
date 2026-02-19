<?php
$control_no = $_POST['control_no'];
?>
<style>
.print-wrapper {
	margin-bottom:10px;
}
.iframe-frame {
	display: flex;
	width:90vw;
	height: calc(100vh - 300px);
	overflow-y:auto;
	background:#fff;
	padding-bottom:10px
}
.main-frame {
	width:90vw;
}
</style>
<div class="iframe-frame" id="iframeframe">
	<iframe class="main-frame" id="printFrame"></iframe>
</div>
<script>
function printMe(controlno)
{
	$('#iframeframe').slideDown();
	$('#printFrame').attr('src', './Modules/Warehouse_Management/includes/print_dr_pdf.php?control_no=' + controlno);
}
$(function()
{
	var controlno = '<?php echo $control_no; ?>';
	printMe(controlno);
//	$('#iframeframe').hide();
});
</script>