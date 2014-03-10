<?php


class monitisSnapShots {

	static function externalSnapShots(& $whmcsExt, $server) {

		$ext = null;
		$allPings = MonitisApi::getExternalMonitors();

		if(isset($allPings['testList'])) {
			$allPings = $allPings['testList'];
			$ping = MonitisHelper::in_array($allPings, 'url', $server['ipaddress']);

			if($ping) {
				$monitorId = $ping['id'];
				$ext = MonitisHelper::in_array($whmcsExt, 'monitor_id', $monitorId);
				if(!$ext) {
					// link monitor
					$ext = self::linkPingMonitor( $monitorId, 'ping', $server['id']);
				}
				$ext['isSuspended'] = $ping['isSuspended'];
			} elseif($whmcsExt && count($whmcsExt) > 0 && @$allPings['status'] != 'error' && @$allPings['code'] != 101) {
				for( $i=0; $i<count($whmcsExt); $i++) {
					monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_EXTERNAL_TABLE.' WHERE monitor_id='.$whmcsExt[$i]['monitor_id']);
				}
			}
		}
		return $ext;
	}
	
	static function linkPingMonitor( $monitorId, $monitorType, $server_id) {
		$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => 'external', 'monitorId' => $monitorId));
		$value = array(
			'client_id' => 1, 
			'server_id' => $server_id, 
			'monitor_id' => $monitorId,
			'monitor_type' => $monitorType,
			'available' => MonitisConf::$settings[$monitorType]['available'],
			'publickey' => $pubKey
		);
		insert_query('mod_monitis_ext_monitors', $value);
		return $value;
	}
	
	static function linkInternalMonitor( $monitorId, $monitorType, $adentId, $server_id) {
		$pubKey = MonitisApi::monitorPublicKey(array('moduleType' => $monitorType, 'monitorId' => $monitorId));
		$value = array(
			'client_id' => 1, 
			'server_id' => $server_id, 
			'agent_id' => $adentId, 
			'monitor_id' => $monitorId,
			'monitor_type' => $monitorType,
			'available' => MonitisConf::$settings[$monitorType]['available'],
			'publickey' => $pubKey
		);
		$resp = insert_query('mod_monitis_int_monitors', $value);
		return $value;
	}	
}



class internalClass {

	private $agentInfo = null;
	private $fullAgentInfo = null;
	
	private $drives = null;
	
	public function __construct() {}
	
	
	public function getAgentMonitorsById( $agentId ) {
	
		$this->agentInfo = null;
		$agents = MonitisApi::allAgentsSnapshot( $agentId );

		if( $agents ) {
			$agent = $agents['agents'][0];
			
			$this->agentInfo = array(
				'agentKey' => $agent['key'],
				'agentId' => $agent['id'],
				'name' => $agent['key'],
				'tag' => $agent['key'].'_whmcs',
				'platform' => $agent['platform']
			);
			if( isset( $agent['cpu'] ) )
				$this->agentInfo['cpu'] = $agent['cpu'];
			if( isset( $agent['memory'] ) )
				$this->agentInfo['memory'] = $agent['memory'];
			if( isset( $agent['drives'] ) ) {
				$this->agentInfo['drives'] = $agent['drives'];
			}
		}
		return $this->agentInfo;
	}

	public function getAgent( $keyRegExp  ) {

		$agentInfo = null;
		$agentResp = MonitisApi::getAgent( $keyRegExp );

		if( $agentResp ) {
			$agent = $agentResp[0];
						
			$agentInfo = array(
				'agentKey' => $agent['key'],
				'agentId' => $agent['id'],
				'name' => $keyRegExp,
				'tag' => $keyRegExp.'_whmcs',
				'status' => $agent['status'],
				'platform' => $agent['platform']
			);

			$drives = $agent['drives'];
			if( $drives ) {
				$list = array();
				$drv = array();
				for( $i=0; $i<count($drives); $i++){
					$list[] = $drives[$i];		// $drives[$i][1]
					$drv[] = array( 'letter'=>$drives[$i]);		// $drives[$i][1]
				}
				$agentInfo['drivesList'] = implode(',', $list );
				$agentInfo['drives'] = $drv;
			}
		}
		return $agentInfo;
	}

	private function isMonitor( $letter, & $monitors ) {

		for( $i=0; $i<count($monitors); $i++) {
			$name = $monitors[$i]['name'];
			$nm = explode('@', $name );

			$lttr = substr($nm[0], strlen('drive_'));
			if( $lttr == $letter ) {
				$tag = $nm[1].'_whmcs';
				$monitors[$i]['tag'] = $tag;
				return $monitors[$i];
			}
		}
		return null;
	}

