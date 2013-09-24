<?php
class MonitisApiHelper {

	static function getExternalLocationsGroupedByCountry() {
		$locations = MonitisApi::getExternalLocations();
		$americasIDs = array(1, 3, 5, 9, 10, 14, 15, 17, 26, 27);
		$europeIDs = array(2, 4, 7, 11, 12, 18, 19, 22, 23, 24, 25, 28, 29);
		$asiaIDs = array(8, 13, 16, 21);
		
		$loc = array('Americas' => array(), 'Europe' => array(), 'Asia' => array(), 'Other' => array());
		foreach ($locations as $l) {
			if (in_array($l['id'], $americasIDs))
				$loc['Americas'][$l['id']] = $l;
			elseif (in_array($l['id'], $europeIDs))
			$loc['Europe'][$l['id']] = $l;
			elseif (in_array($l['id'], $asiaIDs))
			$loc['Asia'][$l['id']] = $l;
			else
				$loc['Other'][$l['id']] = $l;
		}
		return $loc;
	}

	static function editPingMonitor( & $mParams ) {

		$resp = MonitisApi::editExternalPing( $mParams );

		//$resp = MonitisApi::createExternalPing( $mParams );
		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
		if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
			return true;
		}
		return false;
	}	
	
	//
	static function addCPU($agentInfo, $cpu ) {
		$platform = $agentInfo['platform'];
		$params = array(
			'agentkey'	=>	$agentInfo['agentKey'],
			'name'		=>	'cpu@'.$agentInfo['name'],
			'tag'		=> 	$agentInfo['name'].'_whmcs'
		);
		foreach($cpu[$platform] as $key=>$val){
			$params[$key] = $val;
		}
		
		$resp = MonitisApi::addCPUMonitor( $params );

		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
		//if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
		//	return $resp['data']['testId'];
		//}
		return $resp;
	}

	static function addCPUMonitor( $server, $client_id, $agentInfo, & $cpus, $cpuSets ) {

		$agentId = $agentInfo['agentId'];
		$cpus_monitorId = self::monitorIdByAgentId( $cpus, $agentId );
		$resp = null;
		if( $cpus_monitorId == 0 ) {
			$resp = self::addCPU($agentInfo, $cpuSets );
			if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
				$cpus_monitorId = $resp['data']['testId'];
			}
		} 
		if( $cpus_monitorId > 0 ) {
			$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'cpu','monitorId'=>$cpus_monitorId) );
			$values = array(
				"server_id" => $server['id'],
				"available" => MonitisConf::$settings['cpu']['available'],
				"monitor_id" => $cpus_monitorId,
				"agent_id" => $agentId,
				"monitor_type" => 'cpu',
				"client_id"=> $client_id,
				"publickey"=> $pubKey
			);
			insert_query('mod_monitis_int_monitors', $values);

			return array(
				'status'=>'ok',
				'msg' => 'CPU Monitor successfully created'
			);
		} else {
			return array(
				'status'=>'error',
				'msg' => $resp["error"]
			);
		}
	}
