<?php
class serversListTab {

	private $synchronize = true;
	private $whmcsAllServers = null;
	
	private $whmcsExt = null;
	private $whmcsInt = null;
	private $total = 0;
	//private $extShots = null;
	private $allAgents = null;
	
	private $allPings = null;
	private $pingsStatus = null;
	
	public function __construct () { }

	private function fixMonitor($monitorId, & $whmcsInt) {
	
		for( $i=0; $i<count($whmcsInt); $i++) {
			if( $whmcsInt[$i]['monitor_id'] == $monitorId ) {
				$whmcsInt[$i]['ok'] = 1;
				return $whmcsInt[$i];
			}
		}
		return null;
	}
	
	private function unlinkedInternal() {
		$arr = array();
		for( $i=0; $i<count($this->whmcsInt); $i++) {
			if( !isset($this->whmcsInt[$i]['ok']) || $this->whmcsInt[$i]['ok'] != 1 ) {
				$arr[] = $this->whmcsInt[$i]['monitor_id'];
			}
		}
		if(count($arr) > 0) {
			$ids = implode(',', $arr);
			monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id in ('.$ids.')');
		}
	}
	
	private function diffTime($time) {
		$sec = strtotime(date('Y-m-d H:i')) - (strtotime($time) + date('Z'));
		return intval($sec/60);	// min
	}

	private function intMonitor( $agnt, $server_id) {
				
		$memory = null;
		$cpu = null;
		
		$adentId = $agnt['id'];
		
		if( isset($agnt['cpu']) ) {
			$status_associate = 'no';

			if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $agnt['cpu']['id'])) {

				$status = '';
				$monitorId = $agnt['cpu']['id'];
				if($this->synchronize)
					$value = monitisSnapShots::linkInternalMonitor( $monitorId, 'cpu', $adentId, $server_id);
				$this->whmcsInt[] = $value;
			} 
			//else {
			$status_associate = 'yes';
			if($this->synchronize)
				$wmon = $this->fixMonitor($agnt['cpu']['id'], $this->whmcsInt);
			
