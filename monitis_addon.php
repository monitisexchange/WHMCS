<?php

/*
 * Standard structure for WHMCS addons. The functions are callbacks
 * that WHMCS uses to delegate handling user input and/or rendering output.
 */

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

// Includes
include_once('monitis_api.php');
include_once('monitis_addon_view.php');
include_once('monitis_addon_helper.php');

/*
 * Addon configuration options
 */
function monitis_addon_config() {
    $configarray = array(
    "name" => "Monitis Addon",
    "description" => "Integration with Monitis",
    "version" => "1.0",
    "author" => "Monitis",
    "language" => "english",
    "fields" => array(
        "apikey" => array ("FriendlyName" => "API Key", "Type" => "text", "Size" => "25", "Description" => "Monitis API Key", "Default" => "", ),
        "secretkey" => array ("FriendlyName" => "Secret Key", "Type" => "password", "Size" => "25", "Description" => "Monitis Secret Key", ),
        "endpoint" => array ("FriendlyName" => "API Endpoint", "Type" => "text", "Size" => "25", "Description" => "API Endpoint", "Default" => "https://www.monitis.com/api", ),
    ));
    return $configarray;
}

/*
 * Handle addon activation
 */
function monitis_addon_activate() {
  // Create Custom DB Table
  // TODO handle DB failures
  $query = "CREATE TABLE `mod_monitis_addon` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`demo` TEXT NOT NULL )";
	$result = mysql_query($query);
  $query = "CREATE TABLE `mod_monitis_server` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,"
         . "`ip_addr` TEXT NOT NULL, "
         . "`monitored` BOOL, test_id INT )";
	$result = mysql_query($query);
  return array('status'=>'success','description'=>'');
}

/*
 * Handle addon deactivation
 */
function monitis_addon_deactivate() {
  // Remove Custom DB Table
  // TODO handle db failures 
  $query = "DROP TABLE `mod_monitis_addon`, `mod_monitis_server`";
	$result = mysql_query($query);
  return array('status'=>'success','description'=>'Deactivation successful');
}

/*
 * HTML snippet for addon admin UI embedded in WHMCS UI
 */
function monitis_addon_output($vars) {
  // Dispatch output based on get/post params
  // default to the server table
  $success_msg = array();
  $error_msg = array();

  if ($_POST) {
    $action = $_POST['action'];
    if ($action) {
      foreach ($_POST['servers'] as $server_ip) {
        if ($action == 'add') {
          $result = add_ping_monitor($vars, $server_ip);
        }
        elseif ($action == 'remove') {
          $result = remove_ping_monitor($vars, $server_ip);
        }
        elseif ($action == 'remove_deleted') {
          $result = remove_ping_monitor($vars, $server_ip);
        }
        if ($msg = $result['ok']) {
          $success_msg[] = $msg;
        }
        else {
          $error_msg[] = $result['err'];
        }
      }
    }
  }
  else { // no POST, check for specific GET-based views
    // Detail view for a specific test ID
    if ($view = $_GET['view'] == 'detail') {
      if ($test_id = $_GET['test_id']) {
        print view_detail($vars, $test_id);
      }
    }
  }

  print view_status_messages($success_msg, $error_msg);
  print view_server_table($vars);
  //print view_deleted_server_table($vars);
}

/*
 * HTML snippet for addon client UI embedded in WHMCS UI
 */
function monitis_addon_clientarea() {
  $configarray = array(
    "name" => "Monitis Addon Name",
    "description" => "Client area for Monitis addon",
    "version" => "1.0",
    "author" => "Jeremiah Shirk"
  );
  return $configarray;
}

/*
 * HTML snippet embedded in WHMCS sidebar for addon content
 */
function monitis_addon_sidebar() {
  $modulelink = $vars['modulelink'];
  $sidebar = <<<EOF
  <span class="header">
    <img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" />Monitis Links</span>
    <ul class="menu">
      <li><a href="http://portal.monitis.com/">Monitis Dashboard</a></li>
    </ul>
EOF;
  return $sidebar;
}

?>