/*
	static function addServerAvailable($server_id ) {
		$whmcs = new WHMCS_class(MONITIS_CLIENT_ID);
		$whmcs->addServerAvailable( $server_id, MonitisConf::$serverAvailable );
	}
*/
	/////////////
	static function addMemory($agentInfo, $memory ) {

		$platform = $agentInfo['platform'];
		$params = array(
			'agentkey'	=>	$agentInfo['agentKey'],
			'name'		=>	'memory@'.$agentInfo['name'],
			'tag'		=> 	$agentInfo['name'].'_whmcs',
			'platform'	=>	$agentInfo['platform']
		);
		foreach($memory[$platform] as $key=>$val){
			$params[$key] = $val;
		}
		
		$resp = MonitisApi::addMemoryMonitor( $params );
		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
		//if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
		//	return $resp['data']['testId'];
		//}
		return $resp;
	}
	
	static function monitorIdByAgentId($arr, $agentId) {
		for($i=0; $i<count($arr); $i++) {
			if( $arr[$i]['agentId'] == $agentId ){
				$monitorId = $arr[$i]['id'];
				return $arr[$i]['id'];
			}
		}
		return 0;
	}
	
	static function addDefaultWeb( $product, $monitorsettings ) {
		// $monitorsettings = MonitisConf::$settings[$monitor_type];
		$monitor_type = $product['monitor_type'];
		$url = $product['web_site'];
		
		//$name = $product['ordernum'] . '_'.$monitor_type;
		$name = $url . '_'.$monitor_type;

		$interval = $monitorsettings["interval"];
		$timeout = $monitorsettings["timeout"];
		//$locationIDs = array_map( "intval", $monitorsettings["locationIds"] );
		$locationIDs = $monitorsettings["locationIds"];

		$tag = $product["tag"];
		if (empty($url))
			return false;
		if( $monitor_type == 'http')
			$resp = MonitisApi::createExternalHttp($name, $url, $interval, $timeout, $locationIDs, $tag);
		else
			$resp = MonitisApi::createExternalHttps($name, $url, $interval, $timeout, $locationIDs, $tag);
			

		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
			//$newID = $resp['data']['testId'];
			//return array('monitor_id'=> $resp['data']['testId'], 'tag' => $tag);
			//return $resp['data']['testId'];
			//return $resp['data'];
		//}
		return $resp;
	}
	////////////////////////// mod_monitis_servers 
	static function addWebPing($product, $pingsettings) {
		//$pingsettings = MonitisConf::$settings['ping'];
		$url = $product['web_site'];
		$name = $product['ordernum'];

		if ( empty($url) || empty($name) )
			return false;
		
		$locationIDs = array_map( "intval", $pingsettings["locationIds"] );
		
		$monParams = array(
			'type' => 'ping',
			'name' => $url . '_ping',
			'url' => $url,
			'interval' => $pingsettings["interval"],
			'timeout' => $pingsettings["timeout"],
			'locationIds' => implode(",", $locationIDs),
			'tag' => $product["tag"]
		);

		$resp = MonitisApi::createExternalPing( $monParams );

		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
			//return $resp['data']['testId'];
			//return $resp['data'];
		//}
		return $resp;
	}

	/*
	 * 
	 */
