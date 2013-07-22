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
			$agentInfo = array(
				'agentKey' => $agent['key'],
				'agentId' => $agent['id'],
				'name' => $keyRegExp,
				'tag' => $keyRegExp.'_whmcs',
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
}
?>