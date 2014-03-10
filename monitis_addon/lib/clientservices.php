<?php

class MonitisSeviceHelper {

	static function productsByServiceId( $serviceid ) {
		$products = null;
		$adminuser = MonitisHelper::getAdminName();
		$values = array( 'serviceid'=>$serviceid );
		$prdcts = localAPI( "getclientsproducts", $values, $adminuser );
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
			if( $products && count($products) == 1 ) {
				return $products[0];	
			}
		}
		return null;
	}
	static function productsByOrderId( $orderid ) {
		$adminuser = MonitisHelper::getAdminName();
		$values = array("id"=> $orderid);
		$iOrder = localAPI( "getorders", $values, $adminuser);
		if( $iOrder && $iOrder['result'] == 'success') {
			return $iOrder['orders']['order'][0];
		}
		return null;
	}
	static function customfields($product) {
		
		$customfieldMonitis = monitisSqlHelper::query('SELECT * FROM mod_monitis_product WHERE product_id='.$product['pid']);
		$flds = array();
		if( $customfieldMonitis) {
			$customfieldMonitis = $customfieldMonitis[0];
			$customfield = $product['customfields']['customfield'];
			if( $customfield ) {
				for($i=0; $i<count($customfield); $i++){
					$field = $customfield[$i];
					if( $field['name'] == MONITIS_FIELD_WEBSITE ) { 
						$flds["web_site"] = $field['value'];
					}
					if( $field['name'] == MONITIS_FIELD_MONITOR) {
						$flds["monitor_type"] = $field['value'];
					}
				}
				$flds["settings"] = $customfieldMonitis['settings'];
			}
		}
		return $flds;
	}
	static function configoptions($product) {
		$optionForModul = array();
		$configoptions = $product['configoptions']['configoption'];

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
			$query = 'SELECT mod_monitis_options.settings, mod_monitis_options.type, mod_monitis_options.is_active, 
				tblproductconfigoptionssub.id
				FROM mod_monitis_options
					LEFT JOIN tblproductconfigoptionssub ON (tblproductconfigoptionssub.id = mod_monitis_options.option_id)
				WHERE tblproductconfigoptionssub.optionname = "'.$optionName.'" AND tblproductconfigoptionssub.configid = '.$option['id'].' AND mod_monitis_options.is_active>0 
				LIMIT 1';
			$result = mysql_query( $query );
			if($result) {
				$optionMonitis = mysql_fetch_assoc( $result );
				if( $optionMonitis ) {
					array_push( $optionForModul, array(
						'option_id' => $optionMonitis['id'],
						'monitor_type' => $optionMonitis['type'],
						'settings' => html_entity_decode( $optionMonitis['settings'] ),
						'web_site' => self::url_IP( $product, $optionMonitis['type'] ),
						'is_active' => $optionMonitis['is_active']
					) );
				}
			}
		}
		return $optionForModul;
	}
	static function url_IP( & $product, $monitotType ) {
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
	
	// CONCAT( firstname, " ", lastname) as username, 
	static function userById($userid) {
		$vals = monitisSqlHelper::objQuery('SELECT `id` as userid, firstname as firstName, lastname as lastName, `email`, `status` 
		FROM tblclients 
		WHERE id='.$userid);
		return $vals;
	}
	
	static function productMonitor($pid, $type, $userid, $serviceid) {
		$mons = monitisSqlHelper::query('SELECT * FROM mod_monitis_product_monitor WHERE type="'.$type.'" AND product_id='.$pid.' AND user_id='.$userid.' AND service_id='.$serviceid);
		if($mons) {
			$mon = $mons[0];
			$mon['api'] = self::apiMonitor($mon['monitor_id'], $userid);
			return $mon;
		} else return null;
	}
	
	static function apiMonitor($monitor_id, $userid) {
		$resp = monitisClientApi::getExternalMonitorInfo($monitor_id, $userid);
		if($resp && !isset($resp['error'])) {
			return array(
				'tag' => $resp['tag'],
				'url' => $resp['url'],
				'name' => $resp['name'],
				'isSuspended' => $resp['isSuspended']
			);
		}
		return null;
	}
	
	static function addonByAddonServiceId( $addonServiceId ) {
		
		$sql = 'SELECT addonid as productid, tblhosting.id as serviceid, tblhosting.userid, tblhosting.server as serverid, 
			CONCAT( tblclients.firstname, " ", tblclients.lastname) as username,
			tblclients.firstname as firstName, tblclients.lastname as lastName, tblclients.email,
			tblhosting.domain, tblhosting.dedicatedip, tblhosting.domainstatus as productstatus,
			tblhostingaddons.status, tblhostingaddons.id as addonserviceid,
			tblhosting.orderid,	tblorders.status as orderstatus, tbladdons.name as productname,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting ON (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon ON ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons ON (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblorders ON (tblorders.id = tblhosting.orderid ) 
			LEFT JOIN tblclients ON tblclients.id = tblhosting.userid
			
			WHERE tblhostingaddons.id='.$addonServiceId.' AND mod_monitis_addon.addon_id>0';
			
		return monitisSqlHelper::query($sql);
	}
	
	static function addonsServicesByServiceId( $serviceid, $userid ) {
		$sql = 'SELECT addonid as productid, tblhosting.id as serviceid, tblhosting.userid, tblhosting.server as serverid, 
			CONCAT( tblclients.firstname, " ", tblclients.lastname) as username,
			tblclients.firstname as firstName, tblclients.lastname as lastName, tblclients.email,
			tblhosting.domain, tblhosting.dedicatedip, tblhosting.domainstatus as productstatus,
			tblhostingaddons.status, tblhostingaddons.id as addonserviceid,
			tbladdons.name as productname,
			tblhosting.orderid,	tblorders.status as orderstatus,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhosting 
			LEFT JOIN tblhostingaddons ON (tblhosting.id = tblhostingaddons.hostingid) 
			LEFT JOIN mod_monitis_addon ON ( mod_monitis_addon.addon_id = tblhostingaddons.addonid) 
			LEFT JOIN tbladdons ON (tbladdons.id = tblhostingaddons.addonid)
			LEFT JOIN tblorders ON (tblorders.id = tblhosting.orderid) 
			LEFT JOIN tblclients ON tblclients.id = tblhosting.userid
			WHERE tblhosting.id='.$serviceid.' AND tblhosting.userid='.$userid.' AND mod_monitis_addon.addon_id>0';
		return monitisSqlHelper::query($sql);
	}
	
	// addon for created monitor from log
	static function addonByAddonServiceIdAlt( $addonServiceId, $serviceid, $userid ) {
		
		$sql = 'SELECT addonid as productid, tblhosting.id as serviceid, tblhosting.userid, tblhosting.server as serverid, 
			CONCAT( tblclients.firstname, " ", tblclients.lastname) as username, CONCAT("addon") as producttype,
			tblclients.firstname as firstName, tblclients.lastname as lastName, tblclients.email,
			tblhosting.domain, tblhosting.dedicatedip, tblhosting.domainstatus,
			tblhostingaddons.status, tblhostingaddons.id as addonserviceid,
			tblhosting.orderid,	tblorders.status as orderstatus, tbladdons.name as productname,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting ON (tblhosting.id = tblhostingaddons.hostingid ) 
			LEFT JOIN mod_monitis_addon ON ( mod_monitis_addon.addon_id = tblhostingaddons.addonid ) 
			LEFT JOIN tbladdons ON (tbladdons.id = tblhostingaddons.addonid ) 
			LEFT JOIN tblorders ON (tblorders.id = tblhosting.orderid ) 
			LEFT JOIN tblclients ON tblclients.id = tblhosting.userid
			WHERE tblhostingaddons.id='.$addonServiceId.' AND tblhostingaddons.hostingid='.$serviceid.' AND tblhosting.userid='. $userid;
		$addon = monitisSqlHelper::objQuery($sql);
		if($addon) {
			$addon['web_site'] = self::url_IP($addon, $addon['monitor_type'] );
		}
		return $addon;
	}
	
	// addons for auto created monitors
	static function addonProductsList( $addonid ) {
		$sql = 'SELECT tblhostingaddons.id as addonserviceid, tblhosting.id as serviceid,  addonid, tblhostingaddons.status, tbladdons.name,
			CONCAT( tblclients.firstname, " ", tblclients.lastname) as username, CONCAT("addon") as producttype,
			tblhosting.userid, tblclients.firstname as firstName, tblclients.lastname as lastName, tblclients.email,
			tblhosting.server as serverid, tblhosting.domain, tblhosting.dedicatedip, tblhosting.domainstatus,
			tblorders.id as orderid, tblorders.ordernum, tblorders.status as orderstatus,
			tblhosting.orderid as serviceorderid,
			mod_monitis_addon.type as monitor_type, mod_monitis_addon.settings
			FROM tblhostingaddons 
			LEFT JOIN tblhosting on (tblhosting.id = tblhostingaddons.hostingid) 
			LEFT JOIN mod_monitis_addon on ( mod_monitis_addon.addon_id = tblhostingaddons.addonid) 
			LEFT JOIN tbladdons on (tbladdons.id = tblhostingaddons.addonid) 
			LEFT JOIN tblorders on ( tblorders.id = tblhostingaddons.orderid) 
			LEFT JOIN tblclients on ( tblclients.id = tblhosting.userid ) 
			WHERE tblhostingaddons.addonid='.$addonid.' AND tblhostingaddons.status="Active" AND tblhosting.id>0 ORDER BY tblorders.id DESC';
			
		// WHERE tblhostingaddons.addonid='.$addonid.' AND tblorders.id>0 ORDER BY tblorders.id DESC';

		$services = monitisSqlHelper::query($sql);

		$arr = array();
		for( $i=0; $i<count($services); $i++) {
			$services[$i]['web_site'] = self::url_IP( $services[$i], $services[$i]['monitor_type'] );
			$services[$i]['productid'] = $services[$i]['addonid'];
			$arr[] = $services[$i];
		}
		return $arr;
	}
	
	static function configOptionProduct($optionid, $configid, $serviceid) {
		$sql = 'SELECT tblhostingconfigoptions.configid as productid, tblhostingconfigoptions.optionid as option_id, 
			mod_monitis_options.type as monitor_type, mod_monitis_options.settings,
			tblhosting.id as serviceid, tblhosting.userid, tblhosting.orderid, tblhosting.domain, tblhosting.dedicatedip, tblhosting.domainstatus,
			tblhosting.server as serverid, tblorders.ordernum, tblorders.status,
			tblclients.firstname as firstName, tblclients.lastname as lastName, tblclients.email,
			CONCAT( tblclients.firstname, " ", tblclients.lastname) as username,
			CONCAT("option") as producttype
		FROM tblhostingconfigoptions
			RIGHT JOIN mod_monitis_options
				ON mod_monitis_options.option_id = tblhostingconfigoptions.optionid
			LEFT JOIN tblhosting
				ON tblhosting.id = tblhostingconfigoptions.relid
			LEFT JOIN tblorders
				ON tblorders.id = tblhosting.orderid
			LEFT JOIN tblclients
				ON tblclients.id = tblhosting.userid
		WHERE tblhostingconfigoptions.optionid='.$optionid.' AND tblhostingconfigoptions.configid='.$configid.' AND tblhostingconfigoptions.relid='.$serviceid;
		$product = monitisSqlHelper::objQuery($sql);
		if($product) {
			$product['web_site'] = self::url_IP($product, $product['monitor_type'] );
		}
		return $product;
		
	}
	///////////////////////////////////////////////////////////////
	static function linkMonitor($monitor_id, & $product, $type='external') {
		
		$linked = monitisSqlHelper::query('SELECT * FROM mod_monitis_product_monitor WHERE monitor_id='.$monitor_id);
		if(!$linked) {
			$resp = monitisClientApi::getWidget( array('moduleType'=>$type,'monitorId'=>$monitor_id), $product['userid'] );
			if( $resp && $resp['data'] ) {
				$publicKey = $resp['data'];
				
				$values = array(
					'monitor_id' => $monitor_id,
					'server_id' => $product['serverid'],
					'service_id' => $product['serviceid'],
					'product_id' => $product['productid'],
					'option_id' => $product['option_id'],							
					'type' => $product["producttype"], 
					'monitor_type' => $product['monitor_type'],
					'user_id' => $product['userid'],
					'order_id' => $product['orderid'],
					'publicKey' => $publicKey
				);
				insert_query('mod_monitis_product_monitor', $values);
				return array('status'=>'ok', 'msg'=>'Monitor successfully created');
			}
			return array('status'=>'error', 'msg'=>'getWidget action error, monitor is not link');
		} else {
			return array('status'=>'warning', 'msg'=>'Monitor already exists');
		}
		
	}
	
	static function createMonitor( & $product ) {
		$monitor_type = $product['monitor_type'];
		$userid = $product['userid'];
		$product["tag"] = ''.$userid . '_whmcs';
		$result = array("status"=>'ok', "monitor_id"=>0, "monitor_type"=>$monitor_type);

monitisLog($product, 'createMonitor UserID='.$userid);
		if( !empty($product['web_site']) ) {
			$settings = null;
			if( !empty( $product["settings"] ) ) {
				$settings = json_decode( $product['settings'], true );
			} else {
				$settings = MonitisConf::$settings[$monitor_type];
			}

			// get client api_key and secret_key,  if the client does not exist to create
			$resp = monitisClientApi::userApiInfoById($product['userid']);
			if($resp['status'] == 'ok' && isset($resp['data']) && $resp['data']) {
			
			//$resp = monitisClientApi::userToken($userid);
			//if($resp['status'] == 'ok') {
			//	$userInfo = $resp;
				$response = null;
				$userInfo = $resp['data'];
				
			
				if($product['producttype'] == 'product' && $monitor_type == 'ping' && isset($settings['timeoutPing'])) {
					$settings['timeout'] = $settings['timeoutPing'];
				}

				$response = monitisClientApi::addExternalMonitor( $product, $settings, $userInfo );

				if (@$response['status'] == 'ok' || (isset($response['error']) && @$response['errorCode'] == 11)) {
					$monitor_id = $response['data']['testId'];
					$result["monitor_id"] = $monitor_id;
					$resp = self::linkMonitor($monitor_id, $product, 'external');
					$result['status'] = $resp['status'];
					$result['msg'] = $resp['msg'];
					
					// activate monitor
					$mon = array('user_id'=>$userid, 'monitor_id'=>$monitor_id);
					monitisClientApi::activateExternal($mon);
				} else {
					$result["status"] = 'error'; 
					if( empty($response['error'])) 
						$result["msg"] = 'Unknown error / Monitis api server problem';
					else $result["msg"] = $response['error'];
				}
			} else {
				$result["status"] = 'error'; 
				$result["msg"] = $resp['msg'];
			}
		} else {
			$result["status"] = 'error'; 
			$result["msg"] = 'Domain and dedicated IP fields are empty';
		}
		return $result;
	}
	/////////////////////////////////////////////////////////////// for client class
	static function userProducts( $userid ) {
		$products = null;
		$adminuser = MonitisHelper::getAdminName();

		$values = array( "clientid"=> $userid );
		$prdcts = localAPI( "getclientsproducts", $values, $adminuser );
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
		}
		return $products; 
	}
	
}	


class MonitisPredefinedProduct {
	
	public $username = '';
	public function __construct () {}
	/*
	 * products By Order
	 *
	*/
	public function productsByOrderId($orderid) {

		$order = MonitisSeviceHelper::productsByOrderId($orderid);
		
		$result = array('status'=>'error','msg'=>'no products','orderid'=>$orderid,'serviceid'=>0, 'productstatus'=>'','orderstatus'=>$order['status'],'products'=>null);
		
		if($order) {
			$lineitem = $order['lineitems']['lineitem'];
			$orderid = $order['id'];

			$userid = $order['userid'];
			$user = MonitisSeviceHelper::userById($userid);
			
			$allProducts = array();
			$arr = array();
			if($lineitem) {
			
				// all order monitors
				// products
				for( $i=0; $i<count($lineitem); $i++) {
					$item = $lineitem[$i];

					if($item['type'] == 'product') {
						$serviceid = $item['relid'];
						$product = MonitisSeviceHelper::productsByServiceId( $item['relid'] );
						
						$customfields = MonitisSeviceHelper::customfields($product);
						$configoptions = MonitisSeviceHelper::configoptions($product);
						if($product) {
						
							$allProducts[] = $product;
							$result['productstatus'] = $product['status'];
							if(	count($customfields) > 0 || count($configoptions) > 0) {

								$pid = $product['pid'];
								$this->username = $order['name'];
								$serviceid = $product['id'];
								$result['serviceid'] = $serviceid;
								$data = array(
									'orderid' => $product['orderid'],
									'userid' => $userid,
									'firstName' => $user['firstName'],
									'lastName' => $user['lastName'],
									'email' => $user['email'],
									'serviceid' => $serviceid,
									'serverid' => $product['serverid'],
									'productid' => $pid,
									'domain' => $product['domain'],
									'dedicatedip' => $product['dedicatedip'],
									'status' => $product['status'],
									'productstatus' => $product['status'],
									'productname' => $product['groupname'] .' - ' . $product['name']
								);
								
								if(count($configoptions)>0) {
									for($j=0; $j<count($configoptions); $j++) {
										$data['producttype'] = 'option';
										foreach($configoptions[$j] as $key=>$val)
											$data[$key] = $val;
										$data['monitor'] = MonitisSeviceHelper::productMonitor($pid, 'option', $userid, $serviceid );
										$arr[] = $data;
									}
								}
								if( count($customfields)>0 ) {
									$data['option_id'] = 0;
									$data['producttype'] = 'product';
									foreach($customfields as $key=>$val)
										$data[$key] = $val;
									$data['monitor'] = MonitisSeviceHelper::productMonitor($pid, 'product', $userid, $serviceid );
									$arr[] = $data;
								}
							}
						}

					} elseif($item['type'] == 'addon') {		// addons
						$addonserviceid = $item['relid'];
						$addons = MonitisSeviceHelper::addonByAddonServiceId( $addonserviceid );
						if($addons) {
							for($j=0; $j<count($addons); $j++) {
								$addon = $addons[$j];
								$addon["firstName"] =  $user['firstName'];
								$addon["lastName"] =  $user['lastName'];
								$addon["email"] =  $user['email'];
								
								$addon["option_id"] = 0;
								$addon["web_site"] = MonitisSeviceHelper::url_IP( $addon, $addon['monitor_type'] );
								$addon["option_id"] = 0;
								$addon["producttype"] = 'addon';
								$addon['monitor'] = MonitisSeviceHelper::productMonitor($addon["productid"], 'addon', $addon["userid"], $addon["serviceid"] );
								$arr[] = $addon;
							}
						}
					}
				}
			}
			if(count($arr) > 0) {
				$result['status'] = 'ok';
				$result['msg'] = 'success';
				$result['products'] = $arr;
			}
		}
		return $result;
	}
	
	/*
	 * products By service
	 *
	*/
	public function seviceProductsById($serviceid, $user){
		$userid = $user['userid'];
		$result = array('status'=>'error', 'msg'=>'service has no products', 'productstatus'=>'', 'serviceid'=>$serviceid, 'userid'=>$userid);
		$arr = array();
		$product = MonitisSeviceHelper::productsByServiceId($serviceid);

		//$orderid = 0;
		if($product) {
			$orderid = $product['orderid'];
			
			$customfields = MonitisSeviceHelper::customfields($product);
			$configoptions = MonitisSeviceHelper::configoptions($product);
			$result['productstatus'] = $product['status'];

			$pid = $product['pid'];
			if($configoptions || $customfields) {
				$data = array(
					'orderid' => $product['orderid'],
					'userid' => $product['clientid'],
					
					'firstName'=> $user['firstName'],
					'lastName'=> $user['lastName'],
					'email'=> $user['email'],
					
					'serviceid' => $serviceid,
					'serverid' => $product['serverid'],
					'productid' => $pid,
					'domain' => $product['domain'],
					'dedicatedip' => $product['dedicatedip'],
					'status' => $product['status'],
					'productname' => $product['groupname'] .' - ' . $product['name']
				);
				if(count($configoptions)>0) {
					for($j=0; $j<count($configoptions); $j++) {
						$data['producttype'] = 'option';
						foreach($configoptions[$j] as $key=>$val)
							$data[$key] = $val;
						$data['monitor'] = MonitisSeviceHelper::productMonitor($pid, 'product', $userid, $serviceid );
						$arr[] = $data;
					}
				}
				if(count($customfields)>0) {
					$data['producttype'] = 'product';
					$data['option_id'] = 0;
					foreach($customfields as $key=>$val)
						$data[$key] = $val;
					$data['monitor'] = MonitisSeviceHelper::productMonitor($pid, 'product', $userid, $serviceid );
					$arr[] = $data;
				}
			} 
		} 
		
		$addons = MonitisSeviceHelper::addonsServicesByServiceId($serviceid, $userid);

		if($addons) {
			for($i=0; $i<count($addons); $i++) {
				$addon = $addons[$i];
				$addon["option_id"] = 0;
				$addon["web_site"] = MonitisSeviceHelper::url_IP( $addon, $addon['monitor_type'] );
				$addon["option_id"] = 0;
				$addon["producttype"] = 'addon';
				$addon['monitor'] = MonitisSeviceHelper::productMonitor($addon["productid"], 'addon', $addon["userid"], $addon["serviceid"] );
				$arr[] = $addon;
			}
		}
		if(count($arr) > 0) {
			$result['status'] = 'ok';
			$result['msg'] = 'success';
			$result['products'] = $arr;
		}
		return $result;
	}
}

class MonitisHookClass {

	private $actionBehavior = null;
	private $orderActive = false;
	
	public function __construct () {}
	
	// addon
	public function addonHookHandler(& $vars, $hook) {

		$addonserviceid = $vars['id'];
		$result = array('status'=>'nomonitis', 'addonserviceid'=>$addonserviceid);

		$addonService = MonitisSeviceHelper::addonByAddonServiceId($addonserviceid);
		$result['hook'] = $hook;
		$result['hook_type'] = 'addon';
		if($addonService && count($addonService) > 0) {
			$action = MonitisConf::$settings['order_behavior'][$hook];
			$result['action'] = $action; 
			
			if($action != 'noaction') {
			
				$addon = $addonService[0];
				$serviceid = $addon['serviceid'];
				$addonid = $addon['productid'];
				$userid = $addon['userid'];
				
				$result['status'] = 'ok';
				$result['title'] ='Addon service:'.$serviceid.'/'.$addonserviceid.'/'.$addonid;
				$result['serviceid'] = $serviceid;
				$result['addonid'] = $addonid;
				$result['userid'] = $userid;
				$result['username'] = $addon['username'];
				$result['service_url'] = '?userid='.$userid.'&id='.$serviceid.'&aid='.$addonserviceid;
				
				$addonService[0]["option_id"] = 0;
				$addonService[0]["producttype"] = 'addon';
				$addonService[0]["web_site"] = MonitisSeviceHelper::url_IP($addon, $addon['monitor_type']);
				$addonService[0]['monitor'] = MonitisSeviceHelper::productMonitor($addonid, 'addon', $userid, $serviceid);
				$products = array(
					'status' => 'ok',
					'products' => $addonService
				);
				$result['data'] = $this->toDo($products, $action, 'addon');
			}
		} 
		return $result;
	}
	
	// order
	public function orderHookHandler($vars, $hook) {
		$orderid = $vars['orderid'];
		$result = array( 'status'=>'nomonitis', 'title'=>'Order: '.$orderid, 'orderid'=>$orderid, 'hook_type'=>'order');
		
		$action = MonitisConf::$settings['order_behavior'][$hook];
		if($action != 'noaction') {
			if($action == 'active' || $action == 'create') 
				$this->orderActive = true;
				
			$result['hook_type'] = 'order';
			$result['service_url'] = '?action=view&id='.$orderid;
			$result['hook'] = $hook;
			$result['action'] = $action;

			$oPre = new MonitisPredefinedProduct();
			$products = $oPre->productsByOrderId( $orderid );

			if($products) {
				$result['username'] = $oPre->username;
				$result['status'] = 'ok';
				$result['data'] = $this->toDo($products, $action, 'order');
			} 
		}
		return $result;
	}
	
	// module
	public function moduleHookHandler(& $vars, $hook) {
		$serviceid = $vars['serviceid'];
		$userid = $vars['userid'];
		
		$result = array('status'=>'error', 'serviceid'=>$serviceid, 'userid'=>$userid, 'username'=>'', 'title'=>'Service: '.$serviceid, 'hook_type'=>'');
		
		$action = MonitisConf::$settings['order_behavior'][$hook];
		
		$result['hook_type'] = 'module'; 
		$result['service_url'] = '?userid='.$userid.'&id='.$serviceid;
		
		$user = MonitisSeviceHelper::userById($userid);
		$result['username'] = $user['firstName'].' '.$user['lastName'];
		if($action != 'noaction') {
			$result['action'] = $action; 
			$result['hook'] = $hook; 
			$oPre = new MonitisPredefinedProduct();
			$products = $oPre->seviceProductsById($serviceid, $user);
			if($products) {
				$result['status'] = 'ok';
				$result['data'] = $this->toDo($products, $action, 'module');
			} 
		}
		return $result;
	}
	
	// edit
	public function editHookHandler($vars, $hook) {
		$serviceid = $vars['serviceid'];
		$userid = $vars['userid'];
		
		$result = array('status'=>'nomonitis', 'serviceid'=>$serviceid, 'userid'=>$userid, 'title'=>'Service: '.$serviceid, 'hook_type'=>'');

		if( isset($serviceid) && isset($userid) ) {
		
			if( empty($hook)) {
				$rslt = monitisSqlHelper::query('SELECT tblhosting.domainstatus as status FROM tblhosting WHERE tblhosting.id='.$serviceid);
				$hook = strtolower($rslt[0]['status']);
				if($hook == 'cancelled')
					$hook = 'cancel';
			}
			
			$result = $this->moduleHookHandler($vars, $hook );
			$result['hook_type'] = 'edit';
		}
		return $result;
	}
	
	private function addonStatus( $prdct ) {
	
		if($prdct['producttype'] == 'addon' && (($prdct['status'] == 'Active' && $prdct['productstatus'] == 'Active') || $this->orderActive) )
			return true;
		else
			return false;
	}
	
	private function prdctStatus( & $data, $prdct ) {
	
		if(
			($prdct['producttype'] == 'addon' && $prdct['status'] == 'Active' && ($data['productstatus'] == 'Active' || $this->orderActive ) ) || 
			(($prdct['producttype'] == 'product' || $prdct['producttype'] == 'option') && ($prdct['status'] == 'Active' || $this->orderActive) ) )
			return true;
		else
		return false;
	}
	
	private function toDo( & $data, $action, $hookType='' ) {
	
		if(isset($data['products']) && count($data['products']) > 0) {
		
			$result = array('status'=>'ok', 'msg'=>'');
			$products = $data['products'];
			
			$arr = array();
			for($i=0; $i<count($products); $i++) {
			

				$prdct = $products[$i];
				$mon = $prdct['monitor'];
				$api = null;
				if($mon) 
					$api = $mon['api'];
					
				$rslt = null;
				switch( $action ) {
				
					case 'active':
					case 'create':
						$status = '';
						if($prdct['producttype'] == 'addon')
							$status = $this->addonStatus($prdct);
						else 
							$status = $this->prdctStatus($data, $prdct);
						if($status) {
							if($mon) {
								if($api) {
									if($api['isSuspended']) {
										$resp = monitisClientApi::activateExternal($mon);
										if( !$resp['error']) {
											$rslt = array('status'=>'ok', 'msg'=>'Monitor successfully activated');
										} else {
											$rslt = array('status'=>'warning', 'msg'=>$resp['error']);
										}
									} else {
										// edit
										$rslt = array('status'=>'warning', 'msg'=>'Monitor is exist and active');
									}
								} else {
									// link
									$rslt = MonitisSeviceHelper::createMonitor($prdct);
								}
							
							} else {
								$rslt = MonitisSeviceHelper::createMonitor($prdct);
							}

						} else {
							$rslt = array('status'=>'error', 'msg'=>'Product status is inactive');
						}
					break;
					case 'unlink':
						if($mon) { 
							$resp = monitisWhmcsServer::unlinkProductMonitorById( $mon['monitor_id'] );
							$rslt = array('status'=>'ok', 'msg'=>'Monitor unlinked successfully');
						} else {
							$rslt = array('status'=>'warning', 'msg'=>'Monitor is not linked');
						}
					break;
					case 'delete':
						if($mon) { 
							$resp = monitisClientApi::deleteExternalMonitor($mon);
							if( $resp['status'] == 'ok') {
								$rslt = array('status'=>'ok', 'msg'=>'Monitor deleted successfully');
							} else { 
								$rslt = array('status'=>'error', 'msg'=>$resp['error']); 
							}
						} else {
							$rslt = array('status'=>'warning', 'msg'=>'Monitor is not linked');
						}
					break;
					case 'suspended':
						if($mon ) {
							$resp = monitisClientApi::suspendExternal($mon);
							if($resp['status'] == 'ok') {
								if(@$resp['data']) 
									$rslt = array('status'=>'ok', 'msg'=>$resp['data']);
								else
									$rslt = array('status'=>'ok', 'msg'=>'The monitor suspended successfully');
							} else { 
								$rslt = array('status'=>'error', 'msg'=>'Error suspended'); 
							}
						} else {
							$rslt = array('status'=>'warning', 'msg'=>'Monitor is not linked');
						}
					break;
				}
				
				$arr[] = array('product'=>$products[$i], 'response'=>$rslt);

			}
			return $arr;
		} else {
			return null;
		}
	}

	public function keepRespose($resp, $vars) {
	
		if($resp && $resp['status'] == 'ok') {
			if($resp['data']) {
			
				$data = $resp['data'];
				
				$is_error = false;
				for($i=0; $i<count($data); $i++){
					$status = $data[$i]['response']['status'];
					if($status == 'error') {
						$is_error = true;
						break;
					}
				}
				
				if($is_error) {
					// build service URL
					$hookType = $resp['hook_type'];

					if($hookType == 'multiple') {
						$resp['serviceurl'] = $resp['service_url'];
					} else {
						$arr = explode('?', $_SERVER['HTTP_REFERER']);
						$resp['serviceurl'] = $arr[0].$resp['service_url'];
					}
				
					$vars_json = '';
					if($vars)
						$vars_json = json_encode($vars);
					$values = array(
						'json' => json_encode($resp),
						'vars' => $vars_json,
						'date' => date("Y-m-d H:i:s", time())
					);
					insert_query(MONITIS_HOOK_REPORT_TABLE, $values);
//m_log( $resp, '*** resp', 'keepRespose');
				}
			} 
		}
	}
	
	static function createCreateConfigOptionMonitor($product) {
	
		$result = array(
			'status' => 'ok',
			'title' => 'Service '.$product['serviceid'],
			'hook_type' => 'multiple',
			'multi_type' => 'option',
			'username' => $product['username'],
			'service_url' => MonitisHelper::adminServicerUrl($product['userid'], $product['serviceid'])
		);
		
		$response = array('status'=>'error', 'monitor_type'=>$product['monitor_type']);
		
		if( $product['domainstatus'] != 'Active' ){
			$response['msg'] = 'Domain status: '.$product['domainstatus'];
			$response['status'] = 'info';
		}
		elseif( $product['status'] != 'Active' ){
			$response['msg'] = 'Order status: '.$product['status'];
			$response['status'] = 'info';
		} else{
			$response = MonitisSeviceHelper::createMonitor( $product );
		}

		$result['data'] = array(array('product'=>$product, 'response'=>$response));
		if($response['status'] == 'error' || $response['status'] == 'info') {
			MonitisHookClass::keepRespose($result, null);
		}
		return $result;
	}
	
	public function applyCreateConfigOptionMonitor ($optionid, $configid, $serviceid) {
		$product = MonitisSeviceHelper::configOptionProduct($optionid, $configid, $serviceid);
		$result = array();
		if($product) {
			$result = self::createCreateConfigOptionMonitor($product);
		}
		return $result;
	}
	
	public function applyCreateAddonMonitor ($addonServiceId, $serviceid, $userid) {
		$addon = MonitisSeviceHelper::addonByAddonServiceIdAlt($addonServiceId, $serviceid, $userid);
		$result = array();
		if($addon) {
			$result = self::createAddonsMonitorByProduct ($addon);
		}
		return $result;
	}
	
	//  create addon by product
	public function createAddonsMonitorByProduct($product) {
		$result = array(
			'status'=> 'ok',
			'title' => 'Service: '.$product['serviceid'].'/'.$product['addonserviceid'],
			'hook_type' => 'multiple',
			'multi_type' => 'addon',
			'name' => $product['name'],
			'username' => $product['username'],
			'service_url' => MonitisHelper::adminServicerUrl($product['userid'], $product['serviceid']),
			'order_url' => MonitisHelper::adminOrderUrl($product["orderid"])
		);

		$response = array('status'=>'error', 'monitor_type'=>$product['monitor_type'] );
		if( $product['domainstatus']=='Active' && $product['status']=='Active' ) {
			$response = MonitisSeviceHelper::createMonitor($product);
		} elseif( $product['domainstatus']!='Active' || $product['status']!='Active' ) {
			$response['msg'] = 'Service: '.$product['domainstatus'].'; Addon: '.$product['status'];
		}
		$result['data'] = array(array('product'=>$product, 'response'=>$response));
		if($response['status'] == 'error') {
			MonitisHookClass::keepRespose($result, null);
		}
		return $result;
	}
	
	//  multi create addons
	public function createAddonsMonitorById ($addonId) {
		$products = MonitisSeviceHelper::addonProductsList($addonId);
		$result = array();
		if( $products && count($products) > 0) {
			for($i=0; $i<count($products); $i++) {
				$result[] = self::createAddonsMonitorByProduct ($products[$i]);
			}
		}
		return $result;
	}
}


function monitisOrderHookHandler(& $vars, $hook) {
	$oSrvc = new MonitisHookClass();
	$resp = $oSrvc->orderHookHandler( $vars, $hook );
	$oSrvc->keepRespose($resp, $vars);
}

function monitisCreateModuleCommandHandler( & $vars) {

	if( isset($_REQUEST['modop']) && $_REQUEST['modop'] == 'create' ){	// Module Commands

		$props = array(
		  'userid' => $vars['params']['clientsdetails']['userid'],
		  'serviceid' => $vars['params']['serviceid']
		);
		monitisModuleHookHandlerAlt($props, 'active');
	}
}


function monitisModuleHookHandler(& $vars, $hook) {

	if(isset($_REQUEST['modop'])) {
		$props = array (
		  'userid' => $vars['params']['clientsdetails']['userid'],
		  'serviceid' => $vars['params']['serviceid']
		);
		monitisModuleHookHandlerAlt($props, $hook);
	}
}

function monitisModuleHookHandlerAlt(& $vars, $hook) {
		$oSrvc = new MonitisHookClass();
		$resp = $oSrvc->moduleHookHandler($vars, $hook);
		$oSrvc->keepRespose($resp, $vars);
}

function monitisAddonHookHandler(& $vars, $hook) {

	$oSrvc = new MonitisHookClass();
	$vars['addonserviceid'] = $vars['id'];
	$resp = $oSrvc->addonHookHandler($vars, $hook);
	$oSrvc->keepRespose($resp, $vars);
}

function monitisEditHookHandler(& $vars, $hook) {

	$oSrvc = new MonitisHookClass();
	$resp = $oSrvc->editHookHandler($vars, $hook );
	$oSrvc->keepRespose($resp, $vars);
}

?>