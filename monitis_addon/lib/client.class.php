<?
class monitisClientClass extends whmcs_db {

	public function __construct () {}
	
	private function _userMonitors( $userid ) {
		//$sql = 'SELECT * FROM mod_monitis_product_monitor WHERE user_id ='.$userid.' AND available=1';
		$sql = 'SELECT * FROM mod_monitis_product_monitor WHERE user_id ='.$userid;

		return $this->query( $sql );
	}
	
	private function _isMonitisProduct( $productid ) {
		$sql = 'SELECT * FROM mod_monitis_product WHERE product_id='.$productid;
		//return $this->query( $sql );
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}	
	
	private function _userAddonsServices( $userid ) {
		$sql = 'SELECT tblhostingaddons.hostingid serviceid, tblhosting.server as serverid,
			tblhostingaddons.status as addonstatus
			FROM tblhosting
			LEFT JOIN tblhostingaddons on (tblhosting.id = tblhostingaddons.hostingid )
			WHERE tblhosting.userid='.$userid.'  AND tblhosting.server > 0 
			AND tblhostingaddons.status = "Active"
			GROUP BY tblhosting.server';
 
		return $this->query( $sql );
	}
	
	private function _addonsServices( $orderid, $addonid ) {
		$sql = 'SELECT tblhostingaddons.hostingid serviceid, tblhosting.server as serverid,
			tblhostingaddons.status as addonstatus,
			tbladdons.name as addonname,
			mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			WHERE tblhostingaddons.orderid='.$orderid.' AND tblhostingaddons.addonid='.$addonid;
		//return $this->query( $sql );
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}
	private function servers_ext( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey 
		FROM mod_monitis_ext_monitors
		WHERE server_id in ('.$serverIds.') AND available='.$available;
		return $this->query( $sql );
	}
	private function servers_int( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey 
		FROM mod_monitis_int_monitors
		WHERE server_id in ('.$serverIds.') AND available='.$available;
		return $this->query( $sql );
	}
	/////////////////////////////////////////////////////////
	public function externalMonitorInfo( $monitor_id ) {
	
		$extShot = MonitisApi::getExternalMonitorInfo( $monitor_id );
		$locations = array();
		$locs = $extShot["locations"];
		if( isset($locs) && count($locs) > 0 ) {
			for($i=0; $i<count($locs); $i++) {
				$locations[] = $locs[$i]["id"];
			}
		}
		$extShot["locations"] = $locations;
		$extShot["locationIds"] = implode(",", $locations);
		return $extShot;
	}
	
	
	public function addonById($addonid) {
		$sql = 'SELECT * FROM mod_monitis_addon WHERE addon_id='.$addonid;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	} 
	
	
	public function clientProductMonitors( $userid ) {

		$monitors = $this->_userMonitors( $userid );
//_dump($monitors);
		$oUserProds = new monitisClientProducts();
		$userProducts = $oUserProds->userProducts( $userid );

//		if( $userProducts) {
			for( $i=0; $i<count($monitors); $i++) {
				$orderid = $monitors[$i]['order_id'];
				if( $monitors[$i]['type'] == 'addon') {
					$addon = $this->_addonsServices( $orderid, $monitors[$i]['product_id'] );
					
					$monitors[$i]['settings'] = $addon['settings'];
					
					//$monitor = $this->addonById( $monitors[$i]['product_id'] );
					
					$product = $oUserProds->productByField( 'id', $addon['serviceid'] );
					$monitors[$i]['productname'] = $product['groupname'] .' - '. $product['name'] . ' <div class="addon">addon: '.$addon['addonname'].'</div>';
				//_dump($addon);
				//} elseif($monitors[$i]['type'] == 'product') {
				} else {
	
					$product = $oUserProds->productByField( 'orderid', $orderid );
					
//$p = $oUserProds->test_productByField( 'orderid', $orderid );


					if( $product ) {
						if( $monitors[$i]['type'] == 'product' ) {
							$monproduct = $this->_isMonitisProduct( $product['pid']);
							$monitors[$i]['settings'] = $monproduct['settings'];
							
						} else {
//$option_id = $monitors[$i]['option_id'];
//_dump($monitors[$i]);
							// hayk
							$option_id = $monitors[$i]['option_id'];
							$result = select_query( 'mod_monitis_options', 'settings', array('option_id'=>$option_id), null, null, 1 );
							$data = mysql_fetch_assoc( $result );
							$monitors[$i]['settings'] = html_entity_decode($data['settings']);

						}
						$monitors[$i]['productname'] = $product['groupname'] .' - '. $product['name'];
						//_dump($monitors[$i]);
					}
				} 
				$monitors[$i]['info'] = $this->externalMonitorInfo( $monitors[$i]['monitor_id'] );
			}
	//	} else {	return null;}
//_dump($monitors);
		return $monitors;
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
	private function getMonitor( $server_id, & $arr ) {
		
		$monitors = array();
		for( $i=0; $i<count($arr); $i++ ) {
			if( $arr[$i]["server_id"] == $server_id ) {
				$monitors[] = $arr[$i];
			}
		}
		return $monitors;
	}
	
	private function isServer( $serverid, & $servers ) {
		for( $i=0; $i<count( $servers ); $i++) {
			if( $servers[$i]['serverid'] == $serverid )
				return $servers[$i];
		}
		return null;
	}
	
	private function client_serversIds( $userid ) {

		$oUserProds = new monitisClientProducts();
		$userProducts = $oUserProds->userProducts( $userid );
		$result = array( 'status'=>'error', 'msg'=>'No active products');
		$srvrs = array();
		if( $userProducts ){
			for( $i=0; $i<count( $userProducts ); $i++) {
				$product = $userProducts[$i];
				//if( $product["status"] == 'Active' && $product["serverid"] > 0 && !$this->isServer( $product["serverid"], $srvrs) ) {
				if( $product["serverid"] > 0 && !$this->isServer( $product["serverid"], $srvrs) ) {
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
				//$srvrs = array_unique($srvrs);
				$result["status"] = 'ok';
				$result["data"] = $srvrs;
				return $result;
			}			
		} 
		return $result;
	}
	
	
	//public function clientMonitors( $userid  ) {
	public function userNetworkStatus( $userid  ) {

		$result = array();
		if( $userid > 0 ) {
			$srvrs = $this->client_serversIds( $userid );
//_dump($srvrs);
			if( $srvrs && $srvrs["status"] == 'ok' && count($srvrs["data"]) > 0 ) {
				$all_srvrs = $srvrs["data"];
				$serdersIds = $this->_idsList( $all_srvrs, 'serverid' );
				//$serdersIds = array_unique($serdersIds);

				$srvrsIds = implode(',', $serdersIds);
				$ext = $this->servers_ext($srvrsIds, 1 );	// 1 - availble
				$int = $this->servers_int($srvrsIds, 1 );
//_dump($ext);
				if( $ext || $int) {
					for( $s=0; $s<count($all_srvrs); $s++) {
						$serverid = $all_srvrs[$s]['serverid'];
						if( $ext && count($ext) > 0 ) {
							$extMon = $this->getMonitor( $serverid, $ext );
							$all_srvrs[$s]['external'] = $extMon;
						}
						if( $int && count($int) > 0 ) {
							$intMon = $this->getMonitor( $serverid, $int );
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
}

?>