			$status = '';
			if(isset($agnt['cpu']['status'])) {
				$status = $agnt['cpu']['status'];
			}
			//}
			$diffTime = $this->diffTime($agnt['cpu']['time']);
			$cpu = array(
				'id'=>$agnt['cpu']['id'],
			//	'time' => $agnt['cpu']['time'],
				'diff' => $diffTime, 
				'status'=>$status,
				'associate'=> $status_associate
			);
		}

		if( isset($agnt['memory']) ) {
			$status_associate = 'no';
			if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $agnt['memory']['id'])) {
				
				$status = '';
				$monitorId = $agnt['memory']['id'];
				if($this->synchronize)
					$this->whmcsInt[] = monitisSnapShots::linkInternalMonitor( $monitorId, 'memory', $adentId, $server_id);

			} 
			//else {
			$status_associate = 'yes';
			if($this->synchronize)
				$wmon = $this->fixMonitor($agnt['memory']['id'], $this->whmcsInt);

			$status = '';
			if(isset($agnt['memory']['status'])) {
				$status = $agnt['memory']['status'];
			}
			
			$diffTime = $this->diffTime($agnt['memory']['time']);
			$memory = array(
				'id'=>$agnt['memory']['id'],
			//	'time' => $agnt['memory']['time'],
				'diff' => $diffTime, 
				'status'=>$status,
				'associate'=> $status_associate
			);
		}
		
		$info = array(
			'agent_id' => $agnt['id'],
			'status'=>$agnt['status'],
			'cpu' => $cpu,
			'memory' => $memory
		);

		if( isset($agnt['drives'] ) ) {

			$drives = $agnt['drives'];

			$info['drive'] = array();
			for( $j=0; $j<count($drives); $j++) {
				
				$nm = explode("@", $drives[$j]['name']);
				$name = substr($nm[0], strlen('drive_'));
				$status_associate = 'no';
				if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $drives[$j]['id'])) {
					$monitorId = $drives[$j]['id'];
					if($this->synchronize)
						$this->whmcsInt[] = monitisSnapShots::linkInternalMonitor( $monitorId, 'drive', $adentId, $server_id);
				} 
				//else {
				$status_associate = 'yes';
				if($this->synchronize)
					$wmon = $this->fixMonitor($drives[$j]['id'], $this->whmcsInt);
				$status = '';
				if(isset($drives[$j]['status'])) {
					$status = $drives[$j]['status'];
				}
					
				//}
				$diffTime = $this->diffTime($drives[$j]['time']);
				$info['drive'][] = array(
					'id'=>$drives[$j]['id'],
				//	'time' => $drives[$j]['time'],
					'diff' => $diffTime, 
					'status'=>$status,
					'name'=>$name,
					'associate'=> $status_associate
				);
			}
		}
		
		return $info;
	}
	

	private function unlinkedPings( ) {
		
		$extShots = $this->pingsStatus;
		for( $i=0; $i<count($this->whmcsExt); $i++) {
			
			$info = MonitisHelper::in_array($extShots, 'id', $this->whmcsExt[$i]['monitor_id']);
			if(!$info && @$extShots['status'] != 'error' && @$extShots['code'] != 101) {
				monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_EXTERNAL_TABLE.' WHERE monitor_id='.$this->whmcsExt[$i]['monitor_id']);
			}
		}
	}

	private function init_all_servers() {

		for( $i=0; $i<count($this->whmcsAllServers); $i++) {

			$monitors  = array();
			$server = $this->whmcsAllServers[$i];

			if($this->allPings) {
			
				// define ping monitor by IP
				$ping = MonitisHelper::in_array($this->allPings, 'url', $server['ipaddress']);
				if($ping) {
					$monitorId = $ping['id'];
					$ext = MonitisHelper::in_array($this->whmcsExt, 'monitor_id', $monitorId);
					if(!$ext && $this->synchronize) {
						// link monitor
						$ext = monitisSnapShots::linkPingMonitor( $monitorId, 'ping', $server['id']);
					}
					$status = MonitisHelper::in_array($this->pingsStatus, 'id', $monitorId);
					$monitors['ping'] = $status;
					if($ping['isSuspended'])
						$monitors['ping']['status'] = 'suspended';
				}
			}

			$agent = MonitisHelper::in_array($this->allAgents['agents'], 'key', $server['hostname']);
			
			if($agent) {
				if(isset($agent['status']) && $agent['status'] == 'running') {
					$info = $this->intMonitor( $agent, $server['id']);

					if( $info ) {
						$this->whmcsAllServers[$i]['agent_id'] = $info['agent_id']; 	//$int['agent_id'];
						$this->whmcsAllServers[$i]['agent_status'] = $info['status'];
						
						$monitors['cpu'] = $info['cpu'];
						$monitors['drive'] = $info['drive'];
						$monitors['memory'] = $info['memory'];
					}
				} elseif(isset($agent['status']) && $agent['status'] == 'stopped') {
						$this->whmcsAllServers[$i]['agent_id'] = $agent['id']; 	//$int['agent_id'];
						$this->whmcsAllServers[$i]['agent_status'] = $agent['status'];
				}
			}
			if($monitors && count($monitors) > 0)
				$this->whmcsAllServers[$i]['monitors'] = $monitors;
		}
		if($this->synchronize)
			$this->unlinkedInternal();
	}
	
	public function initServers() {
		
			$allSrvrsIds = MonitisHelper::idsByField($this->whmcsAllServers, 'id' );
	
			$srvrsIds = implode(',', $allSrvrsIds);
			// all linked ping monitors
			$this->whmcsExt = monitisWhmcsServer::extMonitorsByServerIds($srvrsIds);
			
			// get all ping monitors
			$this->allPings = MonitisApi::getExternalMonitors();
			// if Monitis server ok
			if(@$this->allPings['status'] != 'error' && @$this->allPings['code'] != 101) {
				$this->pingsStatus = null;
				if($this->allPings && isset($this->allPings['testList']) ) {
					$this->allPings = $this->allPings['testList'];
					// get all ping monitors status
					$this->pingsStatus = MonitisApi::externalSnapshot();
				} else {
					$this->allPings = null;
				}
				// remove unlinked ping monitors from whmcs
				if( $this->whmcsExt && $this->synchronize) {
					$this->unlinkedPings();
				} else {
					$this->whmcsExt = array();
				}
				
				// all linked internal monitors
				$this->whmcsInt = monitisWhmcsServer::intMonitorsByServerIds($srvrsIds);
				if(!$this->whmcsInt) {
					$this->whmcsInt = array();
				}

				// get agents
				$this->allAgents = MonitisApi::allAgentsSnapshot('', true);
				$this->init_all_servers();
			}
	}
	
	public function init($opts) {
		// all servers
		$this->synchronize = $opts['synchronize'];
		$this->whmcsAllServers = monitisWhmcsServer::allServers($opts);
		if( $this->whmcsAllServers ) {
			$this->total = $this->whmcsAllServers[0]['total'];
			$this->initServers();
		}
		return $this->whmcsAllServers;
	}
	
	public function getTotal() {
		return $this->total;
	}
}
?>