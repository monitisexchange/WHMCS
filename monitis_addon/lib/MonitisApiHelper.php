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
		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {
			return true;
		}
		return false;
	}	
	
	static function addPingMonitor($client_id, $server, $mParams ) {

		$resp = MonitisApi::createExternalPing( $mParams );

		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {
			$newID = $resp['data']['testId'];
			
			$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'external','monitorId'=>$newID) );
			$values = array("server_id" => $server['id'], "monitor_id" => $newID, "monitor_type" => "ping", "client_id"=> $client_id, "publickey"=> $pubKey );
			@insert_query('mod_monitis_ext_monitors', $values);

			return true;
		}
		return false;
	}
	static function addDefaultPing($client_id, $server) {
		
		$url = $server['ipaddress'];
		//$hostname = $server['hostname'];
		$name = $server['hostname'];
		
		if ( empty($url) || empty($name) )
			return false;
			
		//$locationIDs = MonitisConf::$settings['ping']['locationIds'];
		$locationIDs = array_map( "intval", MonitisConf::$settings['ping']['locationIds'] );
		
		//$locationIDs = implode(',', $locationIDs);
		
		$monParams = array(
			'type' => 'ping',
			'name' => $name . '_ping',
			'url' => $url,
			'interval' => MonitisConf::$settings['ping']['interval'],
			'timeout' => MonitisConf::$settings['ping']['timeout'],
			//'locationIds' => MonitisConf::$settings['ping']['locationIds'],
			'locationIds' => implode(',', $locationIDs),
			'tag' => $name . '_whmcs'
			//'uptimeSLA' => '',
			//'responseSLA' => ''
		);

		$res = self::addPingMonitor($client_id, $server, $monParams );

		return $res;
	}
	
	//
	static function addCPU($agentInfo, $cpu ) {
		//$cpu =  MonitisConf::$settings['cpu'];
		
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

		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {
			return $resp['data']['testId'];
		}
		return 0;
	}

	static function addCPUMonitor( $server, $client_id, $agentInfo, & $cpus, $cpuSets ) {

		//$cpus_monitorId = self::monitorIdByAgentId( $intMonitors['cpus'], $agentId );
		$agentId = $agentInfo['agentId'];
		$cpus_monitorId = self::monitorIdByAgentId( $cpus, $agentId );
		if( $cpus_monitorId == 0 ) {
			$cpus_monitorId = self::addCPU($agentInfo, $cpuSets );
		} 
		if( $cpus_monitorId > 0 ) {
			$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'cpu','monitorId'=>$cpus_monitorId) );
			$values = array(
				"server_id" => $server['id'],
				"monitor_id" => $cpus_monitorId,
				"agent_id" => $agentId,
				"monitor_type" => 'cpu',
				"client_id"=> $client_id,
				"publickey"=> $pubKey
			);

			insert_query('mod_monitis_int_monitors', $values);
			return true;
		}
		return false;
	}
	
	/////////////
	static function addMemory($agentInfo, $memory ) {
		//$memory =  MonitisConf::$settings['memory'];

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
		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {
			return $resp['data']['testId'];
		}
		return 0;
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
	
	static function addDefaultAgents($client_id, $server, $mTypes) {

		$hostname=$server['hostname'];
		$agents = MonitisApi::getAgent( $hostname );
		$agentKey = $agents[0]['key'];
		$platform = $agents[0]['platform'];
		$agentId = $agents[0]['id'];
		$monitorTypes = explode(',', MonitisConf::$newServerMonitors);
		
		if( $agentKey == $hostname ) {
			$agentInfo = array(
				'agentKey' => $agentKey,
				'agentId' => $agentId,
				'name' => $hostname,
				'platform' => $platform 
			);			

			$intMonitors = MonitisApi::getInternalMonitors();
			// CPU
			if( in_array('cpu', $monitorTypes ) ) {
				$cpus_monitorId = 0;
				if( isset( $intMonitors['cpus'] ) ) {
					$cpus_monitorId = self::monitorIdByAgentId( $intMonitors['cpus'], $agentId );
				}
				if( $cpus_monitorId == 0 ) {
					$cpus_monitorId = self::addCPU($agentInfo, MonitisConf::$settings['cpu'] );
				} 
				if( $cpus_monitorId > 0 ) {
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'cpu','monitorId'=>$cpus_monitorId) );
					$values = array(
						"server_id" => $server['id'],
						"monitor_id" => $cpus_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'cpu',
						"client_id"=> $client_id,
						"publickey"=>$pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
				}
			}
			// memory
			if( in_array('memory', $monitorTypes ) ) { 
				$memory_monitorId = 0;
				if( isset( $intMonitors['memories'] ) ) {
					$memory_monitorId = self::monitorIdByAgentId( $intMonitors['memories'], $agentId );
				}
				//$monitors = MonitisApi::getAgentInfo( $agentId ); // monitors
				if( $memory_monitorId == 0 ) {
					$memory_monitorId = self::addMemory($agentInfo, MonitisConf::$settings['memory'] );
				} 
				if( $memory_monitorId > 0 ) {
					$pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'memory','monitorId'=>$memory_monitorId) );
					$values = array(
						"server_id" => $server['id'],
						"monitor_id" => $memory_monitorId,
						"agent_id" => $agentInfo['agentId'],
						"monitor_type" => 'memory',
						"client_id"=> $client_id,
						"publickey"=>$pubKey
					);
					insert_query('mod_monitis_int_monitors', $values);
				}
			}
		}
	}
		
	static function addDefaultWeb( $product ) {
		
		$monitor_type = $product['monitor_type'];
		$url = $product['web_site'];
		
		//$name = $product['ordernum'] . '_'.$monitor_type;
		$name = $url . '_'.$monitor_type;

		$interval = MonitisConf::$settings[$monitor_type]['interval'];
		$timeout = MonitisConf::$settings[$monitor_type]['timeout'];
		$locationIDs = array_map( "intval", MonitisConf::$settings[$monitor_type]['locationIds'] );
		//$locationIDs = array(1, 9, 10);
		//$tag = $product['user_id'] . '_whmcs';
		
		$tag = $product['tag'];
		if (empty($url))
			return false;
		if( $monitor_type == 'http')
			$resp = MonitisApi::createExternalHttp($name, $url, $interval, $timeout, $locationIDs, $tag);
		else
			$resp = MonitisApi::createExternalHttps($name, $url, $interval, $timeout, $locationIDs, $tag);
			

		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {

			//$newID = $resp['data']['testId'];
			//return array('monitor_id'=> $resp['data']['testId'], 'tag' => $tag);
			//return $resp['data']['testId'];
			return $resp['data'];
		}
		return false;
	}
	////////////////////////// mod_monitis_servers 
	static function addWebPing($product) {
		
		$url = $product['web_site'];
		$name = $product['ordernum'];

		if ( empty($url) || empty($name) )
			return false;
		
		$locationIDs = array_map( "intval", MonitisConf::$settings['ping']['locationIds'] );
		
		$monParams = array(
			'type' => 'ping',
			'name' => $url . '_ping',
			'url' => $url,
			'interval' => MonitisConf::$settings['ping']['interval'],
			'timeout' => MonitisConf::$settings['ping']['timeout'],
			'locationIds' => implode(',', $locationIDs),
			'tag' => $product['tag']
		);
		$resp = MonitisApi::createExternalPing( $monParams );

		if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists') {
			//return $resp['data']['testId'];
			return $resp['data'];
		}
		return false;
	}

	static function addAllDefault($client_id, $server) {
		$monitorTypes = explode(',', MonitisConf::$newServerMonitors);
		
		foreach ($monitorTypes as $type) {
			switch ($type) {
				case 'ping':
					self::addDefaultPing($client_id, $server);
					self::addDefaultAgents($client_id, $server, $monitorTypes);
					break;
				case 'http':
					self::addDefaultHttp($server);
					break;
				case 'https':
					self::addDefaultHttps($server);
					break;
			}
		}
	}
	
	static function getAllAgentsSnapshot() {
		$linux = MonitisApi::getAgentsSnapshot('LINUX');
		$windows = MonitisApi::getAgentsSnapshot('WINDOWS');
		$toReturn = @array_merge($linux['agents'], $windows['agents']);
		return $toReturn;
	}
	
	static function embed_module_by_pubkey( $publicKey ) {
		return  '<script type="text/javascript">
		monitis_embed_module_id="'.$publicKey.'";
		monitis_embed_module_width="500";
		monitis_embed_module_height="350";
		monitis_embed_module_readonly="false";
		monitis_embed_module_readonlyChart ="false";
		monitis_embed_module_readonlyDateRange="false";
		monitis_embed_module_datePeriod="0";
		monitis_embed_module_view="1";
		</script>
		<script type="text/javascript" src="https://api.monitis.com/sharedModule/shareModule.js"></script>
		<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';
	}
	
	static function embed_module($monitor_id, $monitor_type) {
		$params = array('moduleType'=>$monitor_type,'monitorId'=>$monitor_id);
		$resp = MonitisApi::getWidget($params);
		$publicKey = $resp['data'];
	
		return  '<script type="text/javascript">
		monitis_embed_module_id="'.$publicKey.'";
		monitis_embed_module_width="500";
		monitis_embed_module_height="350";
		monitis_embed_module_readonly="false";
		monitis_embed_module_readonlyChart ="false";
		monitis_embed_module_readonlyDateRange="false";
		monitis_embed_module_datePeriod="0";
		monitis_embed_module_view="1";
		</script>
		<script type="text/javascript" src="https://api.monitis.com/sharedModule/shareModule.js"></script>
		<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';
	}	

}
