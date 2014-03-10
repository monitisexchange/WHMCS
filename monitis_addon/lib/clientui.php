<?php
class monitisClientUi {

	private $pubKeys = null;
	
	public function __construct () {}
	
	private function isMonitisProduct($productid) {
		$vals = monitisSqlHelper::query('SELECT * FROM mod_monitis_product WHERE product_id='.$productid);
		if($vals)
			return $vals[0];
		else
			return null; 
	}	
	
	private function _userAddonsServices($userid) {
		$sql = 'SELECT tblhostingaddons.hostingid serviceid, tblhosting.server as serverid,
			tblhostingaddons.status as addonstatus
			FROM tblhosting
			LEFT JOIN tblhostingaddons on (tblhosting.id = tblhostingaddons.hostingid )
			WHERE tblhosting.userid='.$userid.'  AND tblhosting.server > 0 
			AND tblhostingaddons.status = "Active"
			GROUP BY tblhosting.server';
 
		return monitisSqlHelper::query($sql);
	}

	private function _addonsByServiceId($serviceid, $addonid, $userid) {
		$sql = 'SELECT tblhostingaddons.hostingid serviceid, tblhosting.server as serverid,
			tblhostingaddons.status as addonstatus,
			tbladdons.name as addonname,
			mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid AND tblhosting.userid='.$userid.' AND tblhosting.domainstatus="Active" ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			WHERE tblhostingaddons.hostingid='.$serviceid.' AND tblhostingaddons.addonid='.$addonid.' AND tblhostingaddons.status="Active" ' ;
		$vals = monitisSqlHelper::query($sql);
		
		if($vals)
			return $vals[0];
		else
			return null;
	}	
	private function servers_ext( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey 
		FROM mod_monitis_ext_monitors
		WHERE server_id in ('.$serverIds.') AND available='.$available;
		return monitisSqlHelper::query($sql);
	}
	private function servers_int( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey 
		FROM mod_monitis_int_monitors
		WHERE server_id in ('.$serverIds.') AND available='.$available;
		return monitisSqlHelper::query($sql);
	}
	/////////////////////////////////////////////////////////
	public function externalMonitorInfo( $monitor_id, $userid) {

		$extShot = monitisClientApi::getExternalMonitorInfo($monitor_id, $userid);

		if( !isset($extShot['errorCode']) ) {
			$locations = array();
			$locs = $extShot["locations"];
			if(isset($locs) && count($locs) > 0) {
				for($i=0; $i<count($locs); $i++) {
					$locations[] = $locs[$i]["id"];
				}
			}
			$extShot["locations"] = $locations;
			$extShot["locationIds"] = implode(",", $locations);
			return $extShot;
		} else {
			// unlink monitor
			return null;
		}
	}
	
	private function productByField($products, $fieldname, $fieldvalue) {
		for( $i=0; $i<count($products); $i++) {
			if($products[$i][$fieldname] == $fieldvalue)
				return $products[$i];
		}
		return null;
	}

	private function in_array(& $arr, $fieldName, $fieldValue){
		if($arr && count($arr) > 0) {
			for($i=0; $i<count($arr); $i++) {
				if($arr[$i][$fieldName] == $fieldValue) {
					//return $arr[$i];
					return $i;
				}
			}
		}
		return -1;

	}
	public function clientProductMonitors($userid) {

		$monitors = monitisSqlHelper::query('SELECT * FROM mod_monitis_product_monitor WHERE user_id ='.$userid);

		
		$apiMons = monitisClientApi::externalMonitors($userid);
		if($apiMons && isset($apiMons['testList'])) 
			$apiMons = $apiMons['testList'];
		else 
			$apiMons = null;
		$userProducts = MonitisSeviceHelper::userProducts($userid);
		$avMonitors = array();
		$ids = array();
		if($userProducts) {
		
			for($i=0; $i<count($monitors); $i++) {
				$orderid = $monitors[$i]['order_id'];
				$serviceid = $monitors[$i]['service_id'];
				$isAvailble = false;
				if($monitors[$i]['type'] == 'addon') {
					$addon = $this->_addonsByServiceId( $serviceid, $monitors[$i]['product_id'], $userid );
					if($addon) {
						$monitors[$i]['settings'] = $addon['settings'];
						$product = $this->productByField($userProducts, 'id', $serviceid );
						if($product && $product['status'] == "Active") {
							$monitors[$i]['productname'] = $product['groupname'] .' - '. $product['name'] . ' <div class="addon">addon: '.$addon['addonname'].'</div>';
							$isAvailble = true;
						}
					}

				} else {
	
					$result = select_query( 'tblhosting', 'domainstatus', array('id'=>$serviceid,'userid'=>$userid), null, null, 1 );
					$data = mysql_fetch_assoc( $result );
					if($data && $data['domainstatus'] == "Active") {
						$product = $this->productByField($userProducts, 'id', $serviceid );

						if($product && $product['status'] == "Active") {
							if($monitors[$i]['type'] == 'product') {
								$monproduct = $this->isMonitisProduct($product['pid']);
								$monitors[$i]['settings'] = $monproduct['settings'];
								
							} else {
								// hayk
								$option_id = $monitors[$i]['option_id'];
								$result = select_query( 'mod_monitis_options', 'settings', array('option_id'=>$option_id), null, null, 1 );
								$data = mysql_fetch_assoc( $result );
								$monitors[$i]['settings'] = html_entity_decode($data['settings']);
							}
							$monitors[$i]['productname'] = $product['groupname'] .' - '. $product['name'];
							$isAvailble = true;
						}
					}
				}
				
				if($isAvailble) {
					$monitor_id = intval($monitors[$i]['monitor_id']);
					$monitorIndex = $this->in_array($apiMons, 'id', $monitor_id);

					if($monitorIndex > -1) {
						$monitors[$i]['info'] = $apiMons[$monitorIndex];
						$avMonitors[] = $monitors[$i];
					} else {
						// delete $monitor_id from mod_monitis_product
						//monitisSqlHelper::altQuery('DELETE FROM mod_monitis_product WHERE monitor_id='.$monitor_id );
					}
				}
			}
		} else {
			return null;
		}
		
		return $avMonitors;
	}

