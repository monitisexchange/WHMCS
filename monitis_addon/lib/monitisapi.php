<?php
class MonitisApi {

	static $endpoint = MONITISAPIURL;
	
	static function jsonDecode($result) {
		$err = 'The Monitis API is temporarily unavailable. Please try again later.';
		MonitisConf::$apiServerError = '';
		if(empty($result)) {
			MonitisConf::$apiServerError = $err;
			return array('status'=>'error', 'code'=>101);
		} else {
			$result = utf8_encode($result);
			$resp = json_decode($result, true);
			if(empty($resp) && gettype($resp) != 'array') {
				MonitisConf::$apiServerError = $err;
				return array('status'=>'error', 'code'=>101);
			}
			return $resp;
		}
	}
	
	/* 
	static function getSecretkey() {
		$params = array('apikey' => MonitisConf::$apiKey);
		//$params['apikey'] = MonitisConf::$apiKey;
		$resp = self::requestGet('secretkey', $params);
		if( !@$resp['error'] && $resp['secretkey']) {
			$secretKey = $resp['secretkey'];
			if($secretKey != MonitisConf::$secretKey) {
				$update = array('secretKey' => $secretKey);
				$where = array('client_id' => MONITIS_CLIENT_ID );
				update_query(MONITIS_SETTING_TABLE, $update, $where);
			}
			return $secretKey;
		} else {
			return '';
		}
	}
	*/
	static function getAuthToken() {	
			//if(MonitisConf::$secretKey)
			//	MonitisConf::$secretKey = self::getSecretkey();

			$params = array();
			$params['version'] = MONITIS_API_VERSION;
			$params['action'] = 'authToken';
			$params['apikey'] = MonitisConf::$apiKey;
			$params['secretkey'] = MonitisConf::$secretKey;
			$query = http_build_query($params);
			$url = self::$endpoint . '?' . $query;
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$json = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($json, true);
			
			if( !empty($result) && !isset($result['error']) && isset($result['authToken']) ) {
monitisLog("<b>***************Parent Set authToken</b><p>$json</p>");
				return $result['authToken'];
			} else {
//monitisLog("<b>***************Error authToken</b><p>$json</p>");
				MonitisConf::$authToken = '';
				return null;
			}
	}

	static function getWidget( $params ) {
		return self::requestGet('getWidget', $params);
	}

	static function monitorPublicKey( $params ) {
		$publicKey = null;
		$resp = self::requestGet('getWidget', $params);
		if( $resp && isset($resp['data']) ) {
			$publicKey = $resp['data'];
		}
		return $publicKey;
	}

	static function requestGet($action, $params) {
//self::getSecretkey();
		// TODO: error handling when JSON is not returned
	    
		$authToken = MonitisConf::$authToken;
		if( $authToken) {   
		 
			$params['version'] = MONITIS_API_VERSION;
			$params['action'] = $action;
			$params['apikey'] = MonitisConf::$apiKey;
			$params['authToken'] = $authToken;
			$query = http_build_query($params);
			$url = self::$endpoint . '?' . $query; 
                       
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
			$result = curl_exec($ch);
			curl_close($ch);

			
			monitisLog("GET requestGet **** action = <b>$action</b><p>$url</p><p>$result</p>");

			$json = self::jsonDecode($result);
			if( $json && isset( $json['errorCode']) && $json['errorCode'] == 4 ) {
				if(MonitisConf::update_token())
					return self::requestGet($action, $params);
				else {
					//MonitisConf::$authToken = '';
					return array('status'=>'error', 'msg'=>'Monitis server not response');
				}
			}
			return $json;
		} else {
			if(MonitisConf::update_token())
				return self::requestGet($action, $params);
			else
				return array('status'=>'error', 'msg'=>'Monitis server not response');
		}
		//return self::jsonDecode($result);
	}

