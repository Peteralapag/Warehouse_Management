<?php
if(isset($_POST['branch']))
{
	$branch = $_POST['branch'];
} else {
	$branch = '';
}
if(isset($_POST['mode']))
{
	$mode = $_POST['mode'];
} else {
	$mode = '';
}
$trans_date = $_POST['transdate'];
$control_no = $_POST['control_no'];
?>
<style>
.print-wrapper {margin-bottom:10px;}
.iframe-frame {display: flex;width:90vw;height: calc(100vh - 300px);overflow-y:auto;background:#fff;padding-bottom:10px}
.main-frame {width:90vw;}
</style>
<?php if($mode != 'singleprint')  { ?>
<input type="date" class="form-control form-control-sm" style="width:200px" value="<?php echo date('Y-m-d', strtotime($trans_date))?>" onblur="printMe(this.value)">
<?php } ?>
<div class="iframe-frame" id="iframeframe">
	<iframe class="main-frame" id="printFrame"></iframe>
</div>
<script>
function printMe(transdate)
{
	var mode = '<?php echo $mode; ?>';
	rms_reloaderOn();
	if(mode == 'singleprint')
	{
		var branch = '<?php echo $branch; ?>';
		var control_no = '<?php echo $control_no; ?>';
		$('#printFrame').attr('src', './Modules/Warehouse_Management/apps/packing_list_single.php?trans_date=' + transdate + '&branch=' + branch + '&controlno=' + control_no);
	} else {
		$('#printFrame').attr('src', './Modules/Warehouse_Management/apps/packing_list.php?trans_date=' + transdate);
	}
}
$(function()
{
	var iframe = $('#printFrame');
	iframe.on('load', function() {
		rms_reloaderOff();		
	});
	var transdate = '<?php echo $trans_date; ?>';
	printMe(transdate);
});
</script>