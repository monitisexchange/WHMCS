<?
 
class clientServicesClass extends whmcs_db {
	
	private $serviceData = null;
	private $actionBehavior = null;
	
	public function __construct () {}

	private function _activeServicesByAddonId( $addonid=0 ) {

		$sql = 'SELECT tblhosting.userid, CONCAT( tblclients.firstname, " ", tblclients.lastname) as client, addonid, 
			tblhosting.server as serverid, tblhosting.id as serviceid, tblhosting.domain, tblhosting.dedicatedip, 
			tblorders.id as orderid, tblorders.ordernum,
			tbladdons.name,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblorders on ( tblorders.id = tblhostingaddons.orderid ) 
			LEFT JOIN tblclients on ( tblclients.id = tblhosting.userid ) 
			WHERE tblhostingaddons.addonid='.$addonid. ' AND tblhostingaddons.status="Active" AND tblorders.status="Active" 
			ORDER BY tblhostingaddons.orderid DESC';
		return $this->query( $sql );
	}
	
	private function _addonsServicesByOrderId( $orderid=0 ) {
		$sql = 'SELECT addonid, tblhostingaddons.hostingid, tblhosting.server as serverid, tblhosting.domain, tblhosting.dedicatedip, 
			tblhostingaddons.status as addonstatus,
			tbladdons.name as addonname,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			WHERE tblhostingaddons.orderid='.$orderid;
		return $this->query( $sql );
	}
	
	private function _addonServiceByAddonId( $addonid ) {
		$sql = 'SELECT addonid, tblhostingaddons.hostingid, tblhosting.server as serverid, tblhosting.domain, tblhosting.dedicatedip, 
			tblhostingaddons.status as addonstatus,
			tbladdons.name as addonname,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			WHERE tblhostingaddons.addonid='.$addonid;
		return $this->query( $sql );
	}
	// LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid AND tblhosting.orderid = tblhostingaddons.orderid ) 
	
