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

	static function getExternalLocations() {
		$locations = self::getExternalLocationsGroupedByCountry();
		foreach ($locations as $key => $value) {
			if (empty($value))
				unset($locations[$key]);
		}
		return $locations;
	}

	static function getExternalLocationsGrouped() {
		return MonitisConf::$locations;
	}

	static function editPingMonitor(& $mParams) {

		$resp = MonitisApi::editExternalPing($mParams);
		if (isset($resp['data']) && isset($resp['data']['testId'])) {
			return true;
		}
		return false;
	}

	//
	static function addCPU($agentInfo, $cpu) {
		$platform = $agentInfo['platform'];
		$params = array(
			'agentkey' => $agentInfo['agentKey'],
			'name' => 'cpu@' . $agentInfo['name'],
			'tag' => $agentInfo['name'] . '_whmcs'
		);
		foreach ($cpu[$platform] as $key => $val) {
			$params[$key] = $val;
		}

		return MonitisApi::addCPUMonitor($params);
	}

	static function addCPUMonitor($server, $client_id, $agentInfo, & $cpus, $cpuSets) {

		$agentId = $agentInfo['agentId'];
		$cpus_monitorId = self::monitorIdByAgentId($cpus, $agentId);
		$resp = null;
		if ($cpus_monitorId == 0) {
			$resp = self::addCPU($agentInfo, $cpuSets);
			if (isset($resp['data']) && isset($resp['data']['testId'])) {
				$cpus_monitorId = $resp['data']['testId'];
			}
		}
		if ($cpus_monitorId > 0) {
			$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => 'cpu', 'monitorId' => $cpus_monitorId));
			$values = array(
				"server_id" => $server['id'],
				"available" => MonitisConf::$settings['cpu']['available'],
				"monitor_id" => $cpus_monitorId,
				"agent_id" => $agentId,
				"monitor_type" => 'cpu',
				"client_id" => $client_id,
				"publickey" => $pubKey
			);
			insert_query('mod_monitis_int_monitors', $values);

			return array(
				'status' => 'ok',
				'id' => $cpus_monitorId,
				'msg' => 'CPU Monitor successfully created'
			);
		} else {
			return array(
				'status' => 'error',
				'msg' => $resp["error"]
			);
		}
	}

	/////////////
	static function addMemory($agentInfo, $memory) {

		$platform = $agentInfo['platform'];
		$params = array(
			'agentkey' => $agentInfo['agentKey'],
			'name' => 'memory@' . $agentInfo['name'],
			'tag' => $agentInfo['name'] . '_whmcs',
			'platform' => $agentInfo['platform']
		);
		foreach ($memory[$platform] as $key => $val) {
			$params[$key] = $val;
		}
		return MonitisApi::addMemoryMonitor($params);
	}

	static function monitorIdByAgentId($arr, $agentId) {
		for ($i = 0; $i < count($arr); $i++) {
			if ($arr[$i]['agentId'] == $agentId) {
				$monitorId = $arr[$i]['id'];
				return $arr[$i]['id'];
			}
		}
		return 0;
	}

	// ------------------ Default Internal monitors

	static function addDefaultAgents($client_id, $server, $internal, & $agents) {

		$hostname = $server['hostname'];

		$agentKey = $agents[0]['key'];
		$platform = $agents[0]['platform'];
		$agentId = $agents[0]['id'];
		$result = array(
			'cpu' => array("status" => 'warning', "msg" => 'agentKey: '.$agentKey.', hostname: '.$hostname),
			'memory' => array("status" => 'warning', "msg" => 'agentKey: '.$agentKey.', hostname: '.$hostname)
		);

		//if (strtolower($agentKey) == strtolower($hostname)) {
			$agentInfo = array(
				'agentKey' => $agentKey,
				'agentId' => $agentId,
				'name' => $hostname,
				'platform' => $platform
			);

			$intMonitors = MonitisApi::getInternalMonitors();
			$resp = null;
			// CPU
			if (MonitisConf::$settings['cpu']['autocreate'] > 0) {
				$cpus_monitorId = 0;
				if (isset($intMonitors['cpus'])) {
					$cpus_monitorId = self::monitorIdByAgentId($intMonitors['cpus'], $agentId);
				}
				if ($cpus_monitorId == 0) {
					$resp = self::addCPU($agentInfo, MonitisConf::$settings['cpu']);
					if (isset($resp['data']) && isset($resp['data']['testId'])) {
						
						$cpus_monitorId = $resp['data']['testId'];
						$result["cpu"]["status"] = 'ok';
						$result["cpu"]["msg"] = 'CPU monitor created successfully';
					} else {
						$result['cpu']['error'] = 'error';
						$result['cpu']['msg'] = $resp['error'];
					}
				}

				if ($internal && $cpus_monitorId > 0 && self::isWhmcsMonitor('monitor_id', $cpus_monitorId, $internal)) {
				
					$result["cpu"]["status"] = 'warning';
					$result["cpu"]["msg"] = 'CPU monitor already exists';
				} elseif ($cpus_monitorId > 0) {
				
					$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => 'cpu', 'monitorId' => $cpus_monitorId));
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['cpu']['available'],
						"monitor_id" => $cpus_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'cpu',
						"client_id" => $client_id,
						"publickey" => $pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					$result["cpu"]["status"] = 'ok';
					$result["cpu"]["msg"] = 'CPU monitor created successfully';
				}
			} else {
				$result["cpu"]["status"] = 'warning';
				$result["cpu"]["msg"] = 'no autocreate';
			}

			// memory
			if (MonitisConf::$settings['memory']['autocreate'] > 0) {

				$memory_monitorId = 0;
				if (isset($intMonitors['memories'])) {
					$memory_monitorId = self::monitorIdByAgentId($intMonitors['memories'], $agentId);
				}
				if ($memory_monitorId == 0) {
					$resp = self::addMemory($agentInfo, MonitisConf::$settings['memory']);
					if (isset($resp['data']) && isset($resp['data']['testId'])) {
						$memory_monitorId = $resp['data']['testId'];
						$result["memory"]["msg"] = 'Memory monitor created successfully';
						$result["memory"]["status"] = 'ok';
					$result["memory"]["msg"] = 'Memory monitor created successfully';
					
					} else {
						$result['memory']['error'] = 'error';
						$result['memory']['msg'] = $resp['error'];					
					}
				}

				if ($internal && $memory_monitorId > 0 && self::isWhmcsMonitor('monitor_id', $memory_monitorId, $internal)) {

					$result["memory"]["status"] = 'warning';
					$result["memory"]["msg"] = 'Memory monitor already exists';
				} elseif ($memory_monitorId > 0) {

					$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => 'memory', 'monitorId' => $memory_monitorId));
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['memory']['available'],
						"monitor_id" => $memory_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'memory',
						"client_id" => $client_id,
						"publickey" => $pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					$result["memory"]["status"] = 'ok';
					$result["memory"]["msg"] = 'Memory monitor created successfully';
				}
			} else {
				$result["memory"]["status"] = 'warning';
				$result["memory"]["msg"] = 'no autocreate';
			}
		//}
		monitisLog($result, 'addAllDefault - add Default CPU / Memory');
		return $result;
	}

	static function isWhmcsMonitor($fieldName, $fieldValue, & $whmcs) {

		for ($i = 0; $i < count($whmcs); $i++) {
			if ($whmcs[$i][$fieldName] == $fieldValue) {
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

		if (empty($url) || empty($name))
			return $result;

		$locationIDs = array_map("intval", MonitisConf::$settings['ping']['locationIds']);

		$monParams = array(
			'type' => 'ping',
			'name' => $name . '_ping',
			'url' => $url,
			'interval' => MonitisConf::$settings['ping']['interval'],
			'timeout' => MonitisConf::$settings['ping']['timeout'],
			'locationIds' => implode(',', $locationIDs),
			'tag' => $name . '_whmcs'
		);

		$resp = MonitisApi::createExternalPing($monParams);
		if (isset($resp['data']) && isset($resp['data']['testId'])) {
			$newID = $resp['data']['testId'];

			if ($external && self::isWhmcsMonitor('monitor_id', $newID, $external)) {

				$result["status"] = 'warning';
				$result["msg"] = 'Ping monitor already exists';
			} else {
				$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => 'external', 'monitorId' => $newID));
				$values = array(
					"server_id" => $server['id'],
					"available" => MonitisConf::$settings['ping']['available'],
					"monitor_id" => $newID,
					"monitor_type" => "ping",
					"client_id" => $client_id,
					"publickey" => $pubKey
				);
				@insert_query('mod_monitis_ext_monitors', $values);
				$result["status"] = 'ok';
				$result["msg"] = 'Ping monitor created successfully';
			}
		} else {
			$result["status"] = 'error';
			$result["msg"] = $resp["error"];
		}
		return $result;
	}

	static function addAllDefault($client_id, $server, $whmcs = null) {

		$response = array(
			'ping' => array("status" => 'warning', "msg" => 'No autocreate'),
			'agent' => array('status' => 'warning', 'msg' => 'The server does not have agent.'),
			'internal_monitors' => array(
				'drive' => array("status" => 'warning', "msg" => 'does not exist')
			)
		);
		$external = null;
		$internal = null;
		if ($whmcs) {
			$external = ( isset($whmcs['ext']) && $whmcs['ext'] ) ? $whmcs['ext'] : null;
			$internal = ( isset($whmcs['int']) && $whmcs['int'] ) ? $whmcs['int'] : null;
		}

		if (MonitisConf::$settings['ping']['autocreate'] > 0) {  // || MonitisConf::$settings['ping']['autolink'] > 0
			$response['ping'] = self::addDefaultPing($client_id, $server, $external);
		}

		$agents = MonitisApi::getAgent($server['hostname']);

		if ($agents && count($agents) > 0) {
			//$agents = $agents[0];
			if(isset($agents[0]['status']) && $agents[0]['status'] == 'running') {
			
				$response['agent'] = array('status' => 'ok', 'msg' => '');
				$resp = self::addDefaultAgents($client_id, $server, $internal, $agents);
				$response['internal_monitors']['cpu'] = $resp["cpu"];
				$response['internal_monitors']['memory'] = $resp["memory"];

				$oInt = new internalClass();
				$agentInfo = $oInt->getAgentInfo($server['hostname']);

				if ($agentInfo) {
					$whmcs_drives = monitisWhmcsServer::intMonitorsByType($agentInfo['agentId'], 'drive');
					$rep = $oInt->associateDrives($whmcs_drives, $agentInfo, $server['id']);
					$response['internal_monitors']["drive"] = $rep;
				} else {
					$response['internal_monitors']["drive"]["status"] = 'error';
					$response['internal_monitors']["drive"]["msg"] = 'Agent error';
				}
			} else {
				$response['agent']['msg'] = 'Agent stopped';
			// link agent monitors
			}
		} 

		monitisLog($response, 'addAllDefault - add All Default Monitors');
		return $response;
	}

	//
	static function deleteExternalMonitor($monitor_id) {
		$resp = MonitisApi::deleteExternal($monitor_id);
		self::unlinkExternalMonitor($monitor_id);
		return $resp;
	}

	static function unlinkExternalMonitor($monitor_id) {
		monitisWhmcsServer::unlinkExternalMonitorById($monitor_id);
		return array('status' => 'ok');
	}

	// 
	static function rulesByGroupid($groupid, & $list) {
		for ($i = count($list); $i > 0; $i--) {
			if ($list[$i - 1]['contactGroupId'])
				return $list[$i - 1];
		}
		return null;
	}


	static function getNotificationRuleByType($monitor_type, $group_id=0){
	   $params=($group_id)? array('monitorType' => $monitor_type, 'contactGroupId' => $group_id):array('monitorType' => $monitor_type);
	   $rules = MonitisApi::getNotificationRules($params);
	   if(count($rules)> 0){
		$rule = end($rules);
		$notif = null;
		if ($rule) {
		     $notif = self::rulesToJson($rule);
		 }
		return $notif;
	   }else{
	        return null;
		}
	}
	



	static function addNotificationRule($contactId, $monitor_type, $alertGroupId, $alertRules) {
		$result = array();
		if ($alertGroupId > 0) {

			$info = array(
				'contactId' => $contactId,
				'monitorType' => $monitor_type,
				'contactGroupId' => $alertGroupId
			);
			$infoSet = self::rulesFromJson($info, $alertRules);

			$resp = MonitisApi::addNotificationRule($infoSet);
			if ($resp['status'] == 'ok') {
				$result['status'] = 'ok';
				$result['msg'] = 'A notification has been successfully set';
			} else {
				$result['status'] = 'error';
				$result['msg'] = $resp['error'];
			}
		} else {
			$result['status'] = 'error';
			$result['msg'] = 'Notification is not set';
		}
		return $result;
	}

	static function deleteNotificationRule($contact_id, $monitor_type) {

		$params = array(
			'contactIds' => $contact_id,
			'monitorType' => $monitor_type
		);
		return MonitisApi::deleteNotificationRule($params);
	}

	static function getGroupById($group_id) {
		$allGroups = MonitisApi::getContactGroupList();
		for ($i = 0; $i < count($allGroups); $i++) {
			if ($allGroups[$i]["id"] == $group_id) {
				return $allGroups[$i];
			}
		}
		return null;
	}

	static function getContactsEmails() {
		$allGroups = MonitisApi::getContactsByGroupID();
		$array = array();
		if ($allGroups) {
			for ($i = 0; $i < count($allGroups); $i++) {
				$array[$allGroups[$i]["contactId"]] = $allGroups[$i]["contactAccount"];
			}
			return $array;
		}
		return null;
	}

	static function getContactsEmailByGroup($groupId) {
		$allGroups = MonitisApi::getContactsByGroupID($groupId);
		$array = array();
		if ($allGroups) {
			for ($i = 0; $i < count($allGroups); $i++) {
				foreach ($allGroups[$i]['contacts'] as $contact) {
					$array[] = $contact["account"];
				}
			}
			return $array;
		}
		return null;
	}

	static function getContactsIdsByGroup($groupIds) {
		$allGroups = MonitisApi::getContactsByGroupID($groupIds);
		$array = array();
		if ($allGroups) {
			for ($i = 0; $i < count($allGroups); $i++) {
				if (count($allGroups[$i]['contacts']) != 0) {
					foreach ($allGroups[$i]['contacts'] as $contact) {
						$array[] = $contact["contactId"];
					}
				}
			}
			return $array;
		}
		return null;
	}

	static function groupNameByGroupId($alertGroupId, & $groupList) {
		for ($i = 0; $i < count($groupList); $i++) {
			if ($groupList[$i]['id'] == $alertGroupId)
				return $groupList[$i]['name'];
		}
		return '';
	}

	static function alertGroupById($alertGroupId, & $groupList) {
		$max_len = 20;
		$grouptitle = $groupname = 'no alert';
		if ($alertGroupId > 0) {
			if (!isset($groupList) || !$groupList) {
				$groupList = MonitisApi::getContactGroupList();
			}
			$groupname = self::groupNameByGroupId($alertGroupId, $groupList);
			$grouptitle = (strlen($groupname) > $max_len ) ? substr($groupname, 0, $max_len) . '...' : $groupname;
		}
		return array('id' => $alertGroupId, 'name' => $groupname, 'title' => $grouptitle);
	}

	//////
	static function rulesToJson(& $rule) {

		$notif = json_decode(MONITIS_NOTIFICATION_RULE, true);
		$period = $rule["period"];

		$notif['period']['always']['value'] = 0;
		$notif['period']['specifiedTime']['value'] = 0;
		$notif['period']['specifiedDays']['value'] = 0;
		$notif['continuousAlerts'] = $rule["continuousAlerts"];
		$notif['notifyBackup'] = $rule["notifyBackup"];
		$notif['failureCount'] = $rule["failureCount"];
		$notif['minFailedLocationCount'] = $rule["minFailedLocationCount"];

		if ($period == 'always') {
			$notif['period']['always']['value'] = 1;
		} elseif ($period == 'specifiedTime') {
			$notif['period']['specifiedTime']['value'] = 1;
			$notif['period']['specifiedTime']['params']['timeFrom'] = $rule["timeFrom"];
			$notif['period']['specifiedTime']['params']['timeTo'] = $rule["timeTo"];
		} elseif ($period == 'specifiedDays') {
			$notif['period']['specifiedDays']['value'] = 1;
			$notif['period']['specifiedDays']['params']['weekdayFrom']['day'] = $rule["weekdayFrom"];
			$notif['period']['specifiedDays']['params']['weekdayFrom']['time'] = $rule["timeFrom"];
			$notif['period']['specifiedDays']['params']['weekdayTo']['day'] = $rule["weekdayTo"];
			$notif['period']['specifiedDays']['params']['weekdayTo']['time'] = $rule["timeTo"];
		}
		return $notif;
	}

	static function rulesFromJson(& $info, & $rule) {
		$periodObj = $rule['period'];

		$period = '';
		foreach ($periodObj as $key => $val) {
			if ($periodObj[$key]['value'] > 0)
				$period = $key;
		}
		$info["period"] = $period;

		if ($period == 'specifiedTime') {
			$params = $periodObj[$period]["params"];
			$info["timeFrom"] = $params["timeFrom"];
			$info["timeTo"] = $params["timeTo"];
		} elseif ($period == 'specifiedDays') {
			$params = $periodObj[$period]["params"];
			$info["weekdayFrom"] = $params["weekdayFrom"]["day"];
			$info["weekdayTo"] = $params["weekdayTo"]["day"];

			$info["timeFrom"] = $params["weekdayFrom"]["time"];
			$info["timeTo"] = $params["weekdayTo"]["time"];
		}
		$info["notifyBackup"] = $rule["notifyBackup"];
		$info["continuousAlerts"] = $rule["continuousAlerts"];
		$info["failureCount"] = $rule["failureCount"];
		$info["minFailedLocationCount"] = $rule["minFailedLocationCount"];

		return $info;
	}
}

