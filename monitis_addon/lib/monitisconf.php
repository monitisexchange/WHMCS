<?php
class MonitisConf {
	private static $default_settings = '{
"ping":{"interval":1,"timeout":1000,"locationIds":[1,9,10],
	"available":1,"autocreate":1,"suspendmsg":"Monitor suspended","locationsMax":5
},
"cpu":{
	"LINUX":{"usedMax":90,"kernelMax":90,"idleMin":0,"ioWaitMax":90,"niceMax":90},
	"WINDOWS":{"usedMax":100,"kernelMax":90},
	"OPENSOLARIS":{"usedMax":90,"kernelMax":90},
	"available":0,"autocreate":1,"suspendmsg":"Monitor suspended"
},
"memory":{
	"LINUX":{"freeLimit":2000,"freeSwapLimit":1000,"bufferedLimit":3000,"cachedLimit":3000},
	"WINDOWS":{"freeLimit":2000,"freeSwapLimit":1000,"freeVirtualLimit":3000},
	"OPENSOLARIS":{"freeLimit":2000,"freeSwapLimit":1000},
	"available":0,"autocreate":1,"autolink":0,"suspendmsg":"Monitor suspended"},	
"drive":{"freeLimit":30,
	"available":0,"autocreate":0,"suspendmsg":"Monitor suspended"
},
"http":{"interval":1,"timeout":10,"locationIds":[1,9,10],"available":1,"locationsMax":5},
"https":{"interval":1,"timeout":10,"locationIds":[1,9,10],"available":1,"locationsMax":5},
"groups":{
        "external":{"groupId":null,"groupName":"no alert", "alert":null },
        "internal":{"groupId":null,"groupName":"no alert", "alert":null}
},
"available":1,"locationsMax":5,"timezone":0
}';

	// suspended monitor message
	static $apiKey = '';
	static $secretKey = '';
	
	static $adminName = '';
	static $parentDomain = '';
	
	static $authToken = null;
	static $authTokenHour = 10;
	
	static $checkInterval = '1,3,5,10,15,20,30,40,60';

	//static $newServerMonitors = 'ping,http'; //
	static $newAgentPlatform = 'LINUX'; //

	static $jqueryClientTheme = 'smoothness';
	static $jqueryAdminTheme = 'redmond';
	static $adminuser = '';

	static $settings = null;

	static $locations = null;
	static $locationsDays = 0;
	
	static $apiServerError = '';
	
	

	static function update_config( $vals ) {

		if( $vals && isset($vals['apiKey']) && isset($vals['secretKey'])) {

			MonitisHelper::checkAdminName();
			
			self::$apiKey = $vals['apiKey'];
			self::$secretKey = $vals['secretKey'];
			self::$adminName = MonitisHelper::checkAdminName();
			
			$update = array(
				'apiKey' => $vals['apiKey'],
				'secretKey' => $vals['secretKey'],
				'admin_name' => self::$adminName
			);

			$where = array('client_id' => MONITIS_CLIENT_ID );
			update_query(MONITIS_SETTING_TABLE, $update, $where);

			self::$settings["timezone"] = $vals['timezone'];
			self::$settings["order_behavior"] = self::setupBehavior(MONITIS_ORDER_BEHAVIOR);
			self::$settings["user_behavior"] = self::setupBehavior(MONITIS_USER_STATUS_BEHAVIOR);
		
			self::$parentDomain = MonitisHelper::parentDomain();
			self::$settings["parentDomain"] = self::$parentDomain;
			
			// set autoToken
			self::update_token();

			
			// setup notifications
			$oNot = new notificationsClass();
			$resp = $oNot->createDefaultGroup();
			self::update_settings( json_encode( self::$settings ) );
			
			// setup locations
			$locations = MonitisApiHelper::getExternalLocations();
			self::update_locations( $locations );
		}
	}

	static function update_settings( $settings_json ) {

		if( isset($settings_json) ) {
			self::$settings = json_decode($settings_json, true);
			$update = array('settings' => $settings_json);
			$where = array('client_id' => MONITIS_CLIENT_ID );
			update_query(MONITIS_SETTING_TABLE, $update, $where);
			
		}
	}

	static function update_parentDomain() {
	
		if(self::$settings && isset(self::$settings['parentDomain']) && !empty(self::$settings['parentDomain'])) {
			self::$parentDomain = self::$settings['parentDomain'];
		} else {
			self::$parentDomain = MonitisHelper::parentDomain();
			self::$settings["parentDomain"] = self::$parentDomain;
			self::update_settings( json_encode( self::$settings ) );
		}

	}
	
	static function update_locations($locations) {

		if( $locations ) {
			self::$locations = $locations;
			$update = array('locations' => json_encode($locations, true), 'locations_update' => date("Y-m-d H:i:s", time()));
			$where = array('client_id' => MONITIS_CLIENT_ID );
			update_query(MONITIS_SETTING_TABLE, $update, $where);
		}
	}
	
	static function update_token() {

		$authToken = MonitisApi::getAuthToken();
		if( $authToken ) {
			self::$authToken = $authToken;
			$update = array('authToken' => $authToken, 'authToken_update' => date("Y-m-d H:i:s", time()));
			$where = array('client_id' => MONITIS_CLIENT_ID );
			update_query(MONITIS_SETTING_TABLE, $update, $where);
			return true;
		} else {
			echo '<div><b> Monitis server not respose!</b></div>';
//monitisLog("<b>Monitis server not respose!</b>");
			return false;
		}
	}
	
	static function load() {
		// HOUR
		$row = monitisSqlHelper::objQuery('SELECT *, TIMESTAMPDIFF(DAY, locations_update, NOW()) as locs,
			TIMESTAMPDIFF(HOUR, authToken_update, NOW()) as token
			FROM '.MONITIS_SETTING_TABLE);
		if($row) {
			self::$apiKey = $row['apiKey'];
			self::$secretKey = $row['secretKey'];
			self::$authToken = $row['authToken'];
			self::$adminName = $row['admin_name'];
			
			self::$settings = json_decode($row['settings'], true);
			self::$locations = json_decode($row['locations'], true);
			
			if($row['locs'] > self::$locationsDays ) {
				self::update_locations( MonitisApiHelper::getExternalLocations() );
			}
			if($row['token'] > self::$authTokenHour ) {
				self::update_token();
			}
			self::update_parentDomain();
			
			return true;
		}
		// 
		if(empty(self::$locations) && !empty(self::$secretKey)) {
			self::update_locations( MonitisApiHelper::getExternalLocations() );
		}
		if(empty(self::$authToken)) {
			self::update_token();
		}
		return false;
	}

	// MONITIS_ORDER_BEHAVIOR
	// MONITIS_USER_STATUS_BEHAVIOR
	static function setupBehavior($defaultBehavior) {
		$status = json_decode($defaultBehavior, true);
		$arr = array();
		foreach ($status as $key => $val) {
			$item = $status[$key];
			foreach($status[$key] as $k => $v ) {
				if($v > 0) {
					$arr[$key] = $k;
				}
			}
		}
		return $arr;
	}

	static function setupDB() {

	
		$row = monitisSqlHelper::objQuery('SELECT * FROM '.MONITIS_SETTING_TABLE.' WHERE client_id='.MONITIS_CLIENT_ID );

		if( !$row ) {
			$default_settings = json_decode(self::$default_settings, true);

			$default_settings["order_behavior"] = self::setupBehavior(MONITIS_ORDER_BEHAVIOR);
			$default_settings["user_behavior"] = self::setupBehavior(MONITIS_USER_STATUS_BEHAVIOR);

			$values = array(
				'client_id' => MONITIS_CLIENT_ID,
				'apiKey' => self::$apiKey,
				'secretKey' => self::$secretKey,
				'settings' => json_encode( $default_settings )
			);
			self::$settings = $default_settings;
			insert_query(MONITIS_SETTING_TABLE, $values);
		} else {
		

		}
		//return true;
	}
}