<?

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
			
			//$status = $agent['status'];
			
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
	private function isDriveAssociate( $monitor_id, $whmcs_drives) {
		for($i=0; $i<count($whmcs_drives); $i++) {
			if( $whmcs_drives[$i]['monitor_id'] == $monitor_id)
				return $whmcs_drives[$i];
		}
		return null;
	}
	/////////////////////////////
	public function associateDrives( $whmcs_drives, $agentInfo, $serverID ) {
		if( $agentInfo && isset($agentInfo['drives']) ) {
			$agentId = $agentInfo['agentId'];
			$monDrives = $agentInfo['drives'];
			$ids = array();
			for($i=0; $i<count($monDrives); $i++) {
				$drive = $monDrives[$i];
				if( isset($drive['id']) ) {
					if( !$whmcs_drives || !$this->isDriveAssociate( $drive['id'], $whmcs_drives) ) {
						$ids[] = $drive['id'];
					} 
				}
			}
			if($ids && count($ids) > 0 ) {
			
				for($i=0; $i<count($ids); $i++) {
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
			}
		}
		return null;
	}
}
?>