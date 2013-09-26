<?php

function hook_monitis_AdminAreaHeadOutput($vars) {
	//$rand = rand(1, 1000);
	$rand = 1;
	$head = '';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitis.js?'.$rand.'"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisclasses.js?'.$rand.'"></script>';
	$head .= '<link href="../modules/addons/monitis_addon/static/css/monitis.css" rel="stylesheet" type="text/css" />';
	return $head;
}
add_hook("AdminAreaHeadOutput", 1, "hook_monitis_AdminAreaHeadOutput");


///////////////////////////////////////////////////////////////////////////////////// Hooks: Admin

function hook_monitis_ServerAdd($vars) {
	require_once 'MonitisApp.php';
	$res = mysql_query(sprintf('SELECT id, name, ipaddress, hostname FROM tblservers WHERE id=%d', $vars['serverid']));
	$server = mysql_fetch_assoc($res);
	
	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $server );
	//exit;
}
add_hook("ServerAdd", 1, "hook_monitis_ServerAdd");

function hook_monitis_ServerDelete($vars) {
	require_once 'MonitisApp.php';
	$server_id = $vars['serverid'];
	$oWhmcs = new WHMCS_class();
	$oWhmcs->removeMonitorsByServersId($server_id);
	//_dump($vars);
}
add_hook("ServerDelete",1,"hook_monitis_ServerDelete");





///////////////////////////////////////////////////////////////////////////////////// Hooks: Order Process
// FraudOrder
function hook_monitis_AcceptOrder($vars) {
	
	require_once 'MonitisApp.php';

	m_log( $vars, 'AcceptOrder', 'order');
	monitisOrderHookHandler( $vars, 'active' ); 
}
add_hook("AcceptOrder",1,"hook_monitis_AcceptOrder");


function hook_monitis_PendingOrder($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'PendingOrder', 'order');
	monitisOrderHookHandler( $vars, 'pending' ); 
}
add_hook("PendingOrder",1,"hook_monitis_PendingOrder");


function hook_monitis_DeleteOrder($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'DeleteOrder', 'order');
	monitisOrderHookHandler( $vars, 'deleted' ); 
}
add_hook("DeleteOrder",1,"hook_monitis_DeleteOrder");


function hook_monitis_CancelOrder($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'CancelOrder', 'order');
	monitisOrderHookHandler( $vars, 'cancelled' ); 
}
add_hook("CancelOrder",1,"hook_monitis_CancelOrder");



////////////////////////////////////////////////////////////////////  Addon hooks

function hook_monitis_AddonActivation($vars) {
	require_once 'MonitisApp.php';

	m_log( $vars, 'AddonActivation', 'addon');
	monitisAddonHookHandler( $vars, 'active' ); 
}
add_hook("AddonActivation",1,"hook_monitis_AddonActivation");

function hook_monitis_AddonActivated($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonActivated', 'addon');
	monitisAddonHookHandler( $vars, 'active' ); 
}
add_hook("AddonActivated",1,"hook_monitis_AddonActivated");

function hook_monitis_AddonSuspended($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonSuspended', 'addon');
	monitisAddonHookHandler( $vars, 'suspended' ); 
}
add_hook("AddonSuspended",1,"hook_monitis_AddonSuspended");


function hook_monitis_AddonTerminated($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonTerminated', 'addon');
	monitisAddonHookHandler( $vars, 'terminated' ); 
}
add_hook("AddonTerminated",1,"hook_monitis_AddonTerminated");

function hook_monitis_AddonCancelled($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonCancelled', 'addon');
	monitisAddonHookHandler( $vars, 'cancelled' ); 
}
add_hook("AddonCancelled",1,"hook_monitis_AddonCancelled");

function hook_monitis_AddonFraud($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonFraud', 'addon');
	monitisAddonHookHandler( $vars, 'fraud' ); 
}
add_hook("AddonFraud",1,"hook_monitis_AddonFraud");

function hook_monitis_AddonDeleted($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AddonDeleted', 'addon');
	monitisAddonHookHandler( $vars, 'deleted' ); 
}
add_hook("AddonDeleted",1,"hook_monitis_AddonDeleted");


/*
function hook_monitis_AddonEdit($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AddonEdit', 'addon');
}

add_hook("AddonEdit",1,"hook_monitis_AddonEdit");


function hook_monitis_AddonAdd($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AddonAdd', 'addon');
}
add_hook("AddonAdd",1,"hook_monitis_AddonAdd");
*/




//////////////////////////////////////////////////////////////////// Module hooks


function hook_monitis_AfterModuleCreate($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AfterModuleCreate', 'module');
	monitisModuleHookHandler( $vars, 'active' ); 
}
add_hook("AfterModuleCreate",1,"hook_monitis_AfterModuleCreate");



function hook_monitis_AfterModuleSuspend($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AfterModuleSuspend', 'module');
	monitisModuleHookHandler( $vars, 'suspended' ); 
}
add_hook("AfterModuleSuspend",1,"hook_monitis_AfterModuleSuspend");


function hook_monitis_AfterModuleUnsuspend($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AfterModuleUnsuspend', 'module');
	monitisModuleHookHandler( $vars, 'active' ); 
}
add_hook("AfterModuleUnsuspend",1,"hook_monitis_AfterModuleUnsuspend");

function hook_monitis_AfterModuleTerminate($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AfterModuleTerminate', 'module');
	monitisModuleHookHandler( $vars, 'terminated' ); 
}
add_hook("AfterModuleTerminate",1,"hook_monitis_AfterModuleTerminate");

function hook_monitis_AfterModuleRenew($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AfterModuleRenew', 'module');
}
add_hook("AfterModuleRenew",1,"hook_monitis_AfterModuleRenew");



function hook_monitis_AfterConfigOptionsUpgrade($vars) {
	require_once 'MonitisApp.php';
	m_log( $vars, 'AfterConfigOptionsUpgrade', 'ConfigOptions');
	
}
add_hook("AfterConfigOptionsUpgrade",1,"hook_monitis_AfterConfigOptionsUpgrade");


function hook_monitis_AdminServiceEdit($vars) {
	require_once 'MonitisApp.php';
	
	m_log( $vars, 'AdminServiceEdit', 'edit');
	monitisEditHookHandler( $vars );
	//m_log( $_POST, 'AdminServiceEdit_POST', 'edit');
	//m_log( $_REQUEST, 'AdminServiceEdit_POST', 'edit');
}
add_hook("AdminServiceEdit",1,"hook_monitis_AdminServiceEdit");

///////////////////////////////////////////////////////////////////////////



