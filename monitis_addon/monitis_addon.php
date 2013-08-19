<?php
// FOR DEBUG
//error_reporting(-1);
error_reporting(E_ALL & ~E_NOTICE);

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

require_once 'MonitisApp.php';

function monitis_addon_config() {
	$configarray = array(
		"name" => "Monitis Addon",
		"description" => "www.monitis.com monitoring services",
		"version" => "1.0",
		"author" => "Monitis",
		"logo" => '../modules/addons/monitis_addon/static/img/logo-big.png',
		"language" => "english",
			"fields" => array(
				"confdescription" => array (
						"FriendlyName" => "",
						"Description" => "<b>Please grant access to your user from below checkboxes and save changes.<br/>
							After that go to Addons->Monitis Addon to finish setup.</b>",
				),
				"adminuser" => array ("FriendlyName" => "WHMCS Admin", "Type" => "text", "Size" => "25",
                              "Description" => "Admin user name for WHMCS API access", "Default" => "", )
    ));
	return $configarray;
}

/*
 * Handle addon activation
 */
function monitis_addon_activate() {
	//$result = mysql_query("DROP TABLE `mod_monitis_product_monitor`");
	$query = "CREATE TABLE `mod_monitis_product_monitor` (
				`server_id` INT NOT NULL,
				`product_id` INT NOT NULL,
				`type` varchar(50),
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(50),
				`available` INT default 1,
				`user_id` INT NOT NULL,
				`orderid` INT NOT NULL,
				`ordernum` varchar(255),
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	$result = mysql_query($query);
	//$result = mysql_query("DROP TABLE `mod_monitis_product`");	
	$query = "CREATE TABLE `mod_monitis_product` (
				`product_id` INT NOT NULL,
				`settings`  TEXT,
				`order_behavior` TEXT,
				`notification_rule` TEXT,
				`status` varchar(50),
				PRIMARY KEY ( `product_id` )
				);";
	$result = mysql_query($query);
	//$result = mysql_query("DROP TABLE `mod_monitis_addon`");
	$query = "CREATE TABLE `mod_monitis_addon` (
				`addon_id` INT NOT NULL,
				`type` varchar(50),
				`settings`  TEXT,
				`order_behavior` TEXT,
				`notification_rule` TEXT,
				`status` varchar(50) default 'active',
				PRIMARY KEY ( `addon_id` )
				);";
	$result = mysql_query($query);

	$query = "CREATE TABLE `mod_monitis_client` (
				`client_id` INT,
				`apiKey` VARCHAR( 255 ) NOT NULL,
				`secretKey` VARCHAR( 255 ) NOT NULL,
				`settings`  TEXT NOT NULL,
				PRIMARY KEY ( `client_id` )
				);";
	$result = mysql_query($query);
	
	$query = "CREATE TABLE `mod_monitis_ext_monitors` (
				`client_id` INT NOT NULL,
				`server_id` INT NOT NULL,
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(100),
				`available` INT default 1,
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	$result = mysql_query($query);
	
	$query = "CREATE TABLE `mod_monitis_int_monitors` (
				`client_id` INT NOT NULL,
				`server_id` INT NOT NULL,
				`agent_id` INT NOT NULL,
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(100),
				`available` INT default 1,
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	$result = mysql_query($query);	

	$query = "CREATE TABLE `mod_monitis_contact_group` (
				`group_id` INT NOT NULL,
				`notification_rule` TEXT,
				PRIMARY KEY ( `group_id` )
				);";
	$result = mysql_query($query);	
	
	//$result = mysql_query("DROP TABLE IF EXISTS `mod_monitis_server_available` ");

	MonitisConf::setupDB();
	
	//MonitisConf::admin_setupDB();
	return array('status'=>'success','description'=>'Monitis addon activation successful');
}


function monitis_addon_deactivate() {

	$query = "DROP TABLE  `mod_monitis_client`, `mod_monitis_ext_monitors`, `mod_monitis_int_monitors`";
	$result = mysql_query($query);
	$query = "DROP TABLE `mod_monitis_product`, `mod_monitis_product_monitor`, `mod_monitis_addon`, `mod_monitis_contact_group`";
	$result = mysql_query($query);
	
	return array('status'=>'success','description'=>'Monitis addon deactivation successful');
}

function monitis_addon_output($vars) {
//	logActivity("MONITIS ADMIN LOG *************** <b>monitis_addon_output</b>  vars = ". json_encode($vars));
	MonitisRouter::route();
}


function monitis_addon_sidebar() {
	//$modulelink = $vars['modulelink'];
	$sidebar = <<<EOF
	<span class="header">
    <img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" />Monitis Links</span>
	<ul class="menu">
		<li><a href="http://portal.monitis.com/">Monitis Dashboard</a></li>
	</ul>
EOF;
	return $sidebar;
}


function monitis_addon_upgrade($vars) {
 
    $version = $vars['version'];

    # Run SQL Updates for V1.0 to V1.1
    if ($version < 1.1) {

		logActivity("MONITIS ADMIN LOG ***** <b>monitis_addon_upgrade</b>  vars = ". json_encode($vars));
	/*	$query = "CREATE TABLE `mod_monitis_server_available` (
					`server_id` INT NOT NULL,
					`available` INT default 1,
					PRIMARY KEY ( `server_id` )
					);";
		$result = mysql_query($query);*/
    }
 
    # Run SQL Updates for V1.1 to V1.2
    if ($version < 1.2) {
		//$query = "ALTER `mod_addonexample` ADD `demo3` TEXT NOT NULL ";
		//$result = mysql_query($query);
    }
 
}

