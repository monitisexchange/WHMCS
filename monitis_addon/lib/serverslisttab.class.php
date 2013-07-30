<?
class serversListTab {
	private $whmcs_all_servers = null;
	
	private $whmcs_ext = null;
	private $whmcs_int = null;
	private $total = 0;
	//private $serversIds = null;
	private $extShots = null;
	private $allAgents = null;
	
	public function __construct () { }

	// internal
	private function getAgents() {
		$int = $this->whmcs_int;

		$agentIds = array();
		for($i=0; $i < count($int); $i++) {
			$agentIds[] = $int[$i]['agent_id']; 
		}
		return array_unique($agentIds);

	}
	private function __monitor( $server_id, & $whmcs ) {
	
		for( $i=0; $i<count($whmcs); $i++) {
			if( $server_id == $whmcs[$i]['server_id'] ) {
				return $whmcs[$i];
			}
		}
		return null;
	}
	private function _isWhmcsMonitor( $fieldName, $fieldValue, & $whmcs ) {
	
		for( $i=0; $i<count($whmcs); $i++) {
			if( $whmcs[$i][$fieldName] == $fieldValue ) {
				return $whmcs[$i];
			}
		}
		return null;
	}
	private function getInternalMonitors( $agent_id) {
		
		$agents = $this->allAgents['agents'];
		$info = null;

		for( $i=0; $i<count($agents); $i++) {
			if($agents[$i]['id'] == $agent_id ) {

				$agnt = $agents[$i];
				$memory = null;
				if( isset($agnt['memory']) ) {
					$memory = array( 'id'=>$agnt['memory']['id'], 'status'=>$agnt['memory']['status'] );
				}
				
				$info = array(
					'status'=>$agnt['status'],
					'cpu' => array( 'id'=>$agnt['cpu']['id'], 'status'=>$agnt['cpu']['status'] ),
					'memory' => $memory
				);
				if( isset($agnt['drives'] ) ) {
					$drives = $agnt['drives'];
					$info['drive'] = array();
					for( $j=0; $j<count($drives); $j++) {
						
						$nm = explode("@", $drives[$j]['name']);
						$name = substr($nm[0], strlen('drive_'));
						$status_associate = 'no';
						if( $this->_isWhmcsMonitor( 'monitor_id', $drives[$j]['id'], $this->whmcs_int ) ) {
							$status_associate = 'yes';
						}
						$info['drive'][] = array('id'=>$drives[$j]['id'], 'status'=>$drives[$j]['status'], 'name'=>$name, 'associate'=> $status_associate );
					}
				}

			}
		}
		return $info;

	}	

	///////////////////////////////////////////////////////////////////////////////////////////////////////
	public function _idsList( & $list, $fieldName ){
		$ids = array();
		if( count($list) > 0 ) {
			$cnt = count($list);
			for($i=0; $i<$cnt;$i++) {
				if( empty($fieldName) )
					$ids[] = $list[$i];
				else
					$ids[] = $list[$i][$fieldName];
			}
			$ids = array_unique($ids); 
		}
		return $ids;
	}
	public function isMonitor($server_id, & $mons) {
		for($i=0; $i<count($mons); $i++) {
			if( $mons[$i]['server_id'] == $server_id) {
				return $mons[$i];
			}
		}
		return false;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////
	private function checkMonitor ( $monitor_id,  & $newList, & $data) {

		for( $i=0; $i<count($newList); $i++) {
			if( $newList[$i]['monitor_id'] == $monitor_id ) {
				$newList[$i]['isSuspended'] = $data['isSuspended'];
				if( $data['status'] == 'OK' )	$newList[$i]['status_ok']++;
				return $newList[$i];
			}
		}
		$status = 0;
		if( $data['status'] == 'OK' ) $status = 1;
		$newList[] = array( 'monitor_id'=>$data['id'], 'status_ok'=>$status, 'isSuspended'=>$data['isSuspended'] );
		return $newList[ count($newList)-1];
	}
	private function setPingInfo( $monitor_id, $info ) {
		
		for( $i=0; $i<count($this->whmcs_ext); $i++) {
			if( $this->whmcs_ext[$i]['monitor_id'] == $monitor_id ) {
				$this->whmcs_ext[$i]['ping'] = $info;
				return;
			}
		}
	}
	private function extSnapShots( ) {
	
		// get pings monitors ids
		$ping_Ids = $this->_idsList( $this->whmcs_ext, 'monitor_id' );
		$pingIds = implode(',', $ping_Ids);
		$extShots = MonitisApi::getExternalSnapshot( $pingIds );
		/////////
		$pings = array();
		for( $i=0; $i<count($extShots); $i++) {
			$data = $extShots[$i]['data'];
			for( $j=0; $j<count($data); $j++) {
				$info = $this->checkMonitor( $data[$j]['id'], $pings, $data[$j]);
				$this->setPingInfo( $data[$j]['id'], $info );
			}
		}
		return $pings;
	}

	
	private function init_all_servers() {
	
		for( $i=0; $i<count($this->whmcs_all_servers); $i++) {
			//$this->whmcs_all_servers[$i]['monitors'] = array();
			$monitors  = array();
			
			$server = $this->whmcs_all_servers[$i];
			$ext = $this->__monitor( $server['id'], $this->whmcs_ext );
			
			$this->whmcs_all_servers[$i]['available'] = $ext['available'];

			if( $ext && $ext['ping'] ) {
				$monitors['ping'] = $ext['ping'];
			}

			if( $this->whmcs_int ) {
				$int = $this->__monitor( $server['id'], $this->whmcs_int );
				if( $int && isset($int['agent_id']) ) {

					$info = $this->getInternalMonitors( $int['agent_id'] );
					if( $info ) {
						$this->whmcs_all_servers[$i]['agent_id'] = $int['agent_id'];
						$this->whmcs_all_servers[$i]['agent_status'] = $info['status'];
						$monitors['cpu'] = $info['cpu'];
						$monitors['memory'] = $info['memory'];
						$monitors['drive'] = $info['drive'];
					}
				}
			}
			if($monitors && count($monitors) > 0)
				$this->whmcs_all_servers[$i]['monitors'] = $monitors;
		}

	}
	public function init( $opts) {
		$oWhmcs = new WHMCS_class( MONITIS_CLIENT_ID );
		
		$this->whmcs_all_servers = $oWhmcs->all_servers( $opts );
		if( $this->whmcs_all_servers ) {
			$this->total = $this->whmcs_all_servers[0]['total'];
			
			$allSrvrsIds = $this->_idsList( $this->whmcs_all_servers, 'id' );

			$srvrsIds = implode(',', $allSrvrsIds);
			$this->whmcs_ext = $oWhmcs->servers_list_ext( $srvrsIds );
//_dump( $this->whmcs_ext );
			if( $this->whmcs_ext ) {
				$this->extSnapShots();
				$this->whmcs_int = $oWhmcs->servers_list_int( $srvrsIds);
				
				if( $this->whmcs_int ) {
					$agents = $this->getAgents();
					$agentIds = implode(',', $agents );
					$this->allAgents = MonitisApi::allAgentsSnapshot($agentIds);
//L::ii( 'allAgents| allAgents allAgents  ' .  json_encode($this->allAgents) );	
				}
				$this->init_all_servers();
			}
		}

		return $this->whmcs_all_servers;
	}
	public function getTotal() {
		return $this->total;
	}
}
?>