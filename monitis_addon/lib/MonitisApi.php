<?php
class MonitisApi {
	static $endpoint = 'https://api.monitis.com/api';
	//static $endpoint = 'http://prelive.monitis.com/api';

	
	static function authToken() {
		//$resp = self::requestGet('authToken', array());
		$authToken = null;
		if( isset($_COOKIE) && isset($_COOKIE["monitis_authtoken"]) && $_COOKIE["monitis_authtoken"] != '' ) {
			$authToken = $_COOKIE["monitis_authtoken"];
//_logActivity("<b>from COOKIE *************** Get authToken</b><p>$authToken</p>");
		} else {
			$params = array();
			$params['version'] = '2';
			$params['action'] = 'authToken';
			$params['apikey'] = MonitisConf::$apiKey;
			$params['secretkey'] = MonitisConf::$secretKey;
			$query = http_build_query($params);
			$url = self::$endpoint . '?' . $query;
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$json = curl_exec($ch);
			$result = json_decode($json, true);

			if( !isset($result['error']) && isset($result['authToken']) ) {
				$authToken = $result['authToken'];
				//setcookie("monitis_authtoken", $authToken, time()+(3600*20) );
				setcookie("monitis_authtoken", $authToken, time()+3600 );
//_logActivity("<b>from COOKIE *************** Set authToken</b><p>$authToken</p>");
			} 
		}

		return $authToken;

	}
	
	static function prelive_getWidget( $params ) {
		$endpoint = 'http://prelive.monitis.com/api';

		$params['action'] = 'getWidget';
		$params['apikey'] = MonitisConf::$apiKey;
		
		$query = http_build_query($params);
		//$url = $endpoint . '?' . $query;
		$url = $endpoint . '?' . $query;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$json = curl_exec($ch);

		$result = json_decode($json, true);
		return $result;
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
		// TODO: error handling when JSON is not returned
		$params['version'] = '2';
		$params['action'] = $action;
		$params['apikey'] = MonitisConf::$apiKey;
		$query = http_build_query($params);
		$url = self::$endpoint . '?' . $query;
		
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
_logActivity("requestGet **** action = <b>$action</b><p>$url</p><p>$result</p>");
		$json = json_decode($result, true);
		return $json;
	}
	
