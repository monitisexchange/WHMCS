<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
require_once ('../modules/addons/monitis_addon/lib/services.class.php');
require_once ('../modules/addons/monitis_addon/lib/client.class.php');
// set_time_limit(3000);



//_db_table ( 'mod_monitis_server_available' );
//
//$result = mysql_query("DROP TABLE IF EXISTS `mod_monitis_server_available` ");



/*
$orderid = 88;
$adminuser = MonitisConf::$adminuser;
if( empty($adminuser) ) {
	$whmcs = new WHMCS_class();
	$adm = $whmcs->getAdminName( 'monitis_addon', 'adminuser');
	$adminuser = $adm['value'];
}
$oService = new servicesClass();
$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
$iOrder = localAPI( "getorders", $values, $adminuser);

$product = $oService->product_by_order( $orderid, $iOrder, $adminuser );
_dump($product);


if( $product ) {

	$resp = $oService->createMonitor( $product );
//	_logActivity("createMonitor by AcceptOrder hook: **** result = ". json_encode( $resp));
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

$array = array('lastname');
$comma_separated = implode(",", $array);

echo $comma_separated; // lastname,email,phone


//$aOrders = array();
//$arr = ordersList( $aOrders, 0, 25 );
//ordersList( $aOrders, 25, 25 );

//_dump($aOrders);
//_db_table ( 'tblhostingaddons' );
//_db_table ( 'tblhosting' );

_db_table ( 'mod_monitis_client' );

//_db_table ( 'tblhosting' );

_db_table ( 'mod_monitis_ext_monitors' );
_db_table ( 'mod_monitis_int_monitors' );
//_db_table ( 'mod_monitis_product_monitor' );
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


