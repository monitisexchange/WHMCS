<?php
class MonitisConf {
	private static $configs = array('apiKey', 'secretKey', 'newServerMonitors');
	private static $default_settings = '{"ping":{"interval":1,"timeout":1000,"locationIds":[1,9,10]},"cpu":{"LINUX":{"usedMax":90,"kernelMax":90,"idleMin":0,"ioWaitMax":90,"niceMax":90},"WINDOWS":{"usedMax":100,"kernelMax":90},"OPENSOLARIS":{"usedMax":90,"kernelMax":90}},"memory":{"LINUX":{"freeLimit":2000,"freeSwapLimit":1000,"bufferedLimit":3000,"cachedLimit":3000},"WINDOWS":{"freeLimit":2000,"freeSwapLimit":1000,"freeVirtualLimit":3000},"OPENSOLARIS":{"freeLimit":2000,"freeSwapLimit":1000}},"drive":{"freeLimit":30},"http":{"interval":1,"timeout":10,"locationIds":[1,9,10]},"https":{"interval":1,"timeout":10,"locationIds":[1,9,10]}}';
	static $apiKey = '';
	static $secretKey = '';
	static $newServerMonitors = 'ping,http'; //
	
	static $newAgentPlatform = 'LINUX'; //
	static $settings = null;
	static $defaultgroup = 'WHMCS_ADMINGROUP';

	static function update($conf, $value) {	// ??? remove
		if (!in_array($conf, self::$configs))
			return;
		self::$$conf = $value;
		$update = array('value' => self::$$conf);
		$where = array('conf' => $conf);
		update_query('mod_monitis_conf',$update,$where);
	}
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
			if( isset($vals['settings']) ) {
				self::$settings =json_decode($vals['settings'], true);
				$update['settings'] = $vals['settings'];
			}
			if( $update && count($update) > 0) {
				$where = array('client_id' => $client_id);
				update_query('mod_monitis_client', $update, $where);		
			}
		}
	}	
	
	
	static function load_config() {
	
		$res = mysql_query('SELECT * FROM mod_monitis_client WHERE client_id=' . MONITIS_CLIENT_ID);
		if( $res && mysql_num_rows($res) > 0 ) {
			//$row = mysql_fetch_assoc($res);
			while ($row = mysql_fetch_assoc($res)) {
		self::$apiKey = $row['apiKey'];
		self::$secretKey = $row['secretKey'];
		self::$newServerMonitors = $row['newServerMonitors'];
		self::$settings = json_decode($row['settings'], true);
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
			'newServerMonitors' => 	'ping'			//self::$newServerMonitors
		);
		self::$settings =json_decode(self::$default_settings, true);
		insert_query('mod_monitis_client', $values);
	}
}