	private function _clientAddonServiceByAddonId( $addonid, $addonserviceid ) {
		$sql = 'SELECT addonid, tblhosting.userid, tblhosting.server as serverid, tblhosting.domain, tblhosting.dedicatedip, 
			tblhostingaddons.status as addonstatus,
			tblhostingaddons.id as addonserviceid,
			tblhosting.orderid,
			tblorders.ordernum,
			tblorders.status as orderstatus,
			tbladdons.name as addonname,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblorders on (tblorders.id = tblhosting.orderid ) 
			WHERE tblhostingaddons.addonid='.$addonid.' AND tblhostingaddons.id='.$addonserviceid;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}
	
	private function _addonServiceById( $serviceid ) {
	
		$sql = 'SELECT tblhostingaddons.orderid, tblhosting.server as serverid FROM tblhostingaddons 
		LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
		LEFT JOIN tblhosting on ( tblhosting.id = tblhostingaddons.hostingid ) 
		WHERE tblhostingaddons.id='.$serviceid;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}

	

	
	private function _productServiceById($serviceid, $pid) {
		$sql = 'SELECT * FROM tblhosting 
		LEFT JOIN mod_monitis_product on ( mod_monitis_product.product_id = '.$pid.' ) 
		WHERE id='.$serviceid;
		
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}	
	
	private function _monitorById($monitor_id) {
		$sql = 'SELECT * FROM mod_monitis_product_monitor WHERE monitor_id='.$monitor_id;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}
	
	private function _productById($pid) {
		$sql = 'SELECT * FROM mod_monitis_product WHERE product_id='.$pid;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}

	public function _monitorByOrderId( $orderid ) {
		$sql = 'SELECT * FROM mod_monitis_product_monitor WHERE order_id='.$orderid;
		return $this->query( $sql );
	}

	private function _monitorByType($pid, $type, $userid) {
		$sql = 'SELECT * FROM mod_monitis_product_monitor WHERE type="'.$type.'" AND product_id='.$pid.' AND user_id='.$userid;
		return $this->query( $sql );
	}
	
	private function _productSeviceById( $serviceid ) {
		$sql = 'SELECT * FROM tblhosting WHERE id='.$serviceid;
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}
	
	public function removeProductMonitorsById($monitor_id) {
		$sql = 'DELETE FROM mod_monitis_product_monitor WHERE monitor_id='.$monitor_id;
echo $sql;
		return $this->query_del( $sql );
	}
	
	////////////////////////////////////// filter Monitors if it deleted in the dashboard remove it
	private function isExistMonitor( $monitor_id, & $existMonitor ) {
		
		for( $i=0; $i<count($existMonitor); $i++) {
			if( $existMonitor[$i]["id"] == $monitor_id ) {
				//$this->removeProductMonitorsById($monitor_id);
				return $existMonitor[$i];
			}
		}
		return null;
	}
	
	private function monsIds( & $mons ) {
		$ids = array();
		for( $i=0; $i<count($mons); $i++) {
//_dump($mons[$i] );
			$ids[] = $mons[$i]["monitor_id"];
		}
		return $ids;
	}


	private function filterMonitors( & $mons ) {
		if( $mons ) {
			$aIds = $this->monsIds($mons);	// 
			$ids = implode(",", $aIds);
			$existMonitors = MonitisApi::externalSnapshot( $ids );
			
			$arr = array();
			for( $i=0; $i<count($mons); $i++) {
				//$m = $this->isExistMonitor( $mons[$i]["monitor_id"], $existMonitors );
				if( $this->isExistMonitor( $mons[$i]["monitor_id"], $existMonitors ) ) {
					$arr[] = $mons[$i];
				} else {
					$this->removeProductMonitorsById( $mons[$i]["monitor_id"] );
				}
			}
			return $arr;
		} else {
			return null;
		}
	}
	
	private function monitorByOrderId( $orderid ) {

		$mons = $this->_monitorByOrderId( $orderid );
		if( $mons )  {
			echo $orderid."<br>";
			return $this->filterMonitors( $mons );
		} else return null;
	}
	
	private function monitorByType( $pid, $type, $userid ) {
		$mons = $this->_monitorByType( $pid, $type, $userid );
		if( $mons && count($mons)>0 ) {
//_dump($mons);
			return $this->filterMonitors( $mons );
		} else return null;
	}
	

	//////////////////////////////////////////
	private function getProduct( & $lineitem ) {
		$arr = array();
		if($lineitem) {
			for( $i=0; $i<count($lineitem); $i++) {
				if($lineitem[$i]['type'] == 'product') {
					$item = $lineitem[$i];
					return array(
						'type' => $item['type'],
						'serviceid' => $item['relid'],
						'producttype' => $item['producttype'],
						'productname' => $item['product'],
						'status' => $item['status']
					);
				}
			}
		}
		return null;
	}
	private function url_IP( & $product, $monitotType ) {
		$url = '';
		if( $monitotType == 'ping'){
			if( !empty( $product['dedicatedip'] )) {
				$url = $product['dedicatedip'];
			} elseif( !empty( $product['domain'] ) ) {
				$url = $product['domain'];
			} 
		} else {
			if( !empty( $product['domain'] ) ) {
				$url = $product['domain'];
			} elseif(!empty( $product['dedicatedip'] )) {
				$url = $product['dedicatedip'];
			} 
		}
		return $url;
	}

	private function addonService( $vars ) {
		
		$addonserviceid = $vars['id'];
		$serviceid = $vars['serviceid'];
		$userid = $vars['userid'];
		$addonid = $vars['addonid'];
  
		$result = array( 'status'=>'error', 'msg'=>'no monitis product', 'data'=>null );
		
		// get order id and check is monitis addon
		$addonservice = $this->_addonServiceById( $addonserviceid );	// tblhostingaddons
		
		if($addonservice) {
			$result['status'] = 'ok';
			$result['msg'] = 'monitis addon';
			
			$orderid = $addonservice['orderid'];
			$product_addons = $this->_clientAddonServiceByAddonId( $addonid, $addonserviceid );

			if( $product_addons ) {
				//$monitors = $this->monitorByOrderId($orderid); // ????
				$monitors = $this->monitorByType($addonid,'addon',$userid);

				$client_product = $this->clientProductBySeviceId( $serviceid, $adminuser );
				
				if( $product_addons['orderstatus'] == 'Active' ) {
	
					$product_addons['web_site'] = $this->url_IP( $product_addons, $product_addons['monitor_type'] );
					$module = array(
						'clientproduct' => $client_product,
						'addon' => $product_addons,
						'monitors' => $monitors
					);
					
					$result['data'] = $module;
					$result['msg'] = 'success';
					return $result;
				} else {	// order error
					$result['status'] = 'error';
					$result['msg'] = 'order status '.$product_addons['orderstatus'];
				}
			} else { // no addons
				$result['status'] = 'error';
				$result['msg'] = 'no addons';
			}
		} // no monitis product
		return $result;
	}
	private function isAddonActive( & $data ) {
		if( $data['addon']['orderstatus'] == 'Active' && 
			$data['clientproduct'] && $data['clientproduct']['status'] == 'Active' && 
			$data['addon']['addonstatus'] == 'Active' ) {
			return true;
		}
		return false;
	}
	
	public function addonHookHandler( $vars, $status ) {
		/* Hooks:	Active, Pending, Suspended, Terminated, Cancelled, Fraud
		 * Actions: active, noaction, unlink, suspended, delete
		 */
		$result = array('status'=>'ok', 'msg'=>'No action' );
		$this->actionBehavior = MonitisConf::$settings['order_behavior'];
		$action = $this->actionBehavior[$status];

//echo "*************** hook=$status ******** action=$action *********************** <br>";		
		$result['action'] = $action; 
		$result['hook'] = $status; 
		if( $action != 'noaction') {
			$response = $this->addonService( $vars );
			//$addonid = $vars['addonid'];

			if( $response['status'] == 'ok' && $response['data'] ) {
				//$this->serviceData = $response['data'];
				$data = $response['data'];
//_dump($data);
				if( $data['monitor'] ) {
					$monitor_id = $data['monitor']['monitor_id'];
					switch( $action ) {
						case 'suspended':
							$resp = MonitisApi::suspendExternal( $monitor_id );
							if( $resp['status'] == 'ok') {
								$result['msg'] = "Monitor $monitor_id suspended";
							} else { $result['status'] = 'error'; $result['msg'] = $resp['error']; }
						break;
						case 'delete':
							$resp = MonitisApiHelper::deleteExternalMonitor( $monitor_id );
							if( $resp['status'] == 'ok') {
								$result['msg'] = "Monitor $monitor_id deleted";
							} else { $result['status'] = 'error'; $result['msg'] = $resp['error']; }
						break;
						case 'unlink':
							$resp = MonitisApiHelper::unlinkExternalMonitor( $monitor_id );
							$result['msg'] = "Monitor $monitor_id unlinked";
						break;
						case 'active':
						case 'create':
							if( $this->isAddonActive( $data ) ) {
								$resp = MonitisApi::activateExternal( $monitor_id );
								if( $resp['status' == 'ok']) {
									$result['msg'] = "Monitor $monitor_id activated";
								} else { $result['status'] = 'error'; $result['msg'] = $resp['error']; }
							} else {
								$result['status'] = 'error';
								$result['msg'] = 'Addon or service is not Active';
							}
						break;
					}
				} elseif( $action == 'active' && $this->isAddonActive( $data ) ) {
					$resp = $this->createMonitor( $data['addon'] );
					$result = $resp;
				}
			} else {
				//$result['msg'] = 'No monitis addon or no action';
				$result = $response;
			}
		}
		return $result;
	}	

	
	//////////////////////////////////////////////
	private function moduleService( & $vars ) {

		$result = array( 'status'=>'error', 'msg'=>'no monitis product', 'data'=>null );
		
//_dump($vars);

		$serviceid = $vars['params']['serviceid'];
		$pid = $vars['params']['pid'];

		// get product and check monitis
		$service = $this->_productServiceById($serviceid, $pid);

		if( $service ) {
			$result['status'] = 'ok';
//_dump($service);
			$orderid = $service['orderid'];
			$monitors = $this->monitorByOrderId($orderid);
			$userid = $service['userid'];
			
			//if( $monitor ) {	
				$adminuser = MonitisConf::getAdminName();
				$values = array( "id"=> $orderid );
				$orders = localAPI( "getorders", $values, $this->adminuser);
				if( $orders['result'] == 'success' ) {
					$order = $orders['orders']['order'][0];
				
					$client_product = $this->clientProductBySeviceId( $serviceid, $adminuser );
					$product_addons = $this->_addonsServicesByOrderId( $orderid );
					
					$module = array(
						'userid' => $userid,
						'serviceid' => $serviceid,
						'orderid' => $orderid,
						'orderstatus'=>$order['status'],
						'ordernum' => $order['ordernum'],
						'clientproducts' => $client_product,
						'addons'=> $product_addons,
						'monitors' => $monitors
					);
					
					$result['msg'] = 'ok';
					$result['data'] = $module;
				} else {
					$result['msg'] = 'order error';
				}
		}
//_dump($module);
		return $result;
	}
	
	
	private function fieldsList( $fieldname, & $list ) {
		$arr = array();
		for($i=0; $i<count($list); $i++) {
			$arr[] = $list[$i][$fieldname];
		}
		
		return $arr;
	}
	
	//private function isAddonActive( $addonid ) {
	private function addonStatus( $addonid ) {
		$data = $this->serviceData;
		$resule = false;
		if( isset($data['clientproducts']) && $data['clientproducts'] && strtolower($data['clientproducts']['status']) == 'active' ) {
			
			if( isset($data['addons']) && $data['addons'] ) {
				$addons = $data['addons'];
				for( $i=0; $i < count($addons); $i++) {
					if( $addons[$i]['addonid'] == $addonid )
						//$resule = true;
						return strtolower($addons[$i]['addonstatus']);
				}
			}
		}
		return '';
	}

	//private function isClientProductActive() {
	private function clientProductStatus() {
		$data = $this->serviceData;
		if( isset($data['clientproducts']) && $data['clientproducts'] ) {
			return strtolower($data['clientproducts']['status']);
			//return true;
		}
		return '';
	}
	

	private function isMonitorById( $monitor_id ) {
	
		$monitors = $this->serviceData["monitors"];
		for($i=0; $i<count( $monitors ); $i++) {
			if( $monitors[$i]['monitor_id'] == $monitor_id )
				return $monitors[$i];
		}
		return null;
	}
	
	
	private function isMonitorExist(& $monitors, $productid, $producttype, $monitor_type='' ) {
		
		for($i=0; $i<count( $monitors ); $i++) {
		
//echo "isMonitorExist ****** productid = $productid ******** producttype = $producttype********************** monitor_type = $monitor_type <br>";	
		
			if( $monitors[$i]['type'] == $producttype && $monitors[$i]['product_id'] == $productid ) { 
				if( empty($monitor_type) || ( !empty($monitor_type) && $monitors[$i]['monitor_type'] == $monitor_type) )
					return $monitors[$i];
			}
		}
		return null;
	}
	
	private function createNewMonitors( $action='active' ) {
	
		$result = array('monitors'=>array());
		$data = $this->serviceData;
		$monitors = $data['monitors'];
		$params = array();
		$params['tag'] = ''.$data['userid'].'_whmcs';
		//$params['serverid'] = $data['serverid'];
		$params['userid'] = $data['userid'];
		$params['orderid'] = $data['orderid'];
		$params['ordernum'] = $data['ordernum'];
		$params['serverid'] = $clientproducts['serverid'];
//_dump($data);

		$productStatus = '';
		if( $data['clientproducts'] ) {
		
			$clientproducts = $data['clientproducts'];
			$productStatus = strtolower($clientproducts['status']);
			
			$params['serverid'] = $clientproducts['serverid'];
			$params['productid'] = $clientproducts['productid'];

			if( $clientproducts['customfields'] && isset($clientproducts['customfields']['monitor_type']) ) {
			
				$existMonitor = $this->isMonitorExist( $monitors, $clientproducts['pid'], 'product' );
				
				if( !$existMonitor ) {		// && ($productStatus == 'active' || $action == 'active')
					$customfields = $clientproducts['customfields'];
					$type = $customfields['monitor_type'];
		//_dump($data);
					$params['producttype'] = 'product';
					$params['monitor_type'] = $type;
					$params["web_site"] = $customfields['web_site'];
					$params['settings'] = $customfields['settings'];
					// call 
					//m_log( $params, 'customfields', '_monitors');
					$result['monitors'][] = $this->createMonitor( $params );
					//$monitors[] = $params;
				} elseif( $existMonitor ) {

					$cProductStatus = $this->clientProductStatus(); 
					if( !empty($cProductStatus) ) {
						$monitor_id = $existMonitor['monitor_id'];
						
						$result['monitors'][$monitor_id] = $this->_doMonitorAction( $monitor_id, $this->actionBehavior[$cProductStatus] );
						//m_log( 'customfields id '.$monitor_id.'***************** action = '.$this->actionBehavior[$cProductStatus], ' customfields monitor', '_monitors');
					}
				}
			}
			
			if( $clientproducts['configoptions'] && count($clientproducts['configoptions']) > 0 ) {
			
				$params['producttype'] = 'option';
				//$params['productid'] = $clientproducts['productid'];

				for($i=0; $i < count($clientproducts['configoptions']); $i++) {
				
					$configoption = $clientproducts['configoptions'][$i];
					$type = $configoption['monitor_type'];

					$existMonitor = $this->isMonitorExist( $monitors, $clientproducts['productid'], 'option', $type );

					if(!$existMonitor ) {		// && ($productStatus == 'active' || $action == 'active') 
						
						$params["web_site"] = $this->url_IP( $clientproducts, $type );

						$params['monitor_type'] = $type;
						$params['settings'] = $configoption['settings'];
//_dump($params);
						//m_log( $params, 'configoptions', '_monitors');
						$result['monitors'][] = $this->createMonitor( $params );
					} elseif( $existMonitor ) {
						$cProductStatus = $this->clientProductStatus(); 
						if( !empty($cProductStatus) ) {
							$monitor_id = $existMonitor['monitor_id'];
							
							$result['monitors'][$monitor_id] = $this->_doMonitorAction( $monitor_id, $this->actionBehavior[$cProductStatus] );
							//m_log( 'configoptions id '.$monitor_id.'***************** action = '.$this->actionBehavior[$cProductStatus], ' configoptions monitor', '_monitors');
						}
					}
				}
			}			
		}
	
		if( $data['addons'] ) {
			for($i=0; $i<count($data['addons']); $i++) {
				$addon = $data['addons'][$i];
				$addonid = $addon['addonid'];
				$existMonitor = $this->isMonitorExist( $monitors, $addonid, 'addon' );
				$addonStatus = $this->addonStatus( $addonid );
				// if monitor doesn't exist
//m_log( "addonStatus = $addonStatus **************", '***************', '_monitors_order');
				if( !$existMonitor && $addonStatus == 'active' ) {
					$addon = $data['addons'][$i];
					$params['serverid'] = $addon['serverid'];
					$params['producttype'] = 'addon';
					$params['productid'] = $addonid;
					$type = $addon['monitor_type'];
					
					$web_site = $this->url_IP( $addon, $type );
					$params["web_site"] = $web_site;
					
					$params['monitor_type'] = $type;
					$params['settings'] = $addon['settings'];
					$result['monitors'][] = $this->createMonitor( $params );
					// call 
//m_log( $params, 'addon product', '_monitors');
				//} elseif( $monitors && $existMonitor ) {
				} elseif( $existMonitor ) {
					if( !empty($addonStatus) ) {
						$monitor_id = $existMonitor['monitor_id'];
						$result['monitors'][$monitor_id] = $this->_doMonitorAction( $monitor_id, $this->actionBehavior[$addonStatus] );
//m_log( 'monitor id = '.$monitor_id.'*****************'.$this->actionBehavior[$addonStatus], 'addon product', '_monitors');
					}
				}
			}
		}
	return $result;
	}
	
	public function clientProductBySeviceId( $serviceid, $adminuser ) {
		$values = array( "serviceid"=> $serviceid );
		$prdcts = localAPI( "getclientsproducts", $values, $adminuser );
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
			$product = $products[0];

			$module = array(
				'orderid' => $product['orderid'],
				'serviceid' => $serviceid,
				'serverid' => $product['serverid'],
				'pid' => $product['pid'],
				'productid' => $product['pid'],
				'domain' => $product['domain'],
				'dedicatedip' => $product['dedicatedip'],
				'status' => $product['status'],
				'productname' => $product['groupname'] .' - ' . $product['name']
			);
			
			// custom fields
			$module['customfields'] = null;
			$monitis_product = $this->_productById( $product['pid'] );
			if( $monitis_product ) {
				$customfield = $product['customfields']['customfield'];
				if( $customfield ) {
					$flds = array();
					for($i=0; $i<count($customfield); $i++){
						$field = $customfield[$i];
						if( $field['name'] == MONITIS_FIELD_WEBSITE ) { 
							$flds["web_site"] = $field['value'];
						}
						if( $field['name'] == MONITIS_FIELD_MONITOR) {
							$flds["monitor_type"] = $field['value'];
						}
					}
					$module['customfields'] = $flds;
					$module['customfields']['settings'] = $monitis_product['settings'];
				}
			}
			
			// hayk 
			// configure options
			$module['configoptions'] = array();
			$configoptions = $product['configoptions']['configoption'];
			if( $configoptions ) {
				foreach( $configoptions as $option ) {
					if( $option['type'] == 'yesno' ) {
						if( $option['value'] == '1' ) {
							$optionName = $option['option'];
						} else {
							continue;
						}
					} else {
						$optionName = $option['value'];
					}
					$query = '
						SELECT monitis.settings, monitis.type, monitis.is_active, options.id
						FROM `mod_monitis_options` AS monitis
							LEFT JOIN `tblproductconfigoptionssub` AS options
								ON options.id = monitis.option_id
						WHERE options.optionname = "'.$optionName.'" AND options.configid = '.$option['id'].'
						LIMIT 1
					';
					$result = mysql_query( $query );
					$optionMonitis = mysql_fetch_assoc( $result );
					$optionForModul = array();
					if( $optionMonitis ) {
						array_push( $module['configoptions'], array(
							//'option_id' => $optionMonitis['id'],
							'monitor_type' => $optionMonitis['type'],
							'settings' => html_entity_decode( $optionMonitis['settings'] ),
							'is_active' => $optionMonitis['is_active']
						) );
					}
				}
			}
			return $module;
		}
		return null;
	}

	
	public function byOrderId( $orderid ) {
	
		$result = array( 'status'=>'error', 'msg'=>'no monitis product', 'data'=>null );
		
		$adminuser = MonitisConf::getAdminName();
		$values = array( "id"=> $orderid  );
		$iOrder = localAPI( "getorders", $values, $adminuser);
		
//_dump($iOrder);
		if( $iOrder && $iOrder['result'] == 'success') {
			$order = $iOrder['orders']['order'][0];
			if( $order ){

				$product_addons = $this->_addonsServicesByOrderId( $orderid );
				$product = $this->getProduct( $order['lineitems']['lineitem'] );
				$client_product = null;
				if( $product ) {
					$client_product = $this->clientProductBySeviceId( $product['serviceid'], $adminuser );
				}
				if( $product_addons || $client_product ) {
					$result['status'] = 'ok';
					
					$monitors = $this->monitorByOrderId($orderid);
					$module = array(
						'userid' => $order['userid'],
						'orderid' => $orderid,
						'orderstatus'=>$order['status'],
						'ordernum' => $order['ordernum'],
						'clientproducts' => $client_product,
						'addons' => $product_addons,
						'monitors' => $monitors
					);
//_dump($module);
					$result['msg'] = 'success';
					$result['data'] = $module;
				} else {
					$result['msg'] = 'no monitis product';
				} 
			} 
		}
		return $result;
	}
	
	private function toDo( & $data, $action ){
	
//echo "****** toDo  $action ******************** <br>";
//m_log( "******* toDo  $action ***********************", 'addon product', '_testing');
		
//_dump($this->serviceData);

		$result = array( 'status'=>'','msg'=>'');
		if( $action == 'active') {
			$result = $this->createNewMonitors( );
		} elseif( isset($data['monitors']) && $data['monitors'] ) {
			$monitors = $data['monitors'];
			$result['monitors'] = array();
			for( $i=0; $i<count($monitors); $i++) {
				$monitor_id = $monitors[$i]['monitor_id'];
				$result['monitors'][$monitor_id] = $this->_doMonitorAction( $monitor_id, $action );
			}
		} 
		return $result;
	}
	
	
	
	private function _doMonitorAction( $monitor_id, $action ) {
	
//echo "****** _doMonitorAction  action=$action monitor_id=$monitor_id ******************** <br>";
//m_log( "***** _doMonitorAction  action=$action monitor_id=$monitor_id ********************", 'addon product', '_testing');
//echo "****** action = $action *********** monitor_id = $monitor_id ******************** ";
		$result = array('status'=>'***', 'action'=>$action, 'monitor_id'=>$monitor_id, 'msg'=>'' );
		switch( $action ) {
			case 'suspended':
				$resp = MonitisApi::suspendExternal( $monitor_id );
				if( $resp['status'] == 'ok') {
					$result['status'] = "ok";
					$result['msg'] = "Monitor $monitor_id suspended";
				} else { $result['status'] = 'error'; $result['msg'] = $result['error']; }
			break;
			case 'delete':
				$resp = MonitisApiHelper::deleteExternalMonitor( $monitor_id );
				if( $resp['status'] == 'ok') {
					$result['status'] = "ok";
					$result['msg'] = "Monitor $monitor_id deleted";
				} else { $result['status'] = 'error'; $result['msg'] = $resp['error']; }
			break;
			case 'unlink':
				$resp = MonitisApiHelper::unlinkExternalMonitor( $monitor_id );
				$result['status'] = "ok";
				$result['msg'] = "Monitor $monitor_id unlinked";
			break;
			case 'active':
			case 'create':
				$mon = $this->isMonitorById( $monitor_id );
m_log( "***** _doMonitorAction  action=$action monitor_id=$monitor_id ******************** ". $this->clientProductStatus(), '************', '_testing');
m_log( $mon, '************', '_testing');

				if( ($mon['type'] == 'addon' && $this->addonStatus( $mon["product_id"]) == 'active' ) ||
					( ( $mon['type'] == 'product' || $mon['type'] == 'option') && $this->clientProductStatus() == 'active') ) {
					$resp = MonitisApi::activateExternal( $monitor_id );
					if( $resp['status'] == 'ok') {
						$result['status'] = "ok";
						$result['msg'] = "Monitor $monitor_id activated";
					} else { $result['status'] = 'error'; $result['msg'] = $resp['error']; }
				} else {
					$result['status'] = 'error';
					$result['msg'] = 'Module or service is not Active';
				}
			break;
		}
		return $result;
	}
	
	
	public function orderHookHandler( $vars, $status ) {

		$result = array('status'=>'ok', 'msg'=>'No action' );
		$orderid = $vars["orderid"];
		
		$this->actionBehavior = MonitisConf::$settings['order_behavior'];
		$action = $this->actionBehavior[$status];
		
		if( $action != 'noaction') {
			$result['action'] = $action; 
			$result['hook'] = $status; 		
			$response = $this->byOrderId( $orderid );
			if( $response && $response['data'] ) {
				$this->serviceData = $response['data'];
//_dump($this->serviceData);
				$result['toDoResult'] = $this->toDo( $response['data'], $action );
				$result['action'] = $action; 
				$result['hook'] = $status;
				$result['status'] = 'ok';
				$result['msg'] = '****';
			} else {
				$result['status'] = '***';
				$result['msg'] = 'No data';
			}
		} else {
			$result['status'] = '***';
			$result['msg'] = 'No monitis product or no action';
		}
		return $result;
	}
	
	public function moduleHookHandler( $vars, $status ) {
	
		$result = array('status'=>'ok', 'msg'=>'No action' );
		$this->actionBehavior = MonitisConf::$settings['order_behavior'];
		$action = $this->actionBehavior[$status];
		
		$result['action'] = $action; 
		$result['hook'] = $status; 
		if( $action != 'noaction') {
			$response = $this->moduleService( $vars );
			
			if( $response['status'] == 'ok' && $response['data'] ) {
				$this->serviceData = $response['data'];
//_dump($this->serviceData);
//echo "****** moduleHookHandler ******************** <br>";
//m_log( "****** moduleHookHandler ********************", 'addon product', '_testing');

m_log( $this->serviceData, '************ this->serviceData  & action=$action', '_testing');

				$result['toDoResult'] = $this->toDo( $response['data'], $action );
				$result['action'] = $action; 
				$result['hook'] = $status;
				$result['status'] = 'ok';
				$result['msg'] = '****';
				
			} else {
				$result['msg'] = 'No monitis product or no action';
			}
		} 
		return $result;
	}
	
	
	public function editHookHandler( $vars ) {

		$serviceid = $vars['serviceid'];
		$userid = $vars['userid'];

		$result = array('status'=>'error', 'msg'=>'Incorrect parameters' );
		if( isset($serviceid) && isset($userid) ) {

			$oUserProds = new monitisClientProducts();
			$userProducts = $oUserProds->userProductsByServiceId( $userid, $serviceid );
			
//_dump($userProducts);

			if($userProducts) {
				$product = $oUserProds->productByField( 'id', $serviceid );

				$orderid = $product['orderid'];
				$this->actionBehavior = MonitisConf::$settings['order_behavior'];
				
				$response = $this->byOrderId( $orderid );
				if( $response && $response['data'] ) {
					$this->serviceData = $response['data'];
					
					$productStatus = $this->clientProductStatus();
					$action = $this->actionBehavior[$productStatus];

//echo "***************** productStatus  = $productStatus ****** action = $action <br>";
//_dump($this->serviceData);

					$result['toDoResult'] = $this->toDo( $response['data'], $action );
					$result['action'] = $action; 
					$result['hook'] = $productStatus;
					$result['status'] = 'ok';
					$result['msg'] = '****';
					
					$result['action'] = $action; 
					$result['hook'] = $status;
				}
			} else {
				$result['status'] = 'error'; $result['msg'] = 'No product'; 
			}
		}
		return $result;
	}
	
	
	
	
	////////////////////////////////////////////////
	public function createMonitor( & $product ) {
	
		$monitor_type = $product['monitor_type'];
		$product["tag"] = ''.$product['userid'] . '_whmcs';
		$result = array( "status"=>'ok', "monitor_id"=>0, "monitor_type"=>$monitor_type );
		
		if( !empty($product['web_site']) ) {
			$settings = null;
			if( !empty( $product["settings"] ) ) {
				$settings = json_decode( $product['settings'], true );
			} else {
				$settings = MonitisConf::$settings[$monitor_type];
			}
		
			if($monitor_type == 'ping') {
				$resp = MonitisApiHelper::addWebPing( $product, $settings );
			} else {
				$settings['timeout'] = intval($settings['timeout'] / 1000);
				$resp = MonitisApiHelper::addDefaultWeb( $product, $settings );	
			}

//_logActivity("createMonitor  **** result = ". json_encode($resp));

			if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
				$monitor_id = $resp['data']['testId'];
				$result["monitor_id"] = $monitor_id;
				$resp = MonitisApi::getWidget( array('moduleType'=>'external','monitorId'=>$monitor_id) );
				if( $resp && $resp['data'] ) {

					$mon_monitor = $this->_monitorById($monitor_id);
					if( !$mon_monitor ) {
						$publicKey = $resp['data'];
						$values = array(
							'server_id' => $product['serverid'],
							'available' => $settings['available'],
							'product_id' => $product['productid'], 
							'type' => $product["producttype"], 
							'monitor_id' => $monitor_id,
							'monitor_type' => $monitor_type,
							'user_id' => $product['userid'],
							'order_id' => $product['orderid'],
							'ordernum' => $product['ordernum'],
							'publicKey' => $publicKey
						);
						insert_query('mod_monitis_product_monitor', $values);
						
						if( @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists' ) {
							$resp = MonitisApi::suspendExternal( $monitor_id );
						}
						$result["status"] = 'ok';
						$result["msg"] = 'Monitor successfully created';
					} else {
						$result["status"] = 'warning'; $result["msg"] = 'Monitor already exists.';
					}
					///////////////////////////////////////////////
					
				} else {
					$result["status"] = 'error'; $result["action"] = 'getWidget'; $result["msg"] = $resp['error'];
				}
			} else {
				$result["status"] = 'error'; $result["action"] = 'addMonitor';
				if( empty($resp['error'])) $result["msg"] = 'Unknown error';
				else $result["msg"] = $resp['error'];
			}
		} else {
			$result["status"] = 'error'; $result["action"] = 'addMonitor'; $result["msg"] = 'Domain and dedicated ip fields are empty';
		}
		$result["product"] = $product;
		return $result;
	}
	
/*
	$tpl = array(
		'tag'=>'',
		'serverid'=>'',
		'userid'=>'',
		'orderid'=>'',
		'ordernum'=>'',
		'producttype'=>'',
		'web_site'=>'',
		'monitor_type'=>'',
	);
*/	
	public function addonProductsList( $addonid ) {
		
		$services = $this->_activeServicesByAddonId( $addonid );
//_dump( $services );
		$adminuser = MonitisConf::getAdminName();
		$oUserProds = new monitisClientProducts();
		$arr = array();
		for( $i=0; $i<count($services); $i++) {

			$prdcts = $oUserProds->userProductsByServiceId( $services[$i]['userid'], $services[$i]['serviceid'] );
			$serviceStatus = $prdcts[0]['status'];
			if( $serviceStatus == 'Active') {
				$web_site = $this->url_IP( $services[$i], $type );
				$services[$i]['web_site'] = $web_site;
				$services[$i]['productid'] = $services[$i]['addonid'];
				$services[$i]['producttype'] = 'addon';
				$arr[] = $services[$i];
			}

		}
		return $arr;
	}
}

