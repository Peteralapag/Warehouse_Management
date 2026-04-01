<style>
.masterdata-shell {
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:10px;
    padding:12px;
}
.masterdata-title {
    font-size:18px;
    font-weight:700;
    color:#0f172a;
    margin-bottom:4px;
}
.masterdata-sub {
    font-size:12px;
    color:#64748b;
    margin-bottom:10px;
}
</style>

<div class="masterdata-shell">
    <div class="masterdata-title">Master Data Management</div>
    <div class="masterdata-sub">Item_Masterlist_Settings</div>
    <div id="masterdata-content"><i class="fa fa-spinner fa-spin"></i></div>
</div>

<script>
$(function()
{
    $.post("./Modules/Warehouse_Management/includes/itemlist_management.php", { },
    function(data) {
        $('#masterdata-content').html(data);
    });
});
</script>
