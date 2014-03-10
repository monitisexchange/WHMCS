<?php

class serversMonitorsUpdate {

	public $result = array();
	private $whmcsAllServers = null;
	
	private $whmcsExt = null;
	private $whmcsInt = null;
	private $total = 0;
	private $allAgents = null;
	
	private $allPings = null;
	
	private $pingsStatus = null;
	
	public function __construct () { }

	private function fixMonitor($monitorId) {
	
		for( $i=0; $i<count($this->whmcsInt); $i++) {
			if( $this->whmcsInt[$i]['monitor_id'] == $monitorId ) {
				$this->whmcsInt[$i]['ok'] = 1;
				return $this->whmcsInt[$i];
			}
		}
		return null;
	}
	
	private function unlinkedInternal() {
		$arr = array();
		$servers = array();

		for( $i=0; $i<count($this->whmcsInt); $i++) {

			if( !isset($this->whmcsInt[$i]['ok']) || $this->whmcsInt[$i]['ok'] != 1 ) {
				$arr[] = $this->whmcsInt[$i]['monitor_id'];
				$servers[] = $this->whmcsInt[$i]['server_id'];
			}
		}
		if(count($arr) > 0) {

			for( $i=0; $i<count($arr); $i++) {
				
				$server_id = $servers[$i];
				$server = MonitisHelper::in_array($this->whmcsAllServers, 'id', $server_id);
				
				$this->result[] = array(
					'server' => $server['name'],
					'action' => 'unlink',
					'monitor' => MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $arr[$i])
				);
			}
			
			$ids = implode(',', $arr);
			monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id in ('.$ids.')');
		}
	}
	
	private function internalMonitors( $agnt, $server) {

		$server_id = $server['id'];
		$adentId = $agnt['id'];

		if( isset($agnt['cpu']) ) {
			$status_associate = 'no';

			if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $agnt['cpu']['id'])) {

				$status = '';
				$monitorId = $agnt['cpu']['id'];
				$value = monitisSnapShots::linkInternalMonitor( $monitorId, 'cpu', $adentId, $server_id);
				$this->whmcsInt[] = $value;
				$this->result[] = array(
					'server' => $server['name'],
					'action' => 'link',
					'monitor' => $value
				);
			} 

			$wmon = $this->fixMonitor($agnt['cpu']['id']);
		}

		if( isset($agnt['memory']) ) {

			if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $agnt['memory']['id'])) {
			
				$status = '';
				$monitorId = $agnt['memory']['id'];
				$value = monitisSnapShots::linkInternalMonitor( $monitorId, 'memory', $adentId, $server_id);
				$this->whmcsInt[] = $value;
				$this->result[] = array(
					'server' => $server['name'],
					'action' => 'link',
					'monitor' => $value
				);
			} 
			$wmon = $this->fixMonitor($agnt['memory']['id']);
		}
		
		if( isset($agnt['drives'] ) ) {

			$drives = $agnt['drives'];

			for( $j=0; $j<count($drives); $j++) {
				
				$nm = explode("@", $drives[$j]['name']);
				$name = substr($nm[0], strlen('drive_'));
				if(!MonitisHelper::in_array( $this->whmcsInt, 'monitor_id', $drives[$j]['id'])) {
					$monitorId = $drives[$j]['id'];
					$value = monitisSnapShots::linkInternalMonitor( $monitorId, 'drive', $adentId, $server_id);
					$this->whmcsInt[] = $value;
					$this->result[] = array(
						'server' => $server['name'],
						'action' => 'link',
						'monitor' => $value
					);
				} 
				$wmon = $this->fixMonitor($drives[$j]['id']);
				
			}
		}
	}
	
	private function unlinkedPings( ) {
		
		$extShots = $this->allPings;
		for( $i=0; $i<count($this->whmcsExt); $i++) {
			
			
			$info = MonitisHelper::in_array($extShots, 'id', $this->whmcsExt[$i]['monitor_id']);
			if(!$info) {
				$srv = MonitisHelper::in_array($this->whmcsAllServers, 'id', $this->whmcsExt[$i]['server_id']);
				$this->result[] = array(
					'server' => $srv['name'],
					'action' => 'unlink',
					'monitor' => $this->whmcsExt[$i]
				);
				monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_EXTERNAL_TABLE.' WHERE monitor_id='.$this->whmcsExt[$i]['monitor_id']);
			}
		}
	}
	private function init_all_servers() {

		for( $i=0; $i<count($this->whmcsAllServers); $i++) {

			$server = $this->whmcsAllServers[$i];

			if($this->allPings) {

				// define ping monitor by IP
				$ping = MonitisHelper::in_array($this->allPings, 'url', $server['ipaddress']);
				
				if($ping) {
					$monitorId = $ping['id'];
					$ext = MonitisHelper::in_array($this->whmcsExt, 'monitor_id', $monitorId);
					if(!$ext) {
						// link monitor
						$ext = monitisSnapShots::linkPingMonitor( $monitorId, 'ping', $server['id']);
						$this->result[] = array(
							'server' => $server['name'],
							'action' => 'link',
							'monitor' => $ext
						);
					}
				}
			}

			$agent = MonitisHelper::in_array($this->allAgents['agents'], 'key', $server['hostname']);

			if($agent && isset($agent['status']) && $agent['status'] == 'running') {
				$this->internalMonitors( $agent, $server);
			}
		}
		// remove unlinked internal monitors
		$this->unlinkedInternal();
	}
	
	public function initServers() {
		
			$allSrvrsIds = MonitisHelper::idsByField($this->whmcsAllServers, 'id' );

			$srvrsIds = implode(',', $allSrvrsIds);
			// all linked ping monitors
			

			// get all ping monitors
			$this->allPings = MonitisApi::getExternalMonitors();
			// if Monitis server ok
			if(@$this->allPings['status'] != 'error' && @$this->allPings['code'] != 101) {
				// remove unlinked ping monitors from whmcs
				$this->whmcsExt = monitisWhmcsServer::extMonitorsByServerIds($srvrsIds);
				
				if($this->allPings && isset($this->allPings['testList']) ) {
					// get all ping monitors 
					$this->allPings = $this->allPings['testList'];
				} else {
					$this->allPings = null;
				}
				// remove unlinked ping monitors from whmcs
				if( $this->whmcsExt ) {
					$this->unlinkedPings();
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
	
	public function all() {
		//mysql_query('DELETE FROM mod_monitis_int_monitors WHERE server_id=0');
		
		$this->whmcsAllServers = monitisSqlHelper::query('SELECT id, name, ipaddress, hostname FROM tblservers WHERE disabled=0');
		if( $this->whmcsAllServers ) {
			$this->initServers();
		}
		return $this->result;
	}
}


$oSrv = new serversMonitorsUpdate();
$all = $oSrv->all();
$actionTitle = array(
	'link' => 'add',
	'unlink' => 'remove'
);

?>

<div style="text-align:right;" class="monitis_link_result">	
</div>

<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr>
		<td width="50%" align="left">
			<b><?php echo count($all) ?></b> Changes
		</td>
		<td width="50%" align="right">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabadmin&sub=servers" class="monitis_link_result">&#8592; Back to servers list</a>
		</td>
	</tr>
</table>
	
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <thead>
		<th>&nbsp;</th>
		<th>Server name</th>
		<th>Monitor type</th>
		<th>Action</th>
		<th>&nbsp;</th>
    </thead>
<?php 
if($all && count($all) > 0) {
	for($i=0; $i<count($all); $i++) {
		$server = $all[$i];
?>

	<tr>
		<td>&nbsp;</td>
		<td><?php echo $server['server']?></td>
		<td><?php echo $server['monitor']['monitor_type']?></td>
		<td><?php echo $actionTitle[$server['action']]?></td>
		<td>&nbsp;</td>
	</tr>
<?php
	}
} else {
?>
	<tr>
		<td colspan="6">No changed monitors</td>
	</tr>
<?php
}
?>
</table>
