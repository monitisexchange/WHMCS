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

function hook_AdminAreaHeadOutput($vars) {
	$head = '';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/highcharts.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitis.js"></script>';
	$head .= '<link href="../modules/addons/monitis_addon/static/css/monitis.css" rel="stylesheet" type="text/css" />';
	
	return $head;
}
add_hook("AdminAreaHeadOutput", 1, "hook_AdminAreaHeadOutput");


///////////////////////////////////////////////////////////////////////////////////// Hooks: Admin
// http://docs.whmcs.com/Hooks:Admin

function hook_ServerAdd($vars) {
	require_once 'MonitisApp.php';
	$res = mysql_query(sprintf('SELECT id, name, ipaddress, hostname FROM tblservers WHERE id=%d', $vars['serverid']));
	$server = mysql_fetch_assoc($res);
	
	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $server );
	//MonitisApiHelper::addDefaultAgents($client_id, $server);
	//exit;
}
add_hook("ServerAdd", 1, "hook_ServerAdd");

function hook_ServerDelete($vars) {
	require_once 'MonitisApp.php';
	$server_id = $vars['serverid'];
	$oWhmcs = new WHMCS_class( MONITIS_CLIENT_ID );
	$oWhmcs->removeMonitorsByServersId($server_id);
	//_dump($vars);
}
add_hook("ServerDelete",1,"hook_ServerDelete");


///////////////////////////////////////////////////////////////////////////////////// Hooks: Order Process
// FraudOrder
function hookAcceptOrder($vars) {
	//m_log( $vars, 'AcceptOrder', 'order');
	
	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';

	$oService = new servicesClass();
	$orderid = $vars['orderid'];
	$product = $oService->product_by_order( $orderid );
	if( $product ) {
		$oService->createMonitors( $product );
		//return '<div>Order is Accept</div>';
	} else {
	
	}
}
add_hook("AcceptOrder",1,"hookAcceptOrder");

