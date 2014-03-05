<?php

function hook_monitis_AdminAreaHeadOutput($vars) {
	require_once 'monitisapp.php';
	//$version = rand(1, 1000);
	$version = MONITIS_RESOURCE_VERSION;

	$head = '<link href="http://code.jquery.com/ui/1.10.3/themes/'.MonitisConf::$jqueryAdminTheme.'/jquery-ui.css" rel="stylesheet" type="text/css" />';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	$head .= '<link href="../modules/addons/monitis_addon/static/css/monitis.css?'.$version.'" rel="stylesheet" type="text/css" />';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/lang/js/english.js?'.$version.'"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitis.js?'.$version.'"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisclasses.js?'.$version.'"></script>';
	return $head;
}
add_hook("AdminAreaHeadOutput", 1, "hook_monitis_AdminAreaHeadOutput");


function hook_monitis_AdminAreaFooterOutput($vars) {
	require_once 'monitisapp.php';
	
	$error = @MonitisConf::$apiServerError;
	$foot = '';
	if(!empty($error)) {
		$foot = '<script type="text/javascript">
		$(document).ready(function(){
			$("#monitis_api_error").css({
				fontSize:"12px",
				fontWeight:"bold",
				color: "#ff3333",
				width:"300px",
				position:"relative",
				margin:"-50px 150px 0px 0px",
				float:"right"
			}).html("'.$error.'");
		});
		</script>';
	}
	return $foot;
}
add_hook("AdminAreaFooterOutput", 1, "hook_monitis_AdminAreaFooterOutput");
///////////////////////////////////////////////////////////////////////////////////// Hooks: Admin

function hook_monitis_ServerAdd($vars) {
	require_once 'monitisapp.php';
	$res = mysql_query(sprintf('SELECT id, name, ipaddress, hostname FROM tblservers WHERE disabled=0 AND id=%d', $vars['serverid']));
	$server = mysql_fetch_assoc($res);
	
	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $server );
}
add_hook("ServerAdd", 1, "hook_monitis_ServerAdd");

function hook_monitis_ServerDelete($vars) {
	require_once 'monitisapp.php';
	$server_id = $vars['serverid'];

	// to change, add delete monitors 
	monitisWhmcsServer::unlinkMonitorsByServersId($server_id);
}
add_hook("ServerDelete",1,"hook_monitis_ServerDelete");



///////////////////////////////////////////////////////////////////////////////////// Client
// ClientClose
function hook_monitis_ClientClose($vars) {
	require_once 'monitisapp.php';
	monitisClientHookHandler($vars, 'closed');
}
add_hook("ClientClose",1,"hook_monitis_ClientClose");

// PreDeleteClient
function hook_monitis_PreDeleteClient($vars) {
	require_once 'monitisapp.php';
	monitisClientHookHandler($vars, 'deleted');
}
add_hook("PreDeleteClient",1,"hook_monitis_PreDeleteClient");


///////////////////////////////////////////////////////////////////////////////////// Hooks: Order Process
// FraudOrder
function hook_monitis_AcceptOrder($vars) {
	require_once 'monitisapp.php';
	
//m_log( $vars, 'AcceptOrder', 'order');

	monitisOrderHookHandler($vars, 'active'); 
}
add_hook("AcceptOrder",5,"hook_monitis_AcceptOrder");


function hook_monitis_PendingOrder($vars) {
	require_once 'monitisapp.php';
	
//m_log( $vars, 'PendingOrder', 'order');
	monitisOrderHookHandler( $vars, 'pending' ); 
}
add_hook("PendingOrder",1,"hook_monitis_PendingOrder");


function hook_monitis_DeleteOrder($vars) {
	require_once 'monitisapp.php';
//m_log( $vars, 'DeleteOrder', 'order');
	monitisOrderHookHandler( $vars, 'deleted' ); 
}
add_hook("DeleteOrder",1,"hook_monitis_DeleteOrder");


function hook_monitis_CancelOrder($vars) {
	require_once 'monitisapp.php';
//m_log( $vars, 'CancelOrder', 'order');
	monitisOrderHookHandler( $vars, 'cancelled' ); 
}
add_hook("CancelOrder",1,"hook_monitis_CancelOrder");

function hook_monitis_FraudOrder($vars) {
	require_once 'monitisapp.php';
//m_log( $vars, 'FraudOrder', 'order');
	monitisOrderHookHandler( $vars, 'fraud' ); 
}
add_hook("FraudOrder",1,"hook_monitis_FraudOrder");



////////////////////////////////////////////////////////////////////  Addon hooks
// AcceptOrder fire
/*
function hook_monitis_AddonActivation($vars) {	// AcceptOrder handler 
	require_once 'monitisapp.php';
	//monitis_orderAddonHookHandler( $vars ); 
}
add_hook("AddonActivation",1,"hook_monitis_AddonActivation");
*/

function hook_monitis_AddonActivated($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler( $vars, 'active' ); 
}
add_hook("AddonActivated",1,"hook_monitis_AddonActivated");

function hook_monitis_AddonSuspended($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler( $vars, 'suspended' ); 
}
add_hook("AddonSuspended",1,"hook_monitis_AddonSuspended");


function hook_monitis_AddonTerminated($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler( $vars, 'terminated' ); 
}
add_hook("AddonTerminated",1,"hook_monitis_AddonTerminated");

function hook_monitis_AddonCancelled($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler( $vars, 'cancelled' ); 
}
add_hook("AddonCancelled",1,"hook_monitis_AddonCancelled");

function hook_monitis_AddonFraud($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler( $vars, 'fraud' ); 
}
add_hook("AddonFraud",1,"hook_monitis_AddonFraud");

function hook_monitis_AddonDeleted($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler($vars, 'deleted'); 
}
add_hook("AddonDeleted",1,"hook_monitis_AddonDeleted");

function hook_monitis_AddonAdd($vars) {
	require_once 'monitisapp.php';
	monitisAddonHookHandler($vars, 'active'); 
}
add_hook("AddonAdd",1,"hook_monitis_AddonAdd");

//////////////////////////////////////////////////////////////////// Module hooks
 
function hook_monitis_AfterModuleCreate($vars) {											// AcceptOrder handler 
	require_once 'monitisapp.php';
	monitisCreateModuleCommandHandler($vars);
}
add_hook("AfterModuleCreate",1,"hook_monitis_AfterModuleCreate");



function hook_monitis_AfterModuleSuspend($vars) {
	require_once 'monitisapp.php';
	monitisModuleHookHandler( $vars, 'suspended' ); 
}
add_hook("AfterModuleSuspend",1,"hook_monitis_AfterModuleSuspend");


function hook_monitis_AfterModuleUnsuspend($vars) {
	require_once 'monitisapp.php';
	monitisModuleHookHandler( $vars, 'active' ); 
}
add_hook("AfterModuleUnsuspend",1,"hook_monitis_AfterModuleUnsuspend");

function hook_monitis_AfterModuleTerminate($vars) {
	require_once 'monitisapp.php';
	monitisModuleHookHandler( $vars, 'terminated' ); 
}
add_hook("AfterModuleTerminate",1,"hook_monitis_AfterModuleTerminate");

function hook_monitis_AdminServiceEdit($vars) {
	require_once 'monitisapp.php';
	monitisEditHookHandler( $vars );
}
add_hook("AdminServiceEdit",1,"hook_monitis_AdminServiceEdit");