class monitisClientProducts {

	private $products = null;
	public function __construct () {}
	
	public function userProducts( $userid ) {
		$products = null;
		$adminuser = MonitisConf::getAdminName();
//echo "adminuser=$adminuser";
		$values = array( "clientid"=> $userid );
		$prdcts = localAPI( "getclientsproducts", $values, $adminuser );
//_dump($prdcts);
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
		}
		$this->products = $products;
		return $products; 
	}
	
	public function userProductsByServiceId( $userid, $serviceid ) {
		$products = null;
		$adminuser = MonitisConf::getAdminName();
		$values = array( "clientid"=> $userid, 'serviceid'=>$serviceid );
		$prdcts = localAPI( "getclientsproducts", $values, $adminuser );
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
		}
		$this->products = $products;
		return $products; 
	}
	
	public function productByField( $fieldname, $fieldvalue ) {
		for( $i=0; $i<count($this->products); $i++) {
			if( $this->products[$i][$fieldname] == $fieldvalue )
				return $this->products[$i];
		}
		return null;
	}
/*
	public function test_productByField( $fieldname, $fieldvalue ) {
	
		$arr = array();	
		for( $i=0; $i<count($this->products); $i++) {
			 
			if( $this->products[$i][$fieldname] == $fieldvalue )
				$arr[] = $this->products[$i];
		}
		
		return $arr;
	}
*/
}