	static function requestPost($action, $params) {
		// TODO: error handling when JSON is not returned
		
		$params['version'] = '2';
		$params['action'] = $action;
		$params['apikey'] = MonitisConf::$apiKey;

		
		$authToken = self::authToken();
		if( $authToken) {
			$params['validation'] = 'token';
			$params['authToken'] = $authToken;
		} else {
			$params['timestamp'] = date("Y-m-d H:i:s", time() );
			$params = self::hmacSign($params);
		}
		
		$query = http_build_query($params);

		$ch = curl_init(self::$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		$result = curl_exec($ch);
_logActivity("requestPost **** action = <b>$action</b><p>$query</p><p>$result</p>");		
		$json = json_decode($result, true);
		return $json;
	}
	
	static function hmacSign($params) {
		ksort($params);
		$joined = '';
		foreach ($params as $k => $v)
			$joined .= $k . $v;
		$checksum =  base64_encode(hash_hmac('sha1', $joined, MonitisConf::$secretKey, TRUE));
		$params['checksum'] = $checksum;
		return $params;
	}
	
	static function checkKeysValid($apiKey, $secretKey) {
		$params['version'] = '2';
		$params['action'] = 'secretkey';
		$params['apikey'] = $apiKey;
		$query = http_build_query($params);
		$ch = curl_init(self::$endpoint . '?' . $query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$json = json_decode(curl_exec($ch), true);
		if (isset($json['secretkey']) && $json['secretkey'] == $secretKey)
			return true;
		return false;
	}
	
	/*
	 * $type "http", "https", "ftp", "ping", "ssh", "dns", "mysql", "udp", "tcp", "sip", "smtp", "imap", "pop"
	 */
	static function addExternalMonitor($type, $name, $url, $interval, $locationIds, $tag) {
		$params = array(
				'type' => $type,
				'name' => $name,
				'url' => $url,
				'interval' => $interval,
				'locationIds' => $locationIds,
				'tag' => $tag
				);
		$resp = self::requestPost('addExternalMonitor', $params);
		if ($resp['status'] == 'ok')
			return $resp['data']['testId'];
		else
			return 0;
	}
	
	static function getExternalMonitors() {
		return self::requestGet('tests', array());
	}
	
	static function getExternalMonitorInfo($monitorID) {
		return self::requestGet('testinfo', array('testId' => $monitorID));
	}
	
	static function getExternalSnapshot( $testId='' ) {
		$params = array();
		if( !empty($testId) )
			$params['testId'] = $testId;
		return self::requestGet('testsLastValues', $params );
	}
	
	static function getExternalResults($monitorID, $day, $month, $year, $locationIDs = array(), $timezone = 0) {
		$params = array(
				'testId' => $monitorID,
				'day' => $day,
				'month' => $month,
				'year' => $year,
				'locationIds' => $locationIDs,
				'timezone' => $timezone
				);
		return self::requestGet('testresult', $params);
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
	
	
	static function createExternalHttp($name, $url, $interval, $timeout, $locationIDs, $tag,
			$uptimeSLA='', $responseSLA='', $detailedTestType='', $contentMatchFlag='', $postData='', $basicAuthUser='', $basicAuthPass='') {
		$params = array(
				'type' => 'http',
		);
		empty($name) || $params['name'] = $name;
		empty($url) || $params['url'] = $url;
		empty($interval) || $params['interval'] = $interval;
		empty($timeout) || $params['timeout'] = $timeout;
		empty($locationIDs) || $params['locationIds'] = implode(',', $locationIDs);
		empty($tag) || $params['tag'] = $tag;
	
		empty($uptimeSLA) || $params['uptimeSLA'] = $uptimeSLA;
		empty($responseSLA) || $params['responseSLA'] = $responseSLA;
		empty($detailedTestType) || $params['detailedTestType'] = $detailedTestType;
		empty($contentMatchFlag) || $params['contentMatchFlag'] = $detailedTestType;
		empty($postData) || $params['postData'] = $postData;
		empty($basicAuthUser) || $params['basicAuthUser'] = $basicAuthUser;
		empty($basicAuthPass) || $params['basicAuthPass'] = $basicAuthPass;
		//_dump($params);
		return self::requestPost('addExternalMonitor', $params);
	}
	static function editExternalHttp($testID, $name, $url, $interval, $timeout, $locationIDs, $tag,
			$uptimeSLA='', $responseSLA='', $detailedTestType='', $contentMatchFlag='', $postData='', $basicAuthUser='', $basicAuthPass='') {
		$params = array(
				'type' => 'http',
		);
		empty($testID) || $params['testId'] = $testID;
		empty($name) || $params['name'] = $name;
		empty($url) || $params['url'] = $url;
		//empty($interval) || $params['interval'] = $interval;
		empty($timeout) || $params['timeout'] = $timeout;
		$locationIDs = array_map(function($v) use($interval) { return $v . '-' . $interval; }, $locationIDs);
		empty($locationIDs) || $params['locationIds'] = implode(',', $locationIDs);
		empty($tag) || $params['tag'] = $tag;
	
		empty($uptimeSLA) || $params['uptimeSLA'] = $uptimeSLA;
		empty($responseSLA) || $params['responseSLA'] = $responseSLA;
		empty($detailedTestType) || $params['detailedTestType'] = $detailedTestType;
		empty($contentMatchFlag) || $params['contentMatchFlag'] = $detailedTestType;
		empty($postData) || $params['postData'] = $postData;
		empty($basicAuthUser) || $params['basicAuthUser'] = $basicAuthUser;
		empty($basicAuthPass) || $params['basicAuthPass'] = $basicAuthPass;
		//_dump($params);
		return self::requestPost('editExternalMonitor', $params);
	}
	static function createExternalHttps($name, $url, $interval, $timeout, $locationIDs, $tag,
			$uptimeSLA='', $responseSLA='', $detailedTestType='', $contentMatchFlag='', $postData='', $basicAuthUser='', $basicAuthPass='') {
		$params = array(
				'type' => 'https',
		);
		empty($name) || $params['name'] = $name;
		empty($url) || $params['url'] = $url;
		empty($interval) || $params['interval'] = $interval;
		empty($timeout) || $params['timeout'] = $timeout;
		empty($locationIDs) || $params['locationIds'] = implode(',', $locationIDs);
		empty($tag) || $params['tag'] = $tag;
	
		empty($uptimeSLA) || $params['uptimeSLA'] = $uptimeSLA;
		empty($responseSLA) || $params['responseSLA'] = $responseSLA;
		empty($detailedTestType) || $params['detailedTestType'] = $detailedTestType;
		empty($contentMatchFlag) || $params['contentMatchFlag'] = $detailedTestType;
		empty($postData) || $params['postData'] = $postData;
		empty($basicAuthUser) || $params['basicAuthUser'] = $basicAuthUser;
		empty($basicAuthPass) || $params['basicAuthPass'] = $basicAuthPass;
		//_dump($params);
		return self::requestPost('addExternalMonitor', $params);
	}
	static function editExternalHttps($testID, $name, $url, $interval, $timeout, $locationIDs, $tag,
			$uptimeSLA='', $responseSLA='', $detailedTestType='', $contentMatchFlag='', $postData='', $basicAuthUser='', $basicAuthPass='') {
		$params = array(
				'type' => 'https',
		);
		empty($testID) || $params['testId'] = $testID;
		empty($name) || $params['name'] = $name;
		empty($url) || $params['url'] = $url;
		//empty($interval) || $params['interval'] = $interval;
		empty($timeout) || $params['timeout'] = $timeout;
		$locationIDs = array_map(function($v) use($interval) { return $v . '-' . $interval; }, $locationIDs);
		empty($locationIDs) || $params['locationIds'] = implode(',', $locationIDs);
		empty($tag) || $params['tag'] = $tag;
	
		empty($uptimeSLA) || $params['uptimeSLA'] = $uptimeSLA;
		empty($responseSLA) || $params['responseSLA'] = $responseSLA;
		empty($detailedTestType) || $params['detailedTestType'] = $detailedTestType;
		empty($contentMatchFlag) || $params['contentMatchFlag'] = $detailedTestType;
		empty($postData) || $params['postData'] = $postData;
		empty($basicAuthUser) || $params['basicAuthUser'] = $basicAuthUser;
		empty($basicAuthPass) || $params['basicAuthPass'] = $basicAuthPass;
		//_dump($params);
		return self::requestPost('editExternalMonitor', $params);
	}
	
	static function suspendExternal($ids) {
		$params = array();
		$params['monitorIds'] = implode(',', $ids);
		self::requestPost('suspendExternalMonitor', $params);
	}
	static function activateExternal($ids) {
		$params = array();
		$params['monitorIds'] = implode(',', $ids);
		self::requestPost('activateExternalMonitor', $params);
	}
	static function deleteExternal($ids) {
		$params = array();
		$params['testIds'] = implode(',', $ids);
		self::requestPost('deleteExternalMonitor', $params);
	}
	
	
	////// INTERNAL MONTIORS
	static function getInternalMonitors() {
		return self::requestGet('internalMonitors', array());
	}
	static function getAgentsSnapshot($platform) {
		$params = array('platform' => $platform, 'nocache'=>'true');
		//$params = array('platform' => $platform );
		return self::requestGet('allAgentsSnapshot', $params);
	}

	static function getAgentInfo($agentId) {
		$params = array(
			'agentId'=>$agentId
		);
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
	static function getExternalMonitorsByIds( $monitorIds ) {
		$params = array('testId'=>$monitorIds);
		return self::requestGet('testsLastValues', $params);
	}
	//////////////////////////////////////////////////////////////
	static function getAgents() {			// ????????????
		$params = array();
		return self::requestGet('agents', $params);
	}
	
/*
	static function getAgent( $agentKey ) {
		$params = array(
			'keyRegExp'=>$agentKey
		);
		return self::requestGet('agents', $params);
	}
*/
	static function getAgent( $agentKey ) {

		//$params['version'] = '2';
		//$params['action'] = 'agents';
		//$params['apikey'] = MonitisConf::$apiKey;
		//$query = http_build_query($params);
		$url = self::$endpoint . '?keyRegExp=(?i)'.$agentKey.'&version=2&action=agents&apikey=' .MonitisConf::$apiKey;
		
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);

_logActivity("requestGet **** action = <b>agents</b><p>$url</p><p>$result</p>");
		$json = json_decode($result, true);
		return $json;
	}
	//static function getAgentSnapshot($agentKey, $types) {
	static function getAgentSnapshot( $agentKey='' ) {
		$params = array( 'nocache'=>'true' );
		//$params = array();
		if( !empty($agentKey) )
			$params['agentKey']=$agentKey;
		return self::requestGet('agentSnapshot', $params);
	}
	
	static function agentDrives( $agentId ) {
		$params = array(
			'agentId'=>$agentId
		);
		return self::requestGet('agentDrives', $params);
	}
	
	static function allAgentsSnapshot($agentIds) {
		$params = array('agentIds' => $agentIds);
		return self::requestGet('allAgentsSnapshot', $params);
	}

	////ContactGroups//////////////////// 
	static function addContactGroup($active, $groupName) {
               
		$params['active'] = $active;
		$params['groupName']=$groupName;
		$contactList=getContactGroupList();

		foreach($contactList as $group){
			foreach($group as $cell){
				if($cell['name']!=$groupName){
				  $resp = self::requestPost('addContactGroup', $params);  
				}
			}
		}
                
		if ($resp['status'] == 'ok')
			return true;
		else
			return false;
	}
       
	static function getContactGroupList() {         
           return self::requestGet('contactGroupList', array());
	}

	static function editContactGroup($oldName, $newName) {     
           $params['oldName']=$oldName;
           $params['newName']=$newName;
           $resp = self::requestPost('editContactGroup', $params);
            
		if ($resp['status'] == 'ok')
			return true;
		else
			return false;
	}
        
	static function deleteContactGroup($groupName) {     
           $params['groupName']=$groupName;
           self::requestPost('deleteContactGroup', $params);            
	}

 //////////////Assign contact to group////////////////
	static function addContactToGroup($firstName, $lastName, $account, $contactType=1, $timezone=0) {
             
		$params['firstName']=$firstName;
		$params['lastName'] =$lastName;
		$params['account']=$account;
		$params['contactType']=$contactType;
		$params['timezone']=$timezone;
		$params['group'] = MonitisConf::$defaultgroup;
		$resp = self::requestPost('addContact', $params);  
		if ($resp['status'] == 'ok')
			return true;
		else
			return false;
	}

	static function getContacts() { 
           return self::requestGet('contactsList', array());
	}

	static function deleteContact($contactId, $account, $contactType ){
		$params['contactId']=$contactId;
		$params['account']=$account;
		$params['contactType']= $contactType;
		$resp =  self::requestPost('deleteContact', $params);
			if ($resp['status'] == 'ok')
			return true;
		else
			return false;
	}

 
}