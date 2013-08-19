<?php

function hook_monitis_AdminAreaHeadOutput($vars) {
	$head = '';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/jquery.validate.min.js"></script>';
	$head .= '<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitis.js"></script>';
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
	//MonitisApiHelper::addDefaultAgents($client_id, $server);
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
	
	$orderid = $vars["orderid"];

	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';

//_logActivity("HOOK AcceptOrder: _SESSION = ".json_encode($_SESSION) );

	$adminuser = MonitisConf::getAdminName();
	$oService = new servicesClass();
	$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
	$iOrder = localAPI( "getorders", $values, $adminuser);

	$product = $oService->product_by_order( $orderid, $iOrder, $adminuser );
	
	if( $product && isset( $product['order_behavior'] ) && !empty($product['order_behavior']) ) {
	
		$order_behavior = json_decode($product['order_behavior'], true);
		$productMonitor = $oService->productMonitorByOrderId( $orderid );
		
		$action = $order_behavior['active'];
		if( $action == 'create' ) {
			if(!$productMonitor) {
				$resp = $oService->createMonitor( $product );
			} else {
				$resp = MonitisApi::activateExternal($productMonitor["monitor_id"]);
			}
		} elseif( $action == 'suspend' && $productMonitor ) {
			$resp = MonitisApi::suspendExternal($productMonitor["monitor_id"]);
		} 
/*		
		elseif( $action == 'delete' && $productMonitor ) {
			$resp = MonitisApi::deleteExternal($productMonitor["monitor_id"]);
			if($resp['status'] == 'ok') {
				$oWhmcs = new WHMCS_class();
				$oWhmcs->removeExternalMonitorsById($productMonitor["monitor_id"]);
			}
		}
*/
		/*$resp = $oService->createMonitor( $product );
		if( $resp['status'] && $resp['status'] == 'error') {
			$values["orderid"] = $orderid;
			$results = localAPI("pendingorder", $values, $adminuser );
		}
		_logActivity("createMonitor by AcceptOrder hook: **** result = ". json_encode( $resp));
		*/
	}
}
add_hook("AcceptOrder",1,"hook_monitis_AcceptOrder");


function hook_monitis_PendingOrder($vars) {
	//m_log( $vars, 'PendingOrder', 'order');
	require_once 'MonitisApp.php';
	require_once 'lib/product.class.php';
	require_once 'lib/services.class.php';
	
	$orderid = $vars['orderid'];
/*	
	$adminuser = MonitisConf::getAdminName();
	$oService = new servicesClass();
	$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
	$iOrder = localAPI( "getorders", $values, $adminuser);
	$product = $oService->product_by_order( $orderid, $iOrder, $adminuser );
*/	
	
	$oService = new servicesClass();
	
	$oService->deactiveMonitorByOrder( $orderid );
}
add_hook("PendingOrder",1,"hook_monitis_PendingOrder");


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