function monitisAddonHookHandler( $vars, $hook) {
	$oSrvc = new clientServicesClass();
	$resp = $oSrvc->addonHookHandler( $vars, $hook );
	
	m_log( $resp, 'monitisAddonHookHandler', '_addon');
_logActivity("Addon Hook Handler: **** result = ". json_encode( $resp));
}

function monitisModuleHookHandler( $vars, $hook) {
	$oSrvc = new clientServicesClass();
	$resp = $oSrvc->moduleHookHandler( $vars, $hook );
	
//_dump($resp);	
	m_log( $resp, 'monitisModuleHookHandler', '_module');
_logActivity("Module Hook Handler: **** result = ". json_encode( $resp));
}

function monitisOrderHookHandler( $vars, $hook) {
	$oSrvc = new clientServicesClass();
	$resp = $oSrvc->orderHookHandler( $vars, $hook );
//_dump($resp);	
	m_log( $resp, 'monitisOrderHookHandler', '_order');
_logActivity("Order Hook Handler: **** result = ". json_encode( $resp));
}

function monitisEditHookHandler( $vars ) {
	$oSrvc = new clientServicesClass();
	$resp = $oSrvc->editHookHandler( $vars );
//_dump($resp);	
	m_log( $resp, 'monitisEditHookHandler', '_edit');
_logActivity("Admin Edit Hook Handler: **** result = ". json_encode( $resp));
}



?>
