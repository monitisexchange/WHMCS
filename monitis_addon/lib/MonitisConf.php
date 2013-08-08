<?php
class MonitisConf {
	private static $configs = array('apiKey', 'secretKey', 'newServerMonitors');
	//private static $default_settings = '{"ping":{"interval":1,"timeout":1000,"locationIds":[1,9,10]},"cpu":{"LINUX":{"usedMax":90,"kernelMax":90,"idleMin":0,"ioWaitMax":90,"niceMax":90},"WINDOWS":{"usedMax":100,"kernelMax":90},"OPENSOLARIS":{"usedMax":90,"kernelMax":90}},"memory":{"LINUX":{"freeLimit":2000,"freeSwapLimit":1000,"bufferedLimit":3000,"cachedLimit":3000},"WINDOWS":{"freeLimit":2000,"freeSwapLimit":1000,"freeVirtualLimit":3000},"OPENSOLARIS":{"freeLimit":2000,"freeSwapLimit":1000}},"drive":{"freeLimit":30},"http":{"interval":1,"timeout":10,"locationIds":[1,9,10]},"https":{"interval":1,"timeout":10,"locationIds":[1,9,10]},"available":1,"max_locations":5}';

	private static $default_settings = '{
		"ping":{"interval":1,"timeout":1000,"locationIds":[1,9,10],"available":1},
		"cpu":{
			"LINUX":{"usedMax":90,"kernelMax":90,"idleMin":0,"ioWaitMax":90,"niceMax":90},
			"WINDOWS":{"usedMax":100,"kernelMax":90},
			"OPENSOLARIS":{"usedMax":90,"kernelMax":90},
			"available":1
		},	
		"memory":{
			"LINUX":{"freeLimit":2000,"freeSwapLimit":1000,"bufferedLimit":3000,"cachedLimit":3000},
			"WINDOWS":{"freeLimit":2000,"freeSwapLimit":1000,"freeVirtualLimit":3000},
			"OPENSOLARIS":{"freeLimit":2000,"freeSwapLimit":1000},
			"available":1
		},	
		"drive":{"freeLimit":30,"available":1},
		
		"http":{"interval":1,"timeout":10,"locationIds":[1,9,10]},
		"https":{"interval":1,"timeout":10,"locationIds":[1,9,10]},
		"available":1,
		"max_locations":5
	}';
	
	static $apiKey = '';
	static $secretKey = '';
	
	static $checkInterval = '1,3,5,10,15,20,30,40,60';
	

	static $monitorAvailable = 1;
	static $maxLocations = 3;
	
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
	static function update_settings($client_id, $vals) {
		$update = array();
		if( $vals ) {
			if( isset($vals['newServerMonitors']) ) {
				self::$newServerMonitors = $vals['newServerMonitors'];
				$update['newServerMonitors'] = $vals['newServerMonitors'];
			}
			//if( isset($vals['monitorAvailable']) ) {
			//	self::$monitorAvailable = $vals['monitorAvailable'];
			//	$update['available'] = $vals['monitorAvailable'];
			//}
			if( isset($vals['settings']) ) {
				self::$settings =json_decode($vals['settings'], true);
				$update['settings'] = $vals['settings'];
				
				//self::$monitorAvailable = self::$settings['available'];
				//self::$maxLocations = self::$settings['max_locations'];
			}
			if( $update && count($update) > 0) {
				$where = array('client_id' => $client_id);
				update_query('mod_monitis_client', $update, $where);		
			}
		}
	}
	
	static function settingsByType( $type ) {
		self::$settings[$type]["available"] = self::$monitorAvailable;
		self::$settings[$type]["max_locations"] = self::$maxLocations;
		return self::$settings[$type];
	}
	
	static function getAdminName() {
		$whmcs = new WHMCS_class();
		$adm = $whmcs->getAdminName( 'monitis_addon', 'adminuser');
		return $adm['value'];
	}
	
	static function load_config() {
	
		$res = mysql_query('SELECT * FROM mod_monitis_client WHERE client_id=' . MONITIS_CLIENT_ID);
		if( $res && mysql_num_rows($res) > 0 ) {
			//$row = mysql_fetch_assoc($res);
			while ($row = mysql_fetch_assoc($res)) {
				self::$apiKey = $row['apiKey'];
				self::$secretKey = $row['secretKey'];
				self::$newServerMonitors = $row['newServerMonitors'];
				self::$monitorAvailable = $row['available'];
				self::$settings = json_decode($row['settings'], true);
				
				self::$monitorAvailable = self::$settings['available'];
				self::$maxLocations = self::$settings['max_locations'];
			}
			//$oWhmcs = new WHMCS_class($client_id);
			//$row = $oWhmcs->clientInfo();

			return true;
		} else 
			return false;
	}
	
	static function setupDB( ) {
		$values = array(
			'client_id' => MONITIS_CLIENT_ID,
			'apiKey' => self::$apiKey,
			'secretKey' => self::$secretKey,
			'settings' => self::$default_settings,
			//'available' => self::$monitorAvailable,
			'newServerMonitors' => 	'ping'			//self::$newServerMonitors
		);
		self::$settings =json_decode(self::$default_settings, true);
		insert_query('mod_monitis_client', $values);
	}
}