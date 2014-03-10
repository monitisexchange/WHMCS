<?php
define('MONITIS_APP_PATH', dirname(realpath(__FILE__)));

require_once 'config.php';

// Includes lib
require_once 'lib/common.php';
require_once 'lib/whmcs.class.php';
require_once 'lib/notifications.class.php';
require_once 'lib/internal.class.php';
require_once 'lib/monitisconf.php';
require_once 'lib/monitisrouter.php';
require_once 'lib/monitisapi.php';
require_once 'lib/monitisapihelper.php';
require_once 'lib/clientapi.class.php';
require_once 'lib/clientservices.php';


require_once 'lib/servermonitors.php';

MonitisConf::load();

class MonitisApp {
	private static $errors = array();
	private static $warnings = array();
	private static $messages = array();
	
	static function addError($msg) {
		if(!empty($msg)) {
			self::$errors[] = $msg;
		}
	}
	
	static function addMessage($msg) {
		if(!empty($msg)) {
			self::$messages[] = $msg;
		}
	}
	
	static function addWarning($msg) {
		if(!empty($msg)) {
			self::$warnings[] = $msg;
		}
	}
	
	static function printErrors() {
		foreach (self::$errors as $error) {
			echo '<div class="monitis-message errors">'.$error.'<div class="close notification_x">×</div></div>';		
		}
	}
	static function printMessages() {
		foreach (self::$messages as $msg) {
			echo '<div class="monitis-message success">'.$msg.'<span class="close notification_x">×</span></div>';
		}
	}
	static function printWarnings() {
		foreach (self::$warnings as $warning) {
			echo '<div class="monitis-message warning">'.$warning.'<div class="close notification_x">×</div></div>';
		}
	}
	
	static function printNotifications() {
		self::printErrors();
		self::printWarnings();
		self::printMessages();
	}
	
	static function redirect($url) {
		header('location:' . $url);
		exit;
	}
	
	static function getModule($moduleName) {
		$className = 'MonitisModule' . $moduleName;
		require_once MONITIS_APP_PATH . '/modules/' . $moduleName . '/' . $moduleName . '.php';
		return new $className;
	}
}