/*
	static function addPingMonitor($client_id, $server, $mParams ) {

		$resp = MonitisApi::createExternalPing( $mParams );

		//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
		if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
			$newID = $resp['data']['testId'];

			if( MonitisConf::$settings['ping']['autolink'] > 0 ) {
				$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'external','monitorId'=>$newID) );
				$values = array(
					"server_id" => $server['id'],
					"available" => MonitisConf::$settings['ping']['available'],
					"monitor_id" => $newID,
					"monitor_type" => "ping",
					"client_id"=> $client_id,
					"publickey"=> $pubKey 
				);
				@insert_query('mod_monitis_ext_monitors', $values);
				//self::addServerAvailable( $server['id'] );
			}
			return true;
		}
		return false;
	}
*/
	// ------------------ Default Internal monitors
	static function addDefaultAgents($client_id, $server, $internal ) {

		$hostname=$server['hostname'];
		$agents = MonitisApi::getAgent( $hostname );
		$agentKey = $agents[0]['key'];
		$platform = $agents[0]['platform'];
		$agentId = $agents[0]['id'];
		$result = array(
			'cpu' => array("status"=>'error',"msg"=>'No agent or agent is stopped'),
			'memory' => array("status"=>'error',"msg"=>'No agent or agent is stopped')
		);
		
		if( strtolower($agentKey) == strtolower($hostname) ) {
			$agentInfo = array(
				'agentKey' => $agentKey,
				'agentId' => $agentId,
				'name' => $hostname,
				'platform' => $platform 
			);			

			$intMonitors = MonitisApi::getInternalMonitors();
			$resp = null;
			// CPU
			if( MonitisConf::$settings['cpu']['autocreate'] > 0 ) {
				$cpus_monitorId = 0;
				if( isset( $intMonitors['cpus'] ) ) {
					$cpus_monitorId = self::monitorIdByAgentId( $intMonitors['cpus'], $agentId );
				}
				if( $cpus_monitorId == 0 ) {
					$resp = self::addCPU($agentInfo, MonitisConf::$settings['cpu'] );
					if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
						$cpus_monitorId = $resp['data']['testId'];
					}
				}
				
				if( $internal && $cpus_monitorId > 0 && self::isWhmcsMonitor( 'monitor_id', $cpus_monitorId, $internal ) ) {
				
					$result["cpu"]["status"] = 'warning';
					$result["cpu"]["msg"] = 'CPU monitor already exists';
					
				} elseif( $cpus_monitorId > 0 && MonitisConf::$settings['cpu']['autolink'] > 0 ) {
				
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'cpu','monitorId'=>$cpus_monitorId) );
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['cpu']['available'],
						"monitor_id" => $cpus_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'cpu',
						"client_id"=> $client_id,
						"publickey"=>$pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					$result["cpu"]["status"] = 'ok';
					$result["cpu"]["msg"] = 'CPU monitor created successfully';
				} else {
					if( $cpus_monitorId > 0 ) {
						$result["cpu"]["status"] = 'warning'; 
						$result["cpu"]["msg"] = 'no autolink';
					} else {
						$result["cpu"]["status"] = 'error';
						$result["cpu"]["msg"] = $resp['error'];
					}
				}
			} else {
				$result["cpu"]["status"] = 'warning';
				$result["cpu"]["msg"] = 'no autocreate';
			}
			
				// memory
				//if( in_array('memory', $monitorTypes ) ) { 
			if( MonitisConf::$settings['memory']['autocreate'] > 0 ) {

				$memory_monitorId = 0;
				if( isset( $intMonitors['memories'] ) ) {
					$memory_monitorId = self::monitorIdByAgentId( $intMonitors['memories'], $agentId );
				}
				//$monitors = MonitisApi::getAgentInfo( $agentId ); // monitors
				if( $memory_monitorId == 0 ) {
					$resp = self::addMemory($agentInfo, MonitisConf::$settings['memory'] );
					if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
						$memory_monitorId = $resp['data']['testId'];
					}
				}
				
				if( $internal && $memory_monitorId > 0 && self::isWhmcsMonitor( 'monitor_id', $memory_monitorId, $internal ) ) {
				
					$result["memory"]["status"] = 'warning';
					$result["memory"]["msg"] = 'Memory monitor already exists';
					
				} elseif( $memory_monitorId > 0 && MonitisConf::$settings['memory']['autolink'] > 0 ) {
				
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'memory','monitorId'=>$memory_monitorId) );
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['memory']['available'],
						"monitor_id" => $memory_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'memory',
						"client_id"=> $client_id,
						"publickey"=>$pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					$result["memory"]["status"] = 'ok';
					$result["memory"]["msg"] = 'Memory monitor created successfully';
				} else {
					if($memory_monitorId > 0) {
						$result["memory"]["status"] = 'warning'; 
						$result["memory"]["msg"] = 'no autolink';
					} else {
						$result["memory"]["status"] = 'error';
						$result["memory"]["msg"] = $resp['error'];
					}
				}
			} else {
				$result["memory"]["status"] = 'warning';
				$result["memory"]["msg"] = 'no autocreate';
			}
		}
		return $result;
