<?php
function m_log( $log_text, $title='', $filename=''){
	$logPath = '/var/www/whmcs/modules/addons/monitis_addon/_logs/';
	$file = 'log';
	if( !empty($filename) ) $file = $filename;
	$logFile = "$logPath$file.log";
	$time = date ('Y-m-d H:i:s');
	
	$text = '';
	if( is_array($log_text) ) {
		//$text = json_encode($log_text);
		$text = var_export($log_text, true);
	} else {
		$text = $log_text;
	}
	$string = "$time -|- $title -|- $text\r\n\n";

	if (! $f = fopen ( $logFile, "a" )) return FALSE;
	if (! fwrite ( $f, $string )) return FALSE;
	fclose ( $f );
	chmod($logFile, 0777);
	return TRUE;
}

function hook_monitis_AdminAreaHeadOutput($vars) {
	$head = '';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/highcharts.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitis.js"></script>';
	$head .= '<link href="../modules/addons/monitis_addon/static/css/monitis.css" rel="stylesheet" type="text/css" />';
	
	//$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	//$head .= '<link href="../modules/addons/monitis_addon/static/css/chosen.css" rel="stylesheet" type="text/css" />';
	return $head;
}
add_hook("AdminAreaHeadOutput", 1, "hook_monitis_AdminAreaHeadOutput");


///////////////////////////////////////////////////////////////////////////////////// Hooks: Admin
// http://docs.whmcs.com/Hooks:Admin

function hook_monitis_ServerAdd($vars) {
	require_once 'MonitisApp.php';
	$res = mysql_query(sprintf('SELECT id, name, ipaddress, hostname FROM tblservers WHERE id=%d', $vars['serverid']));
	$server = mysql_fetch_assoc($res);
	
	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $server );
	//MonitisApiHelper::addDefaultAgents($client_id, $server);
	//exit;
}
add_hook("ServerAdd", 1, "hook_monitis_ServerAdd");

function hook_monitis_ServerDelete($vars) {
	require_once 'MonitisApp.php';
	$server_id = $vars['serverid'];
	$oWhmcs = new WHMCS_class( MONITIS_CLIENT_ID );
	$oWhmcs->removeMonitorsByServersId($server_id);
	//_dump($vars);
}
add_hook("ServerDelete",1,"hook_monitis_ServerDelete");





///////////////////////////////////////////////////////////////////////////////////// Hooks: Order Process
// FraudOrder
function hook_monitis_AcceptOrder($vars) {
	
	$orderid = $vars["orderid"];

	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';
_logActivity("HOOK AcceptOrder: orderid = $orderid ");
	//m_log( $vars, 'AcceptOrder', 'order');

	$oService = new servicesClass();
	
	$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
	$iOrder = localAPI( "getorders", $values, "admin");

	$product = $oService->product_by_order( $orderid, $iOrder );
	if( $product ) {
		$resp = $oService->createMonitor( $product );
		if( $resp['status'] && $resp['status'] == 'error') {
			$values["orderid"] = $orderid;
			$results = localAPI("pendingorder", $values, "admin");
		}
		_logActivity("createMonitor by AcceptOrder hook: **** result = ". json_encode( $resp));
	}
}

add_hook("AcceptOrder",1,"hook_monitis_AcceptOrder");


function hook_monitis_DeleteOrder($vars) {
	//m_log( $vars, 'DeleteOrder', 'order');
	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';
	
	$oService = new servicesClass();
	$oService->deactiveMonitorByOrder( $vars['orderid'] );
}
add_hook("DeleteOrder",1,"hook_monitis_DeleteOrder");


function hook_monitis_CancelOrder($vars) {
	//m_log( $vars, 'CancelOrder', 'order');
	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';
	
	$oService = new servicesClass();
	$oService->deactiveMonitorByOrder( $vars['orderid'] );
}
add_hook("CancelOrder",1,"hook_monitis_CancelOrder");

function hook_monitis_PendingOrder($vars) {
	//m_log( $vars, 'PendingOrder', 'order');
	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';
	
	$oService = new servicesClass();
	$oService->deactiveMonitorByOrder( $vars['orderid'] );
}
add_hook("PendingOrder",1,"hook_monitis_PendingOrder");

