<?php
// FOR DEBUG
//error_reporting(-1);
error_reporting(E_ALL & ~E_NOTICE);

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");
	
require_once 'monitisapp.php';

function monitis_addon_config() {

	$configarray = array(
		"name" => "Monitis monitoring",
		"description" => "Monitis addon for monitoring automation for you and your clients. www.monitis.com",
		"version" => "1.0",
		"author" => "Monitis",
		"logo" => '../modules/addons/monitis_addon/static/img/logo-big.png',
		"language" => "english",
			"fields" => array(				
				"adminuser" => array("FriendlyName" => "WHMCS Admin", "Type" => "text", "Size" => "25",
                                "Description" => "Username or ID of the admin user under which to execute the WHMCS API call", "Default" => "" )
				));
				

	if(isset($_REQUEST) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' ) {
		//&& isset($_REQUEST['msave_monitis_addon']) && $_REQUEST['msave_monitis_addon'] == 'Save Changes') {
		$adminuser = $_REQUEST['fields']['monitis_addon']['adminuser'];
		
		MonitisConf::$adminName = MonitisHelper::checkAdminName();	
		$update = array(
			'admin_name' => MonitisConf::$adminName
		);

		$where = array('client_id' => MONITIS_CLIENT_ID );
		update_query('mod_monitis_setting', $update, $where);
	}

	return $configarray;
}

/*
 * Handle addon activation
 */
function monitis_addon_activate() {
	//$result = mysql_query("DROP TABLE `mod_monitis_product_monitor`");
	$query = "CREATE TABLE `".MONITIS_SETTING_TABLE."` (
				`client_id` INT,
				`apiKey` VARCHAR(255) NOT NULL,
				`secretKey` VARCHAR(255) NOT NULL,
				`authToken` VARCHAR(255) NOT NULL,
				`authToken_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`settings`  TEXT NOT NULL,
				`locations` TEXT NOT NULL,
				`locations_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`admin_name` VARCHAR(255) DEFAULT '',
				PRIMARY KEY ( `client_id` )
				);";
	mysql_query($query);
	
	$query = "CREATE TABLE `mod_monitis_product_monitor` (
				`server_id` INT NOT NULL,
				`product_id` INT NOT NULL,
				`service_id` INT NOT NULL,
				`type` varchar(50),
				`option_id` INT NOT NULL,
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(50),
				`user_id` INT NOT NULL,
				`order_id` INT NOT NULL,
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	mysql_query($query);
	
	$query = "CREATE TABLE `mod_monitis_ext_monitors` (
				`client_id` INT NOT NULL,
				`server_id` INT NOT NULL,
				`monitor_id` INT NOT NULL,
				`monitor_type` varchar(100),
				`available` INT default 1,
				`publickey` varchar(255),
				PRIMARY KEY ( `monitor_id` )
				);";
	mysql_query($query);
	
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
	mysql_query($query);	

	$query = "CREATE TABLE `mod_monitis_product` (
				`product_id` INT NOT NULL,
				`settings`  TEXT,
				`status` varchar(50) default 'active',
				PRIMARY KEY ( `product_id` )
				);";
	mysql_query($query);
	$query = "CREATE TABLE `mod_monitis_addon` (
				`addon_id` INT NOT NULL,
				`type` varchar(50),
				`settings`  TEXT,
				`status` varchar(50) default 'active',
				PRIMARY KEY ( `addon_id` )
				);";
	mysql_query($query);
	$query = 'CREATE TABLE `mod_monitis_options`(
				`option_id` INT NOT NULL,
				`type` VARCHAR(50),
				`settings` TEXT,
				`is_active` TINYINT(1) DEFAULT 0,
				PRIMARY KEY (`option_id`)
			)';
	mysql_query($query);
	
	// mod_monitis_report
	$query = "CREATE TABLE `".MONITIS_HOOK_REPORT_TABLE."` (
			`id` int NOT NULL AUTO_INCREMENT,
			`vars` TEXT,
			`json` TEXT,
			`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
	)";
	mysql_query($query);
	
	// mod_monitis_log
	$query = "CREATE TABLE `".MONITIS_LOG_TABLE."` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`type` VARCHAR( 20 ) DEFAULT 'json',
			`title` VARCHAR( 255 ) DEFAULT '',
			`description` TEXT,
			`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`id`)
	)";
	mysql_query($query);
	
	// `auth_token` VARCHAR(255) NOT NULL,
	$query = "CREATE TABLE `".MONITIS_USER_TABLE."` (
			`user_id` INT NOT NULL,
			`api_key` VARCHAR(255) NOT NULL,
			`secret_key` VARCHAR(255) NOT NULL,
			PRIMARY KEY (`user_id`)
	)";
	mysql_query($query);
	
	MonitisConf::setupDB();

	return array('status'=>'success','description'=>'Monitis addon activation successful');
}


function monitis_addon_deactivate() {

	if(MONITIS_REMOVE_TABLES) {
		$query = "DROP TABLE  `".MONITIS_SETTING_TABLE."`, `mod_monitis_ext_monitors`, `mod_monitis_int_monitors`";
		mysql_query($query);
		$query = "DROP TABLE `mod_monitis_product`, `mod_monitis_product_monitor`, `mod_monitis_addon`, `".MONITIS_HOOK_REPORT_TABLE."`, `".MONITIS_LOG_TABLE."`, `".MONITIS_USER_TABLE."`";
		mysql_query($query);
		$query = "DROP TABLE `mod_monitis_options`";
		mysql_query($query);
	}
	return array('status'=>'success','description'=>'Monitis addon deactivation successful');
}

function monitis_addon_output($vars) {
	MonitisRouter::route();
}


function monitis_addon_sidebar() {
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
    }
}

