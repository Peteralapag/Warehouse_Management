function GetAccess(permission,access)
{
    var modulee = sessionStorage.module;
    var module = modulee.replace(/_/g, ' ');
    return new Promise((resolve, reject) => {
        $.post("./Modules/Warehouse_Management/actions/get_access.php", { module: module, access: access, permission: permission },
        function(data) {
            if(data == 1) {
                resolve(true);
            } else {
                resolve(false);
            }
        }).fail(function() {
            reject('Error checking permissions');
        });
    });
}
function checkAccess(permission, access) {
    var modulee = sessionStorage.module;
    var module = modulee.replace(/_/g, ' ');
    return new Promise((resolve, reject) => {
        $.post("./Modules/Property_Custodian_System/actions/check_permissions.php", { module: module, access: access, permission: permission },
        function(data) {
            if(data == 1) {
                resolve(true);
            } else {
                resolve(false);
            }
        }).fail(function() {
            reject('Error checking permissions');
        });
    });
}
function Check_Access(params,permission,action)
{
	var module = sessionStorage.module_name;
	$.post("./Modules/Warehouse_Management/actions/check_permissions.php", { permission: permission, module: module },
	function(data) {
		if(data == 1)
		{
			action(params);
		}
		else if(data == 0)
		{
			swal("Access Denied","You have insufficient access. Please contact System Administrator","warning");
		}
	});
}
function Check_Permissions(permission,action,page,module)
{
	sessionStorage.setItem("page_name", page);
	sessionStorage.setItem("module_name", module);
	$.post("./Modules/Warehouse_Management/actions/check_permissions.php", { permission: permission, module: module },
	function(data) {
		if(data == 1)
		{
			action(page);
		}
		else if(data == 0)
		{
			swal("Access Denied","You have insufficient access. Please contact System Administrator","warning");
		}
	});
}
function dialogue_confirm(dialogtitle,dialogmsg,dialogicon,command,params,btncolor)
{
	if(btncolor == null || btncolor == '') 
	{
		var btncolor = '';
	} else {
		var btncolor = btncolor;
	}
	swal({
		title: dialogtitle,
		text: dialogmsg,
		icon: dialogicon,
		buttons: [
		'No',
		'Yes'
		],
		dangerMode: btncolor,
	}).then(function(isConfirm) {
		if (isConfirm)
		{
			if(command == 'clearCacheYes')
			{
				clearCacheYes(params);
			}
			if(command == 'resetDataYes')
			{
				resetDataYes();
			}
			if(command == 'closeAppsYes')
			{
				closeAppsYes();
			}
			if(command == 'closeReceivingYes')
			{
				closeReceivingYes(params);
			}
			if(command == 'deleteTransferYes')
			{
				deleteTransferYes(params);
			}
			if(command == 'requestReopenYes')
			{
				requestReopenYes(params);
			}
		}
	});
}