	public function getAgentInfo( $keyRegExp ) {

		$fullAgentInfo = $this->getAgent( $keyRegExp);

		if( $fullAgentInfo ) {
			$intMonitors = $this->getAgentMonitorsById( $fullAgentInfo['agentId'] );

			if( isset( $intMonitors['cpu'] ) )
				$fullAgentInfo['cpu'] = $intMonitors['cpu'];
			if( isset( $intMonitors['memory'] ) )
				$fullAgentInfo['memory'] = $intMonitors['memory'];
			if( isset( $fullAgentInfo['drives'] ) && isset( $intMonitors['drives'] ) ) {
				$drivesMonitors = $intMonitors['drives'];
				$drvs = $fullAgentInfo['drives'];
				$fullAgentInfo['driveMonitors'] = array();

				for( $i=0; $i<count($drvs); $i++) {

					$drvMon = $this->isMonitor( $drvs[$i]['letter'], $drivesMonitors );
					if($drvMon) {
						$fullAgentInfo['driveMonitors'][] = $drvMon['id'];
						foreach( $drvMon as $key=>$val) {
							$fullAgentInfo['drives'][$i][$key] = $drvMon[$key];
						}
					}
				}
			}
		}

		return $fullAgentInfo;	
	}
	////////////////////////

	public function filterInternalMonitors( $api, $int, $server_id) {
		
		$adentId = $api['agentId'];
		
		$monitors = array();
		$drivers = null;
		$intMonitors = array('cpu'=>null, 'memory'=>null, 'drives'=>null);

		if( isset($api['cpu']) && count($api['cpu']) > 0 && isset($api['cpu']['id'])) {
			$intMonitors['cpu'] = $api['cpu'];
			$monitorId = $api['cpu']['id'];
			$index = MonitisHelper::in_array_index( $int, 'monitor_id', $monitorId);
			if($index > -1) {
				$monitors[] = $int[$index];
				$int[$index]['api'] = 1;
			} else {
				$monitors[] = monitisSnapShots::linkInternalMonitor( $monitorId, 'cpu', $adentId, $server_id);
			}
		}
		if( isset($api['memory']) && count($api['memory']) > 0 && isset($api['memory']['id'])) {
			$monitorId = $api['memory']['id'];
			$index = MonitisHelper::in_array_index( $int, 'monitor_id', $monitorId);
			if($index > -1) {
				$monitors[] = $int[$index];
				$int[$index]['api'] = 1;
			} else {
				$monitors[] = monitisSnapShots::linkInternalMonitor( $monitorId, 'memory', $adentId, $server_id);
			}
		}
		$drvrs = array();
		if( isset($api['drives']) && count($api['drives']) > 0) {
			$drivers = $api['drives'];
			//$intMonitors['drives'] = array();
			for($i=0; $i<count($drivers); $i++) {
				// monitor exist
				if( isset($drivers[$i]['id']) ) {
					$monitorId = $drivers[$i]['id'];
					// is linked
					$index = MonitisHelper::in_array_index( $int, 'monitor_id', $monitorId);
					if($index > -1) {
						$monitors[] = $int[$index];
						$int[$index]['api'] = 1;
					} else {
						$monitors[] = monitisSnapShots::linkInternalMonitor( $monitorId, 'drive', $adentId, $server_id);
					}
				} else {
					$monitors[] = null;
				}
			}
		}
		// remove api deleted monitors
		for($i=0; $i<count($int); $i++) {
			if(!isset($int[$i]['api'])) {
				monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id='.$int[$i]['monitor_id']);
			}
		}
		
		return $monitors;
	}
	

	/////////////////////////////
	public function associateDrives( $whmcsDrives, $agentInfo, $serverID ) {
	
		$result = array("status"=>"warning","msg"=>"No agent or agent is stopped");
		if( $agentInfo && isset($agentInfo['drives']) ) {
			$agentId = $agentInfo['agentId'];
			$monDrives = $agentInfo['drives'];

			$whmcs_drives = null;
			if($whmcsDrives) {
				$whmcs_drives = array();
				for($i=0; $i<count($whmcsDrives); $i++) {
					$mon = MonitisHelper::in_array($monDrives, 'id', $whmcsDrives[$i]['monitor_id']);
					if($mon) {
						$whmcs_drives[] = $whmcsDrives[$i];
					} else {
						monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id='.$whmcsDrives[$i]['monitor_id'] );
					}
				}
			}
			
			$ids = array();
			for($i=0; $i<count($monDrives); $i++) {
				$drive = $monDrives[$i];
				if( isset($drive['id']) ) {
					if( !$whmcs_drives || !MonitisHelper::in_array($whmcs_drives, 'monitor_id', $drive['id']) ) {
						$ids[] = $drive['id'];
					} 
				}
			}
			$cnt = count($ids);
			if($ids && $cnt > 0 ) {
			
				for($i=0; $i<$cnt; $i++) {
					$monitorID = $ids[$i];
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'drive','monitorId'=>$monitorID) );

					$values = array(
						'server_id' => $serverID,
						'available' => MonitisConf::$settings['drive']['available'],
						'agent_id' => $agentId,
						'monitor_id' => $monitorID,
						'monitor_type' => 'drive',
						'client_id'=> MONITIS_CLIENT_ID,
						"publickey"=> $pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
					
				}
				$result["status"] = 'ok';
				$result["msg"] = 'Add '.$cnt.' drive(s)';
			} else {
				$result["status"] = 'warning'; 
				$result["msg"] = 'No drive monitor for add';
			}
		}
		return $result;
	}
	
	public function driveById(& $drives, $monitorId) {
		for($i=0; $i<count($drives); $i++) {
			if($drives[$i]['id'] == $monitorId) {
				return  $i;
			}
		}
		return null;
	}
}
?>