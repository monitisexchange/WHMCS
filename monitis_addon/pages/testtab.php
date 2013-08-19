<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
require_once ('../modules/addons/monitis_addon/lib/services.class.php');
require_once ('../modules/addons/monitis_addon/lib/client.class.php');
// set_time_limit(3000);

//define('LANG_ORDER', $_ADDONLANG['order'] );
//$LANG_ORDER = $_ADDONLANG['order'];
//_dump($_ADMINLANG);

// 


//$ext_monitors = $oWHMCS->extServerMonitors($serverID);




 
//$order_status = json_decode( MONITIS_ORDER_STATUS, true);


/*
echo '<ul>';
foreach( $order_status as $key=>$val) {
	echo '<li><lable>'.ucfirst($key).'</lable> ';
	echo '<select name="'.$key.'">';
	foreach( $order_status[$key] as $k=>$v) {
		$selected = '';
		if( $order_status[$key][$k] > 0 ) $selected = 'selected';
		echo '<option value="'.$k.'" '.$selected.' />'.$order_title[$k].'</option>';
	}
	echo '</select>';
	echo '</li>';
}
echo '</ul>';
*/





//_db_table ( 'mod_monitis_server_available' );
//
//$result = mysql_query("DROP TABLE IF EXISTS `mod_monitis_server_available` ");
/*

$orderid = 88;

$action = '';
$monitor_id = 0;
$product = null;
$oService = new servicesClass();
$productMonitor = $oService->productMonitorByOrderId( $orderid );
if( $productMonitor ) {
	$order_behavior = $productMonitor['product']['order_behavior'];
	$action = $order_behavior['active'];
	$monitor_id = $productMonitor['monitor_id'];
_dump($productMonitor);
} else {
	$adminuser = MonitisConf::getAdminName();
	$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
	$iOrder = localAPI( "getorders", $values, $adminuser);
	$product = $oService->product_by_order( $orderid, $iOrder, $adminuser );
	if( $product && isset( $product['order_behavior'] ) && !empty($product['order_behavior']) ) {
		$order_behavior = json_decode($product['order_behavior'], true);
		$productMonitor = $oService->productMonitorByOrderId( $orderid );
		$action = $order_behavior['active'];
	}
}
if( !empty($action) ) {

	switch($action) {
		case 'create':
			if( !$productMonitor && $monitor_id == 0 && $product) {		
				$resp = $oService->createMonitor( $product );
echo " ****************************** create <br>";
			} elseif( $monitor_id > 0) {
				$resp = MonitisApi::activateExternal( $monitor_id );	//  $productMonitor["monitor_id"]
echo " ****************************** active <br>";
			}
		break;
		case 'suspend':
			if( $monitor_id > 0 ) {
				$resp = MonitisApi::suspendExternal( $monitor_id );		// $productMonitor["monitor_id"]
echo " ****************************** suspend <br>";
			}
		break;
	}
_logActivity("createMonitor by AcceptOrder hook: **** action = $action result = ". json_encode( $resp));
}
*/

/*
$addonid = 11;
//$oAdd = new servicesClass();
//$oAdd->automateAddMonitorsByAddonid(11);
$adminuser = MonitisConf::getAdminName();
$oSrv = new servicesClass();
$products = $oSrv->addonOrdersList( $addonid, $adminuser );

if( $products && count($products) > 0) {

	for($i=0; $i<count($products); $i++) {
		$resp = $oSrv->createMonitor( $products[$i] );
		if( $resp['status'] && $resp['status'] == 'error') {
		}
_dump( $resp );
	}
}
			//
			
MonitisApp::printNotifications();
*/



//$aOrders = array();
//$arr = ordersList( $aOrders, 0, 25 );
//ordersList( $aOrders, 25, 25 );

//_dump($aOrders);
//_db_table ( 'tblhostingaddons' );
//_db_table ( 'tblhosting' );

//_db_table ( 'mod_monitis_ext_monitors' );
//_db_table ( 'mod_monitis_int_monitors' );
//_db_table ( 'mod_monitis_client' );

//_db_table ( 'tblhosting' );

_db_table ( 'mod_monitis_product_monitor' );



_db_table ( 'mod_monitis_product' );
_db_table ( 'mod_monitis_addon' );
//_db_table ( 'tblorders' );


//_db_table ( 'tblproductgroups' );
//_db_table ( 'tblproductconfigoptionssub' );
//_db_table ( 'tblproductconfigoptions' );


	
/*
_db_table ( 'tbladdonmodules' );
_db_table ( 'tbladmins' );
_db_table ( 'tbladminroles' );
_db_table ( 'tbladminperms' );
*/


//


//_db_table ( 'tbladdons' );
//_db_table ( 'tblproducts' );

//_db_table ( 'tblpricing' );


//_db_table ( 'tblcustomfields' );
//_db_table ( 'tblcustomfieldsvalues' );
//_db_table ( 'tbldomains' );
//_db_table ( 'tbladdonmodules' );
//_db_table ( 'tblregistrars' );
//_db_table ( 'tblwhoislog' );
?>