	static function requestPost($action, $params) {
		// TODO: error handling when JSON is not returned
		//$authToken = self::authToken();
		$authToken = MonitisConf::$authToken;
		if( $authToken) {
			$params['authToken'] = $authToken;
			$params['validation'] = 'token';
			$params['version'] = MONITIS_API_VERSION;
			$params['action'] = $action;
			$params['apikey'] = MonitisConf::$apiKey;

			$query = http_build_query($params);		
			$ch = curl_init(self::$endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1 );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			$result = curl_exec($ch);
			curl_close($ch);
			
monitisLog("POST requestPost **** action = <b>$action</b><p>$query</p><p>$result</p>");		
			//$json = json_decode($result, true);
			$json = self::jsonDecode($result);
			if( $json && isset( $json['errorCode']) && $json['errorCode'] == 4 ) {
			
				if(MonitisConf::update_token())
					return self::requestPost($action, $params);
				else
					return array('status'=>'error', 'msg'=>'Monitis server not response');
			}
			return $json;

		} else {
			if(MonitisConf::update_token())
				return self::requestPost($action, $params);
			else
				return array('status'=>'error', 'msg'=>'Monitis server not response');
		}
	}

	static function checkKeysValid($apiKey, $secretKey) {
		$params['version'] = MONITIS_API_VERSION;
		$params['action'] = 'secretkey';
		$params['apikey'] = $apiKey;
		$query = http_build_query($params);
		$ch = curl_init(self::$endpoint . '?' . $query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//$json = json_decode(curl_exec($ch), true);
		$result = curl_exec($ch);
		$json = self::jsonDecode($result);
		if (isset($json['secretkey']) && $json['secretkey'] == $secretKey)
			return true;
		return false;
	}

	static function getExternalMonitors() {
		return self::requestGet('tests', array());
	}
	
	static function getExternalMonitorsByTag($tag) {
		$params = array('tag' => $tag);
		return self::requestGet('tagtests', $params);
	}
	
	static function getExternalMonitorInfo($monitorID) {
		return self::requestGet('testinfo', array('testId' => $monitorID));
	}

	static function getExternalSnapshot( $testId='' ) {
		$params = array('nocache'=>'true');
		if( !empty($testId) )
			$params['testId'] = $testId;
		return self::requestGet('testsLastValues', $params );
	}

	// alternative getExternalSnapshot
	static function externalSnapshot( $monitorIds='' ) {
		$params = array('nocache'=>'true');
		if( !empty($monitorIds) )
			$params['monitorIds'] = $monitorIds;
		return self::requestGet('teststatuses', $params );
	}

	static function getExternalLocations() {
		return self::requestGet('locations', array());
	}

	static function createExternalPing( $params ) {
		return self::requestPost('addExternalMonitor', $params);
	}
	
	static function editExternalPing( & $params ) {
		return self::requestPost('editExternalMonitor', $params);
	}
	
	static function suspendExternal($ids) {
		$params = array();
		if( is_string( $ids ) && strstr($ids, ',') )
			$params['monitorIds'] = implode(',', $ids);
		else
			$params['monitorIds'] = $ids;
		return self::requestPost('suspendExternalMonitor', $params);
	}

	static function activateExternal($ids) {
		$params = array();
		if( is_string( $ids ) && strstr($ids, ',') )
			$params['monitorIds'] = implode(',', $ids);
		else
			$params['monitorIds'] = $ids;
		return self::requestPost('activateExternalMonitor', $params);
	}

	static function deleteExternal($ids) {
		$params = array();
		if( is_string( $ids ) && strstr($ids, ',') )
			$params['testIds'] = implode(',', $ids);
		else
			$params['testIds'] = $ids;
		return self::requestPost('deleteExternalMonitor', $params);
	}

	static function deleteInternal($ids, $mtype) {
		$params = array('type' => $mtype);
		if( is_string( $ids ) && strstr($ids, ',') )
			$params['testIds'] = implode(',', $ids);
		else
			$params['testIds'] = $ids;
		return self::requestPost('deleteInternalMonitors', $params);
	}


	////// INTERNAL MONTIORS
	static function getInternalMonitors() {
		return self::requestGet('internalMonitors', array());
	}

	static function getAgentInfo($agentId, $loadTests=false) {
		$params = array(
			'agentId' => $agentId
		);
		if($loadTests) {
			$params['loadTests'] = 'true';
		}
		return self::requestGet('agentInfo', $params);
	}

	///////////////////
	// CPU methods
	static function addCPUMonitor( $params ) {
		return self::requestPost('addCPUMonitor', $params);
	}
	static function editCPUMonitor( $params ) {
		return self::requestPost('editCPUMonitor', $params);
	}

	static function getCPUMonitor( $monitorId ) {
		$params = array('monitorId'=>$monitorId);
		return self::requestGet('CPUInfo', $params);
	}

	// memories methods
	static function addMemoryMonitor( $params ) {
		return self::requestPost('addMemoryMonitor', $params);
	}

	static function editMemoryMonitor( $params ) {
		return self::requestPost('editMemoryMonitor', $params);
	}

	static function getMemoryInfo( $monitorId ) {
		$params = array('monitorId'=>$monitorId);
		return self::requestGet('memoryInfo', $params);
	}

	// drivers methods
	static function addDriveMonitor( $params ) {
		return self::requestPost('addDriveMonitor', $params);
	}

	static function editDriveMonitor( $params ) {
		return self::requestPost('editDriveMonitor', $params);
	}

	static function getDriveInfo( $monitorId ) {
		$params = array('monitorId'=>$monitorId);
		return self::requestGet('driveInfo', $params);
	}
	//
	static function getExternalMonitorsByIds( $monitorIds='' ) {
		$params = array();
		if(!empty($monitorIds)) {
			$params['testId']=$monitorIds;
		}
		return self::requestGet('testsLastValues', $params);
	}

	//////////////////////////////////////////////////////////////
	static function getAgent($agentKey='') {
		$params = array();
		if(!empty($agentKey)) {
			$params['keyRegExp'] = '(?i)'.$agentKey;
		} 
		return self::requestGet('agents', $params);
	}
	
	static function getAgents() {			// 
		$params = array();
		return self::requestGet('agents', $params);
	}

	static function getAgentSnapshot( $agentKey='' ) {
		$params = array(
			'nocache'=>'true'
		);
		if( !empty($agentKey) )
			$params['agentKey'] = $agentKey;
		return self::requestGet('agentSnapshot', $params);
	}
	
	static function agentDrives( $agentId ) {
		$params = array(
			'agentId' => $agentId
		);
		return self::requestGet('agentDrives', $params);
	}
	
	static function allAgentsSnapshot($agentIds='', $stopped=false) {
		$params = array(
			'agentIds' => $agentIds,
			'nocache'=>'true',
			'timezone' => date('Z')/3600	// MonitisConf::$settings['timezone']
			);
		if($stopped) {
			$params['loadStopped'] = 'true';
		}
		return self::requestGet('allAgentsSnapshot', $params);
	}
	

	////ContactGroups//////////////////// 
        
	static function addContactGroup($active, $groupName) {               
		$params['active'] = $active;
		$params['groupName'] = $groupName;
		return self::requestPost('addContactGroup', $params);	               
	}
       
	static function getContactGroupList() {         
		return self::requestGet('contactGroupList', array());
	}       
	
	
        static function getContactsByGroupID($contactGroupId = '') { 
	        if($contactGroupId){
		    $params['contactGroupIds'] = $contactGroupId; 
		}
		$params[ 'nocache']='true';
		return self::requestGet('contactsList', $params);
	}
	
	static function editContactGroup($newName, $groupId) {     
		$params['newName'] = $newName;
		$params['groupId'] = $groupId;
		return self::requestPost('editContactGroup', $params);            
	}

	static function deleteContactGroup($groupId) {     
		$params['groupId'] = $groupId;
		return  self::requestPost('deleteContactGroup', $params);            
	}



 //////////////Assign contact to group////////////////

	static function getNotificationRules( $params ) {
		 return self::requestGet('getNotificationRules', $params);  
	}

	static function addNotificationRule($params) {
		 return self::requestPost('addNotificationRule', $params);  
	}

	static function deleteNotificationRule($params) {
		 return self::requestPost('deleteNotificationRule', $params);  
	}

		static function editNotificationRule($params) {
		 return self::requestPost('editNotificationRule', $params);  
	}        
	
	static function addContactToGroup($params) {
		return self::requestPost('addContact', $params);
	}

	static function deleteContact($contactId ){
		$params['contactId']=$contactId;		
		return self::requestPost('deleteContact', $params);
               
	}
	static function editContact($params ){
		return self::requestPost('editContact', $params); 
	}	
	
	// Sub Account
	static function clients($loadMonitors=false) {
		$params = array();
		if($loadMonitors) {
			$params['loadMonitors'] = 'true';
		}
		return self::requestGet('clients', $params);
	}
	
	static function getPublicKeys() {
		$params = array('nocache'=>'true');
		return self::requestGet('getPublicKeys', $params);
	}
	
	static function clientsList($params) {
		return self::requestGet('clients', $params);
	}
	
	static function userInfo($params) {
		return self::requestGet('userInfo', $params);
	}
}