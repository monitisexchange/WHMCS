<?php
$monitorID = monitisPostInt('monitor_id');

$isEdit = $monitorID > 0;
//$type = monitisPost('type');
$type = monitisPost('monitor_type');

$server = array(
	'id' => monitisPostInt('server_id'),
	'ipaddress' =>  monitisPost('server_ipaddress'),
	'hostname'=> monitisPost('server_hostname')
);
$client_id = MONITIS_CLIENT_ID;

switch ($type) {
	case 'ping' :
		$interval = monitisPostInt('interval');
		$locationIDs = isset($_POST['locationIDs']) ? $_POST['locationIDs'] : '';
		if( !empty( $locationIDs ) ) {
			$locationIDs = array_map( "intval", $locationIDs );
			$locationIDs = array_map(function($v) use($interval) { return $v . '-' . $interval; }, $locationIDs);
			$locationIDs = implode(',',$locationIDs );
		}
		$monParams = array(
			'type' => 'ping',
			'name' => monitisPost('name'),
			'url' => monitisPost('url'),
			'interval' => monitisPostInt('interval'),
			'timeout' => monitisPostInt('timeout'),
			'locationIds' => $locationIDs,			
			'tag' => monitisPost('tag')
		);
		if ($isEdit) {
			$monParams['testId'] = $monitorID;
			//$resp = MonitisApiHelper::editPingMonitor( $monParams );
                        
			$resp = MonitisApi::editExternalPing( $monParams );

			//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
			if (@$resp['status'] == 'ok' ) {
				MonitisApp::addMessage('Ping Monitor successfully updated');
			} else {
				MonitisApp::addError('Unable to edit monitor, API request failed: '.  $resp['error']);	
			}
		} else {
			//$resp = MonitisApiHelper::addPingMonitor($client_id, $server, $monParams );
			
			$resp = MonitisApi::createExternalPing( $monParams );

			//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
			if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
				$newID = $resp['data']['testId'];
				
				$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'external','monitorId'=>$newID) );
				//if( $pubKey ) {
				$values = array("server_id" => $server['id'], "available" => MonitisConf::$settings['ping']['available'], "monitor_id" => $newID, "monitor_type" => "ping", "client_id"=> $client_id, "publickey"=>$pubKey );
				insert_query('mod_monitis_ext_monitors', $values);
					
				MonitisApp::addMessage('Ping Monitor successfully created and associated with this server');
				//} 
			} else {
				MonitisApp::addError('Unable to create monitor, API request failed: '. $resp['error']);	
			}
		}
	break;
	
	case 'cpu' :
		if( $monitorID > 0 ) {
			$monitor = MonitisApi::getCPUMonitor( $monitorID );
			$platform = $monitor['agentPlatform'];
			$params = array(
				'testId' => $monitorID,
				'name' => $monitor['name'],
				'tag' => $monitor['tag']
			);	
			$cpu = MonitisConf::$settings['cpu'][$platform];
			foreach($cpu as $key=>$val){
				$params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $cpu[$key];
			}			
			$resp = MonitisApi::editCPUMonitor( $params );
			if( $resp && $resp['status'] == 'ok') 
				MonitisApp::addMessage('CPU Monitor successfully updated');
			else
				MonitisApp::addError('Unable to updated monitor, API request failed: '. $resp['error']);	
		} else {

			$hostname=$server['hostname'];
			$agents = MonitisApi::getAgents( $hostname );
			$agentKey = $agents[0]['key'];
			if( strtolower($agentKey) == strtolower($hostname) ) {//
				$platform = $agents[0]['platform'];
				
				$agentInfo = array(
					'agentKey' => $agents[0]['key'],
					'agentId' => $agents[0]['id'],
					'name' => $hostname,
					'platform' => $platform 
				);
				$intMonitors = MonitisApi::getInternalMonitors();
				
				$cpu = MonitisConf::$settings['cpu'][$platform];
				$cpuSets = array(
					'platform' => array( $platform => array() )
				);

				foreach($cpu as $key=>$val){
					$cpuSets['platform'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $cpu[$key];
				}
				
				$resp = MonitisApiHelper::addCPUMonitor( $server, $client_id, $agentInfo, $intMonitors['cpus'], $cpuSets['platform'] );
				if( $resp && $resp['status'] == 'ok') {
					MonitisApp::addMessage('CPU Monitor successfully created');
				} else {
					MonitisApp::addError('Create monitor, API request failed: '. $resp['error']);
				}

			} else {
				MonitisApp::addError('This server does not have an agent');	
			}
		}
	break;
	case 'memory' :
		
		if( $monitorID > 0 ) {
			$monitor = MonitisApi::getMemoryInfo( $monitorID );

			$platform = $monitor['agentPlatform'];
			$params = array(
				'testId' => $monitorID,
				'name' => $monitor['name'],
				'tag' => $monitor['tag'],
				'platform'=>$platform
			);	
			$memory = MonitisConf::$settings['memory'][$platform];
			foreach($memory as $key=>$val){
				$params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $memory[$key];
			}
			
			$resp = MonitisApi::editMemoryMonitor( $params );
			if( $resp && $resp['status'] == 'ok') {
				MonitisApp::addMessage('Memory Monitor successfully updated');
			} else {
				MonitisApp::addError('Unable to updated monitor, API request failed: '. $resp['error']);
			}
		} else {
			$hostname=$server['hostname'];
			$agents = MonitisApi::getAgent( $hostname );
			if( $agents ) {
				$agentKey = $agents[0]['key'];
				$platform = $agents[0]['platform'];
				$agentId = $agents[0]['id'];
				$params = array(
					'agentkey'	=>	$agentKey,
					'name'		=>	'memory@'.$hostname,
					'tag'		=> 	$hostname.'_whmcs',
					'platform'	=>	$platform
				);
		
				$memory = MonitisConf::$settings['memory'][$platform];
				foreach($memory as $key=>$val){
					$params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $memory[$key];
				}
				
				$resp = MonitisApi::addMemoryMonitor( $params );
				//if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists' ) {
				if ( isset( $resp['data'] ) && isset($resp['data']['testId']) ) {
					$memory_monitorId = $resp['data']['testId'];
					
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'memory','monitorId'=>$memory_monitorId) );
					
					$values = array(
						"server_id" => $server['id'],
						"available" => MonitisConf::$settings['memory']['available'],
						"monitor_id" => $memory_monitorId,
						"agent_id" => $agentId,
						"monitor_type" => 'memory',
						"client_id"=> $client_id,
						"publickey"=> $pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					
					MonitisApp::addMessage('Memory Monitor successfully created');
				} else {
					MonitisApp::addError('Create memory monitor error: ');
				}

			} else {
				MonitisApp::addError('This server does not have an agent');	
			}
		}
	break;
	case 'drive' :
		$action_type = $_POST['action_type'];
		if( $monitorID > 0 ) {
			
			$params = array(
				'testId' => $monitorID,
				'freeLimit' => $_POST['freeLimit'],
				'name' => $_POST['name'],
				'tag' => $_POST['tag']
			);
			$resp = MonitisApi::editDriveMonitor( $params );
			if( $resp ){
				if($resp['status'] == 'ok' ) {
					if( $action_type == 'associate' ) {
					
						$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'drive','monitorId'=>$monitorID) );
						$values = array(
							'server_id' => $server['id'],
							"available" => MonitisConf::$settings['drive']['available'],
							'agent_id' => $_POST['agentId'],
							'monitor_id' => $monitorID,
							'monitor_type' => 'drive',
							'client_id'=> $client_id,
							"publickey"=> $pubKey
						);
						insert_query('mod_monitis_int_monitors', $values);
					}
					MonitisApp::addMessage('Drive Monitor successfully updated');
				} else {
					MonitisApp::addError('Unable to updated monitor, API request failed: '. $resp['error']);
				}
			}
		} elseif( $monitorID == 0 ) {
				
				$params = array(
					'agentkey' => $_POST['agentKey'],
					'driveLetter' => monitisPost('module_CreateMonitorServer_driveLetter'),
					'freeLimit' => $_POST['freeLimit'],
					'name' => $_POST['name'],
					'tag' => $_POST['tag']
				);
				$resp = MonitisApi::addDriveMonitor( $params );
				if( $resp ) {
					if($resp['status'] == 'ok') {
						$newID = $resp['data']['testId'];
						
						$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'drive','monitorId'=>$newID) );
						$values = array(
							'server_id' => $server['id'],
							"available" => MonitisConf::$settings['drive']['available'],
							'agent_id' => $_POST['agentId'],
							'monitor_id' => $newID,
							'monitor_type' => 'drive',
							'client_id'=> $client_id,
							'publickey' => $pubKey
						);
						insert_query('mod_monitis_int_monitors', $values);
						
						MonitisApp::addMessage('Drive Monitor successfully added');
					} else {
						MonitisApp::addError('Unable to updated monitor, API request failed: '. $resp['error']);		
					}
				}
		}
	
	break;
	case 'delete':
		if( $monitorID > 0) {
			$monitorType = monitisPost('monitorType');
			
			$oWhmcs = new WHMCS_class();
			// monitors, 5 for internal ping monitors, 6 for load average monitors, 7 for CPU monitors )
			$mtype = '';
			switch( $monitorType) {
				case 'ping':
					$resp = MonitisApi::deleteExternal($monitorID);
					if($resp['status'] == 'ok') {
						MonitisApp::addMessage('External Monitor successfully removed');
					} else {
						MonitisApp::addError('Delete monitor, API request failed: '. $resp['error']);
					}
					// delete from tables 
					$oWhmcs->removeExternalMonitorsById($monitorID);
				break;
				case 'cpu':
				case 'memory':
				case 'drive':
					$mtype = 2;
					if( $monitorType == 'memory') $mtype = 3;
					if( $monitorType == 'cpu') $mtype = 7;
					$resp = MonitisApi::deleteInternal($monitorID, $mtype);
					if($resp['status'] == 'ok') {
						MonitisApp::addMessage('Internal Monitor successfully removed');
					} else {
						MonitisApp::addError('Delete monitor, API request failed: '. $resp['error']);
					}
					// delete from tables 
					$oWhmcs->removeInternalMonitorsById($monitorID);
				break;
			}

			//echo "delete ********************************* $monitorType $monitorID <br>";
		}
	break;
}
self::render('default');