//_logActivity("addAllDefault ******* <b>add Default CPU / Memory </b><p>".json_encode($result)."</p>");

	}
	
	static function isWhmcsMonitor( $fieldName, $fieldValue, & $whmcs ) {
	
		for( $i=0; $i<count($whmcs); $i++) {
			if( $whmcs[$i][$fieldName] == $fieldValue ) {
				return $whmcs[$i];
			}
		}
		return null;
	}
	// ------------------ Default Ping
	static function addDefaultPing($client_id, $server, $external) {
		
		$url = $server['ipaddress'];
		$name = $server['hostname'];
		
		$result = array(
			'status' => 'error',
			'msg' => 'Empty hostname or ip address '
		);
		
		if ( empty($url) || empty($name) )
			return $result;
			
		$locationIDs = array_map( "intval", MonitisConf::$settings['ping']['locationIds'] );
		
		$monParams = array(
			'type' => 'ping',
			'name' => $name . '_ping',
			'url' => $url,
			'interval' => MonitisConf::$settings['ping']['interval'],
			'timeout' => MonitisConf::$settings['ping']['timeout'],
			'locationIds' => implode(',', $locationIDs),
			'tag' => $name . '_whmcs'
		);

		//$res = self::addPingMonitor($client_id, $server, $monParams );
		$resp = MonitisApi::createExternalPing( $monParams );
		if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
			$newID = $resp['data']['testId'];
			
			//$mon = self::isWhmcsMonitor( 'monitor_id', $newID, $external );
			if( $external && self::isWhmcsMonitor( 'monitor_id', $newID, $external ) ) {
			
				$result["status"] = 'warning';
				$result["msg"] = 'Ping monitor already exists';			
			} else {
				if( MonitisConf::$settings['ping']['autolink'] > 0 ) {
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'external','monitorId'=>$newID) );
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['ping']['available'],
						"monitor_id" => $newID,
						"monitor_type" => "ping",
						"client_id"=> $client_id,
						"publickey"=> $pubKey 
					);
					@insert_query('mod_monitis_ext_monitors', $values);
					$result["status"] = 'ok';
					$result["msg"] = 'Ping monitor created successfully';
				} else {
					$result["status"] = 'warning';
					$result["msg"] = 'No autolink';
				}
			}
		} else {
			$result["status"] = 'error';
			$result["msg"] = $resp["error"];
		}

		return $result;
	}
	
	static function addAllDefault($client_id, $server, $whmcs=null ) {
			
		$response = array(
			"ping"=>array("status"=>'warning', "msg"=>'No autocreate and autolink'),
			"drive"=>array("status"=>'warning', "msg"=>'No autolink')
		);
		$external = null;
		$internal = null;
		if ( $whmcs ) {
			$external = ( isset($whmcs['ext']) && $whmcs['ext'] ) ? $whmcs['ext'] : null;
			$internal = ( isset($whmcs['int']) && $whmcs['int'] ) ? $whmcs['int'] : null;
		}
		
		if( MonitisConf::$settings['ping']['autocreate'] > 0 || MonitisConf::$settings['ping']['autolink'] > 0 ) {
			$response["ping"] = self::addDefaultPing($client_id, $server, $external);
		}
		
		$resp = self::addDefaultAgents($client_id, $server, $internal );
		$response["cpu"] = $resp["cpu"];
		$response["memory"] = $resp["memory"];
		
		if( MonitisConf::$settings['drive']['autolink'] > 0) {
			$oInt = new internalClass();
			$agentInfo = $oInt->getAgentInfo( $server['hostname'] );
			if( $agentInfo ) {
				$oWHMCS = new WHMCS_class( $client_id );
				$whmcs_drives = $oWHMCS->intMonitorsByType( $agentInfo['agentId'], 'drive' );
				$rep = $oInt->associateDrives( $whmcs_drives, $agentInfo, $server['id'] );
				$response["drive"] = $rep;
			} else {
				$response["drive"]["status"] = 'error';
				$response["drive"]["msg"] = 'Aggent error';
			}
		} 
		
_logActivity("addAllDefault ******* <b>add All Default Monitors</b><p>".json_encode($response)."</p>");
		return $response;
	}
	
	//
	static function deleteExternalMonitor( $monitor_id ) {	
		$resp = MonitisApi::deleteExternal( $monitor_id );
		self::unlinkExternalMonitor( $monitor_id );
		return $resp;
	}
	static function unlinkExternalMonitor( $monitor_id ) {
		$oWhmcs = new WHMCS_class();
		$oWhmcs->removeExternalMonitorsById( $monitor_id );
		return array('status'=>'ok');
	}
	
	//
	static function rulesByGroupid($groupid, & $list) {
		for($i=count($list); $i>0; $i--) {
			if($list[$i-1]['contactGroupId'])
				return $list[$i-1];
		}
		return null;
	}
        
    static function getNotificationRules($monitor_id, $monitor_type, $contactGroupId ) {
		 $params = array( 'monitorId'=> $monitor_id, 'monitorType'=>$monitor_type );
		 if( isset($contactGroupId) ) $params['contactGroupId'] = $contactGroupId;
		 
		$rules = MonitisApi::getNotificationRules( $params );
//_dump($rules);		
		$notif = null;
		if($rules) {
			$rule = self::rulesByGroupid($contactGroupId, $rules );
			if( $rule ) {
				$notif = json_decode( MONITIS_NOTIFICATION_RULE, true );
				$period = $rule["period"];
				$notif['period']['always']['value'] = 0;
				$notif['period']['specifiedTime']['value'] = 0;
				$notif['period']['specifiedDays']['value'] = 0;
				$notif['continuousAlerts'] = $rule["continuousAlerts"];
				$notif['notifyBackup'] = $rule["notifyBackup"];
				$notif['failureCount'] = $rule["failureCount"];
				
				if($period == 'always') {
					$notif['period']['always']['value'] = 1;
				} elseif($period == 'specifiedTime') {
					$notif['period']['specifiedTime']['value'] = 1;
					
					$params = $notif['period']['specifiedTime']['params'];
					$params['timeFrom'] = $rule["timeFrom"];
					$params['timeTo'] = $rule["timeTo"];
					
				} elseif($period == 'specifiedDays') {
					$notif['period']['specifiedDays']['value'] = 1;
					$params = $notif['period']['specifiedDays']['params'];
					$params['weekdayFrom']['day'] = $rule["weekdayFrom"];
					$params['weekdayFrom']['time'] = $rule["timeFrom"];
					$params['weekdayTo']['day'] = $rule["weekdayTo"];
					$params['weekdayTo']['time'] = $rule["timeTo"];
				}
			}
		}
//_dump($notif);
		return $notif;
	}
        
     
     static function getNotificationRules_new($monitor_id, $monitor_type, $contactGroupId ) {
		 $params = array( 'monitorId'=> $monitor_id, 'monitorType'=>$monitor_type );	
		 $rule = end(MonitisApi::getNotificationRules( $params ));                 
                
//_dump($rules);		
		$notif = null;
		if($rule) {
			
				$notif = json_decode( MONITIS_NOTIFICATION_RULE, true );
				$period = $rule["period"];
				$notif['period']['always']['value'] = 0;
				$notif['period']['specifiedTime']['value'] = 0;
				$notif['period']['specifiedDays']['value'] = 0;
				$notif['continuousAlerts'] = $rule["continuousAlerts"];
				$notif['notifyBackup'] = $rule["notifyBackup"];
				$notif['failureCount'] = $rule["failureCount"];
				
				if($period == 'always') {
					$notif['period']['always']['value'] = 1;
				} elseif($period == 'specifiedTime') {
					$notif['period']['specifiedTime']['value'] = 1;
					
					$params = $notif['period']['specifiedTime']['params'];
					$params['timeFrom'] = $rule["timeFrom"];
					$params['timeTo'] = $rule["timeTo"];
					
				} elseif($period == 'specifiedDays') {
					$notif['period']['specifiedDays']['value'] = 1;
					$params = $notif['period']['specifiedDays']['params'];
					$params['weekdayFrom']['day'] = $rule["weekdayFrom"];
					$params['weekdayFrom']['time'] = $rule["timeFrom"];
					$params['weekdayTo']['day'] = $rule["weekdayTo"];
					$params['weekdayTo']['time'] = $rule["timeTo"];
				}
			
		}
//_dump($notif);
		return $notif;
	}
        
	
	static function addNotificationRule( $monitor_id, $monitor_type, $alertGroupId, $alertRules ) {
		$result = array();
		if( $alertGroupId > 0 ) {
			$periodObj = $alertRules['period'];
                        
                     //_dump($periodObj);
			$info = array(
				'monitorId'=>$monitor_id,
				'monitorType'=>$monitor_type,
				'groupId'=>$alertGroupId
			);
			$period = '';
			foreach( $periodObj as $key=>$val) {
				if( $periodObj[$key]['value'] > 0) $period = $key; 
			}
			$info['period'] = $period;

			if( $period == 'specifiedTime') {
					$params = $periodObj[$period]["params"];
					$info["timeFrom"] = $params["timeFrom"];
					$info["timeTo"] = $params["timeTo"];
			} elseif(specifiedDays) {
					$params = $periodObj[$period]["params"];
					$info["weekdayFrom"] = $params["weekdayFrom"]["day"];
					$info["weekdayTo"] = $params["weekdayTo"]["day"];
		
					$info["timeFrom"] = $params["weekdayFrom"]["time"];
					$info["timeTo"] = $params["weekdayTo"]["time"];
			}
			$info["notifyBackup"] = $alertRules["notifyBackup"];
			$info["continuousAlerts"] = $alertRules["continuousAlerts"];
			$info["failureCount"] = $alertRules["failureCount"];
			
//_dump( $info );

			$resp = MonitisApi::addNotificationRule($info);
			if( $resp['status'] == 'ok') {
				$result['status'] = 'ok';
				$result['msg'] = 'A notification has been successfully set';
//_dump( $info );

			} else {
				$result['status'] = 'error';
				$result['msg'] = $resp['error'];
			}
//_dump( $resp );

		} else {
			$result['status'] = 'error';
			$result['msg'] = 'Notification is not set';
		}
		return $result; 
	}
	
	static function deleteNotificationRule( $monitor_id, $monitor_type, $group_id, $notification ) {
	
		$params = array( 
		//'contactIds'=> 
		'monitorId'=> $monitor_id, 
		'monitorType'=>$monitor_type 
		);
		
		return MonitisApi::deleteNotificationRule($params);
	}
	
    //////////////////////////////Contact Group///////////////////////   
        
        
	static function getGroupById($group_id){
		$allGroups=  MonitisApi::getContactGroupList();            
		for($i=0; $i<count($allGroups); $i++){
			if($allGroups[$i]["id"]==$group_id){                  
			   return $allGroups[$i];
			}               
		}
		return null;
	}
        
	static function getContactsEmailByGroup($groupIds){
		$allGroups =  MonitisApi::getContactsByGroupID($groupIds);
		$array=array();
		for($i=0; $i<count($allGroups); $i++){               
			foreach($allGroups[$i]['contacts']  as $contact){
				$array[] = $contact["account"]; 
			}
		}
		return $array;
	}
	
	static function groupNameByGroupId( $alertGroupId, & $groupList ) {
		for($i=0; $i<count($groupList); $i++) {
			if($groupList[$i]['id'] == $alertGroupId )
				return $groupList[$i]['name'];
		}
		return '';
	}

	static function alertGroupById( $alertGroupId, & $groupList ) {
		$max_len = 20;
		$grouptitle = $groupname = 'no alert';
		if($alertGroupId > 0 ) {
			if( !isset($groupList) || !$groupList ) {
				$groupList = MonitisApi::getContactGroupList();
			}
			$groupname = self::groupNameByGroupId( $alertGroupId, $groupList  );
			$grouptitle = (strlen($groupname) > $max_len ) ? substr($groupname, 0, $max_len) .'...' : $groupname;
		}
		return  array( 'id'=>$alertGroupId, 'name'=>$groupname, 'title'=>$grouptitle );
	}
}
        
        
