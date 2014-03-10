<?php
class serverMonitors {

	///////////////////////////////////////////
	//private $whmcs_all_servers = null;
	
	//private $serversList = null;
	private $whmcsExt = null;
	private $whmcsInt = null;
	private $allPings = null;
	private $pingsStatus = null;
	
	//private $allAgents = null;
	private $agentFullInfo = null;
	
	private $agentInfo = null;
	
	private function fixMonitor($monitorId) {
	
		for( $i=0; $i<count($this->whmcsInt); $i++) {
			if( $this->whmcsInt[$i]['monitor_id'] == $monitorId ) {
				$this->whmcsInt[$i]['ok'] = 1;
				return $this->whmcsInt[$i];
			}
		}
		return null;
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
	
	private function intMonitor( $agnt, $server_id) {
				

		$info =  array(
			'id'=>$agnt['id'],
			'status'=>$agnt['status'],
			'platform' => $agnt['platform'],
			'key'=>$agnt['key'],
			'cpu' => null,
			'memory' => null,
			'drive' => null
		);
		
		$adentId = $agnt['id'];
		
		if( isset($agnt['cpu']) && isset($agnt['cpu'][0]) ) {
			$cpu = $agnt['cpu'][0];
			
			$linkedMon = MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $cpu['id']);
			if(!$linkedMon) {
				$status = '';
				$monitorId = $cpu['id'];
				$linkedMon = monitisSnapShots::linkInternalMonitor( $monitorId, 'cpu', $adentId, $server_id);
				$this->whmcsInt[] = $linkedMon;
			} 
			//$wmon = $this->fixMonitor($cpu['id']);
			$info['cpu'] = $cpu;

			$info['cpu']['available'] = $linkedMon['available'];
			$info['cpu']['publickey'] = $linkedMon['publickey'];
		}
		
		if( isset($agnt['memory']) && isset($agnt['memory'][0]) ) {
			$memory = $agnt['memory'][0];
		
			$linkedMon = MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $memory['id']);
			if(!$linkedMon) {
				
				$status = '';
				$monitorId = $memory['id'];
				$linkedMon = monitisSnapShots::linkInternalMonitor( $monitorId, 'memory', $adentId, $server_id);
				$this->whmcsInt[] = $linkedMon;
			} 

			//$wmon = $this->fixMonitor($memory['id']);
			$info['memory'] = $memory;

			$info['memory']['available'] = $linkedMon['available'];
			$info['memory']['publickey'] = $linkedMon['publickey'];
		}
		

		if( isset($this->agentInfo['drives']) ) {

			$drvs = array();
			$allDrives = $this->agentInfo['drives'];
			for( $j=0; $j<count($allDrives); $j++) {
				$drive = $allDrives[$j];
				
				$monitor = MonitisHelper::in_array( $agnt['drives'], 'letter', $drive['letter']);

				if($monitor) {
					$linkedMon = MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $monitor['id']);
					if(!$linkedMon) {
						$linkedMon = monitisSnapShots::linkInternalMonitor( $monitor['id'], 'drive', $adentId, $server_id);
						$this->whmcsInt[] = $linkedMon;
					}
					$monitor['available'] = $linkedMon['available'];
					$monitor['publickey'] = $linkedMon['publickey'];	
					$drvs[] = $monitor;
				} else {
					$drvs[] = array('driveLetter'=>$drive['letter']);
				}
			}
			$info['drive'] = $drvs;
		}
		return $info;
	}
	
	
	private function initServer(& $server) {

		$monitor  = null;

		if($this->allPings) {
			// define ping monitor by IP
			$ping = MonitisHelper::in_array($this->allPings, 'url', $server['ipaddress']);
			if($ping) {

				$monitorId = $ping['id'];
				$linkedMon = MonitisHelper::in_array($this->whmcsExt, 'monitor_id', $monitorId);
				if(!$linkedMon) {
					// link ping monitor
					$linkedMon = monitisSnapShots::linkPingMonitor( $monitorId, 'ping', $server['id']);
				}
				$monitor = $ping;
				$monitor['available'] = $linkedMon['available'];
				$monitor['publickey'] = $linkedMon['publickey'];	
			}
		}
		
		$server['ping'] = $monitor;
		$agent = $this->agentFullInfo;

		if($agent) {
			$info = $this->intMonitor( $agent, $server['id']);
			$server['agent'] = $info;
		}
		return $server;
	}
	
	public function getServerInfo($server_id) {
		
		$server = monitisWhmcsServer::serverInfo($server_id);

		// all linked ping monitors
		$this->whmcsExt = monitisWhmcsServer::extMonitorsByServerIds($server['id']);

		// get all ping monitors
		$this->allPings = MonitisApi::getExternalMonitors();
		
		if(@$this->allPings['status'] != 'error' && @$this->allPings['code'] != 101) {
			$this->pingsStatus = null;
			if($this->allPings && isset($this->allPings['testList']) ) {
				$this->allPings = $this->allPings['testList'];
				// get all ping monitors status
				$this->pingsStatus = MonitisApi::externalSnapshot();
			} else {
				$this->allPings = null;
			}
			
			// all linked internal monitors
			$this->whmcsInt = monitisWhmcsServer::intMonitorsByServerIds($server['id']);
			if(!$this->whmcsInt) {
				$this->whmcsInt = array();
			}
			
			$agent = $this->getAgent($server['hostname']);
			if($agent && isset($agent['agentId'])) {
				$this->agentInfo = $agent;
				// get agent info
				$this->agentFullInfo = MonitisApi::getAgentInfo($agent['agentId'], true);
			}
			$server = $this->initServer($server);
		}
		return $server;
	}
	
	private function whmcsInfo(& $info, $monitorId, $type) {
		$table = MONITIS_INTERNAL_TABLE;
		if($type == 'external') {
			$table = MONITIS_EXTERNAL_TABLE;
		}
		
		$mon = monitisSqlHelper::objQuery('SELECT available, publickey FROM '.$table.' WHERE monitor_id='.$monitorId);
		$info['available'] = $mon['available'];
		$info['publickey'] = $mon['publickey'];
		return $info;
	}
	
	// get monitor info by id
	public function getMonitor($monitorId, $type) {
		$resp = null;
		if($monitorId && $monitorId > 0) {
		
			if($type == 'cpu') {
				$resp = MonitisApi::getCPUMonitor($monitorId);
				$resp = $this->whmcsInfo($resp, $monitorId, 'internal');
			} elseif($type == 'memory') {
				$resp = MonitisApi::getMemoryInfo($monitorId);
				$resp = $this->whmcsInfo($resp, $monitorId, 'internal');
				
			} elseif($type == 'drive') {
				$resp = MonitisApi::getDriveInfo($monitorId);
				$resp = $this->whmcsInfo($resp, $monitorId, 'internal');
				
			} elseif($type == 'ping' || $type == 'external') {
				$ping = MonitisApi::getExternalMonitorInfo($monitorId);
				 
				$locs = array();
				$intervals = array();
				for($i=0; $i<count($ping['locations']); $i++) {
					$locs[] = $ping['locations'][$i]['id'];
					$intervals[] = $ping['locations'][$i]['checkInterval'];
				}
				$ping['locations'] = implode(',', $locs);
				$ping['intervals'] = implode(',', $intervals);
				$ping['id'] = $monitorId;
				$resp = $this->whmcsInfo($ping, $monitorId, 'external');
			} 
		}
		return $resp;
	}
	
}


?>