	//////////////////////////////////////////////////
	private function _idsList( & $list, $fieldName ){
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
	
	////////////////////////////////////////////////// Network Status
	
	private function isServer($serverid, & $servers) {
		for($i=0; $i<count( $servers ); $i++) {
			if($servers[$i]['serverid'] == $serverid)
				return $servers[$i];
		}
		return null;
	}
	
	private function client_serversIds( $userid ) {
		
		$userProducts = MonitisSeviceHelper::userProducts($userid);
		$result = array( 'status'=>'error', 'msg'=>'No active products');
		$srvrs = array();
		if( $userProducts ){
			for( $i=0; $i<count( $userProducts ); $i++) {
				$product = $userProducts[$i];

				//if( $product["status"] == 'Active' && $product["serverid"] > 0 && !$this->isServer( $product["serverid"], $srvrs) ) {
				if($product["serverid"] > 0 && !$this->isServer( $product["serverid"], $srvrs)) {
					$srvrs[] = array(
						'serverid'=> intval($product["serverid"]),
						'pid'=> intval($product["pid"]),
						'name'=> $product["name"],
						'groupname'=> $product["groupname"]
					);
				}
			}

			$addonsrvrs = $this->_userAddonsServices( $userid );
			if($addonsrvrs) {
				for( $i=0; $i<count($addonsrvrs); $i++) {
					if( !$this->isServer( $addonsrvrs[$i]['serverid'], $srvrs)  ) {
						$srvrs[] = array(
							'serverid'=> intval($addonsrvrs[$i]['serverid']),
							'pid'=> 0,
							'name'=> '',
							'groupname'=> ''
						);
					}
				}
			}
			if( count($srvrs) > 0 ) {
				$result["status"] = 'ok';
				$result["data"] = $srvrs;
				return $result;
			}			
		} 
		return $result;
	}
	
	private function getServerMonitors($server_id, & $arr) {
		
		$monitors = array();
		for($i=0; $i<count($arr); $i++) {
			if($arr[$i]["server_id"] == $server_id && MonitisHelper::in_array($this->pubKeys, 'key', $arr[$i]["publickey"]) ) {
				$monitors[] = $arr[$i];
			}
		}
		return $monitors;
	}

	public function userNetworkStatus( $userid  ) {

		$result = array();
		if( $userid > 0 ) {
			$srvrs = $this->client_serversIds( $userid );

			if( $srvrs && $srvrs["status"] == 'ok' && count($srvrs["data"]) > 0 ) {
				$all_srvrs = $srvrs["data"];
				$serdersIds = $this->_idsList( $all_srvrs, 'serverid' );
				//$serdersIds = array_unique($serdersIds);

				$srvrsIds = implode(',', $serdersIds);
				$ext = $this->servers_ext($srvrsIds, 1 );	// 1 - availble
				$int = $this->servers_int($srvrsIds, 1 );
				
				if( ($ext && count($ext) > 0) || ($int && count($int) > 0)) {
				
					$this->pubKeys = MonitisApi::getPublicKeys();
					
					for( $s=0; $s<count($all_srvrs); $s++) {
						$serverid = $all_srvrs[$s]['serverid'];
						
						if( $ext && count($ext) > 0 ) {
							$extMon = $this->getServerMonitors( $serverid, $ext);
							$all_srvrs[$s]['external'] = $extMon;
						}
						
						if( $int && count($int) > 0 ) {
							$intMon = $this->getServerMonitors( $serverid, $int);
							$all_srvrs[$s]['internal'] = $intMon;
						}
					}
					
					$result["status"] = 'ok';
					$result["data"] = $all_srvrs;
				} else {
					$result["status"] = 'error';
					$result["msg"] = 'No monitors for the active products, or they are not available';
				}
			} else {
				$result["status"] = 'error';
				$result["msg"] = $srvrs["msg"];
			}
		} else {
			$result["status"] = 'error';
			$result["msg"] = 'User login error';
		}
		return $result;
	}
	
	////////////////////////////////////////////////// Client UI functions
	public function errorMessage($msg) {
		echo '<div class="alert alert-error">'.$msg.'</div>';
	}
	public function successMessage($msg) {
		echo '<div class="alert alert-success">'.$msg.'</div>';
	}
}
?>