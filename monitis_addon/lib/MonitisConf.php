<?php
class MonitisConf {
	private static $configs = array('apiKey', 'secretKey', 'newServerMonitors');
	private static $default_settings = '{
		"ping":{
			"interval":1,"timeout":1000,"locationIds":[1,9,10],
			"available":1,
			"autocreate":1,
			"autolink":1,
			"suspendmsg":"Monitor suspended"
		},
		"cpu":{
			"LINUX":{"usedMax":90,"kernelMax":90,"idleMin":0,"ioWaitMax":90,"niceMax":90},
			"WINDOWS":{"usedMax":100,"kernelMax":90},
			"OPENSOLARIS":{"usedMax":90,"kernelMax":90},
			"available":0,
			"autocreate":0,
			"autolink":0,
			"suspendmsg":"Monitor suspended"
		},	
		"memory":{
			"LINUX":{"freeLimit":2000,"freeSwapLimit":1000,"bufferedLimit":3000,"cachedLimit":3000},
			"WINDOWS":{"freeLimit":2000,"freeSwapLimit":1000,"freeVirtualLimit":3000},
			"OPENSOLARIS":{"freeLimit":2000,"freeSwapLimit":1000},
			"available":0,
			"autocreate":0,
			"autolink":0,
			"suspendmsg":"Monitor suspended"
		},	
		"drive":{"freeLimit":30,
			"available":0,
			"autocreate":0,
			"autolink":0,
			"suspendmsg":"Monitor suspended"
		},
		"http":{"interval":1,"timeout":10,"locationIds":[1,9,10],
			"available":1
		},
		"https":{"interval":1,"timeout":10,"locationIds":[1,9,10],
			"available":1
		},
		"available":1,
		"max_locations":5
	}';
	
	
	// suspended monitor message
	static $apiKey = '';
	static $secretKey = '';
	
	static $checkInterval = '1,3,5,10,15,20,30,40,60';
	
	//static $monitorAvailable = 1;
	//static $maxLocations = 3;
	
	static $newServerMonitors = 'ping,http'; //
	static $newAgentPlatform = 'LINUX'; //
	static $defaultgroup = 'WHMCS_ADMINGROUP';
	static $adminuser = '';
	
	static $settings = null;
	
	static function update_config($client_id, $vals) {

		if( $vals && isset($vals['apiKey']) && isset($vals['secretKey'])) {
			self::$apiKey = $vals['apiKey'];
			self::$secretKey = $vals['secretKey'];
			
			$update = array(
				'apiKey' => $vals['apiKey'],
				'secretKey' => $vals['secretKey']
			);
			$where = array('client_id' => $client_id);
			update_query('mod_monitis_client', $update, $where);
		}
	}
	static function update_settings($client_id, $settings_json) {
		
		if( isset($settings_json) ) {
			self::$settings = json_decode($settings_json, true);
			$update = array('settings' => $settings_json);
			$where = array('client_id' => $client_id);
			update_query('mod_monitis_client', $update, $where);
		}
	}
	
	static function getAdminName() {
		$whmcs = new WHMCS_class();
		$adm = $whmcs->getAdminName( 'monitis_addon', 'adminuser');
		return $adm['value'];
	}
	
	static function activeMonitorTypes() {
		$sets = self::$settings;
		$arr = array();
		if( $sets['ping']['autocreate'] > 0 ) $arr[] = 'ping';
		if( $sets['cpu']['autocreate'] > 0 ) $arr[] = 'cpu';
		if( $sets['memory']['autocreate'] > 0 ) $arr[] = 'memory';
		return $arr;
	}
	
	static function load_config() {
	
		//$res = mysql_query('SELECT * FROM mod_monitis_client WHERE client_id=' . MONITIS_CLIENT_ID);
		$res = mysql_query('SELECT * FROM mod_monitis_client');
		if( $res && mysql_num_rows($res) > 0 ) {
			while ($row = mysql_fetch_assoc($res)) {
				self::$apiKey = $row['apiKey'];
				self::$secretKey = $row['secretKey'];
				//self::$newServerMonitors = $row['newServerMonitors'];
				self::$settings = json_decode($row['settings'], true);
			}
			return true;
		} else 
			return false;
	}
	
	static function setupDB( ) {
		$values = array(
			'client_id' => MONITIS_CLIENT_ID,
			'apiKey' => self::$apiKey,
			'secretKey' => self::$secretKey,
			'settings' => self::$default_settings
			//'newServerMonitors' => 	'ping'			//self::$newServerMonitors
		);
		self::$settings =json_decode(self::$default_settings, true);
		insert_query('mod_monitis_client', $values);
	}
}