<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
require_once ('../modules/addons/monitis_addon/lib/services.class.php');
require_once ('../modules/addons/monitis_addon/lib/client.class.php');
/*
	$result = mysql_query("DROP TABLE `mod_monitis_product_monitor`");
	$query = "CREATE TABLE `mod_monitis_product_monitor` (
				`server_id` INT NOT NULL,
				`product_id` INT NOT NULL,
				`type` varchar(50),
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(50),
				`user_id` INT NOT NULL,
				`orderid` INT NOT NULL,
				`ordernum` varchar(255),
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	$result = mysql_query($query);
*/	


$whmcs = new WHMCS_class();
$adm = $whmcs->getAdminName( 'monitis_addon', 'adminuser');

_dump($adm);

_db_table ( 'tbladdonmodules' );

/*
_db_table ( 'tbladmins' );
_db_table ( 'tbladminroles' );

_db_table ( 'tbladminperms' );
*/
//_dump($iOrder);

//_db_table ( 'mod_monitis_server_available' );
//


// 70.179.172.63
// monitis_abc123
// accesskey=monitis_abc123
//http://78.47.109.157/whmcs/includes/api.php?ip=70.179.172.63&accesskey=monitis_abc123
/*
function whmcsPOST ( & $postfields ) {
	
	$url = "http://78.47.109.157/whmcs/includes/api.php"; # URL to WHMCS API file goes here
	$query_string = "";
	foreach ($postfields AS $k=>$v) {
		$query_string .= "$k=".urlencode($v)."&";
	}
	
echo "query_string = ".$query_string."<br>";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$jsondata = curl_exec($ch);
//echo "jsondata - ".$jsondata."<br>";
	if (curl_error($ch)) 
		die("Connection Error: ".curl_errno($ch).' - '.curl_error($ch));
	curl_close($ch);

	$arr = json_decode($jsondata, true); # Decode JSON String
	return $arr;
	
}


$username = "admin"; # Admin username goes here
$password = "M0niti$"; # Admin password goes here
// '66407c092ca1c257c3d671b54399502572204fad';

$postfields = array();
$postfields["username"] = $username;
$postfields["password"] = md5($password);
$postfields["accesskey"] = "monitis_abc123";
$postfields["action"] = "getadmindetails";
$postfields["responsetype"] = "json";

$arr = whmcsPOST ( $postfields );
_dump($arr); # Output XML Response as Array

$permissions = explode(",", $arr['allowedpermissions']);
_dump($permissions);

*/



//_db_table ( 'tblhosting' );

/*
$oClient = new monitisclientClass();
$monitors = $oClient->clientMonitors( 3 );
_dump($monitors);
_db_table ( 'mod_monitis_product' );
*/



/*
_db_table ( 'mod_monitis_int_monitors' );
_db_table ( 'mod_monitis_ext_monitors' );
_db_table ( 'mod_monitis_product_monitor' );
*/
//_db_table ( 'tblorders' );


//_db_table ( 'tblproductgroups' );
//_db_table ( 'tblproductconfigoptionssub' );
//_db_table ( 'tblproductconfigoptions' );
/*
_db_table ( 'mod_monitis_product' );
_db_table ( 'mod_monitis_addon' );
*/
//_db_table ( 'tblhostingaddons' );
//_db_table ( 'tblhosting' );
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


