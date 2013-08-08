<?
class servicesClass extends WHMCS_product_db {

	private $aOrders = null;
	private $adminuser = '';
	public $productname = '';
	public function __construct () {}

	public function createMonitor( & $product ) {
		
		$monitor = null;
		$monitor_type = $product['monitor_type'];
		$product["tag"] = $product['user_id'] . '_whmcs';
		$result = array(
			"status" => 'ok',
			"monitor_type" => $monitor_type
		);
		
		if( !empty($product['web_site']) ) {
		
			//$settings = $this->productSettings( $product["product_type"], $product['pid'], $monitor_type );
			$settings = null;
			if( !empty( $product["settings"] ) ) {
				$settings = json_decode( $product['settings'], true );
				if( $monitor_type != 'ping')
					$settings['timeout'] = intval( $settings['timeout'] / 1000 );
			} else {
				$settings = MonitisConf::$settings[$monitor_type];
				$settings['available'] = MonitisConf::$settings['available'];
			}
			
			if($monitor_type == 'ping') {
				$resp = MonitisApiHelper::addWebPing( $product, $settings );
			} else {
				$resp = MonitisApiHelper::addDefaultWeb( $product, $settings );
			}

			if (@$resp['status'] == 'ok' || @$resp['error'] == 'monitorUrlExists' || @$resp['error'] == 'Already exists') {
				//return $resp['data']['testId'];
				$monitor = $resp['data'];
			//if( $monitor ) {
				$monitor_id = $monitor['testId'];
				$result["monitor_id"] = $monitor_id;
				$type = 'external';
				$params = array('moduleType'=>$type,'monitorId'=>$monitor_id);
				$resp = MonitisApi::getWidget($params);
				//if( $resp && !$resp['error'] ) {
				if( $resp && $resp['data'] ) {
					$mon_monitor = $this->productMonitorById($monitor_id);

					if( !$mon_monitor ) {
						$publicKey = $resp['data'];
						$values = array(
							'server_id' => $product['serverid'],
							'available' => $settings['available'],
							'product_id' => $product['pid'], 
							'type' => $product["product_type"], 
							'monitor_id' => $monitor_id,
							'monitor_type' => $monitor_type,
							'user_id' => $product['user_id'],
							'orderid' => $product['orderid'],
							'ordernum' => $product['ordernum'],
							'publicKey' => $publicKey
						);
						insert_query('mod_monitis_product_monitor', $values);
						//MonitisApiHelper::addServerAvailable( $product['serverid'] );
						
						$result["status"] = 'ok';
						$result["msg"] = 'Monitor successfully created';
					} else {
						$result["status"] = 'warning';
						$result["msg"] = 'Monitor already exists.';
					}
				} else {
					$result["status"] = 'error';
					$result["action"] = 'getWidget';
					$result["msg"] = $resp['error'];
				}
			} else {
				$result["status"] = 'error';
				$result["action"] = 'addMonitor';
				if( empty($resp['error']))	
				$result["msg"] = 'Unknown error';
					else
				$result["msg"] = $resp['error'];
			}
		} else {
			$result["status"] = 'error';
			$result["action"] = 'addMonitor';
			$result["msg"] = 'Domain and dedicated ip fields are empty';
		}
		$result["product"] = $product;
		return $result;
	}
	
	public function deactiveMonitorByOrder( $orderid ) {
		$this->_deactiveMonitorByOrder($orderid);
	}
	
	public function product_by_order( $orderid, $iOrder, $adminuser ) {

		$info = array();
		if( $iOrder && $iOrder['result'] == 'success') {
			$info['orderid'] = $orderid;
			$ord = $iOrder['orders']['order'];
			$order = $ord[0];

			if( $order ) {

				 $info['ordernum'] = $order["ordernum"];
				 $info['user_id'] = $order["userid"];
				 $items = $order['lineitems']['lineitem'];
//_dump($items);
				 for( $i=0; $i<count($items); $i++ ) {
					$item = $items[$i];
					$order_type = $item['type'];	// addon / product
					$producttype = $item['producttype'];	// Addon / "Other Product/Service
					
					//if( $order_type == 'addon' && $item['status'] == 'Active' ) {
					if( $order_type == 'addon' ) {
			
						$service = $this->addonService($orderid);
//_dump($service);
						$addonid = $service['addonid'];
						$monitor = $this->addonById($addonid);
						if( $monitor ) {
							$type = $monitor['type'];
							$info['monitor_type'] = $type;
							$info['settings'] = $monitor['settings'];

							$info['serverid'] = $service['serverid'];
							$info['pid'] = $addonid;
							$info['product_type'] = 'addon';

_logActivity("product_by_order: addon **** orderid = $orderid  addonid -- $addonid");
							if( $type == 'ping'){
								if( !empty( $service['dedicatedip'] )) {
									$info["web_site"] = $service['dedicatedip'];
								} elseif( !empty( $service['domain'] ) ) {
									$info["web_site"] = $service['domain'];
								} else {
									return null;
								}
							} else {
								if( !empty( $service['domain'] ) ) {
									$info["web_site"] = $service['domain'];
								} elseif(!empty( $service['dedicatedip'] )) {
									$info["web_site"] = $service['dedicatedip'];
								} else {
									return null;
								}
							}
/*
							if( $type == 'ping' && !empty( $service['dedicatedip'] ) ) {
								$info["web_site"] = $service['dedicatedip'];
							} elseif ( !empty( $service['domain'] ) ) {
								$info["web_site"] = $service['domain'];
							} else {
								return null;
							}
*/
							return $info;
						}

					//} else if( $order_type == 'product' && $item['status'] == 'Active'  ) {
					} else if( $order_type == 'product' ) {
						$serviceid = $item['relid'];

						$values = array( "serviceid"=> $serviceid, "clientid" => $info['user_id'] );
						
						$prdcts = localAPI( "getclientsproducts", $values, $adminuser );

						if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
							$products = $prdcts['products']['product'];

							$product = $products[0];
							$pid = $product['pid'];

							if( !$pid ) return null;
							$monitor = $this->productById( $pid );
_logActivity("product_by_order: product **** orderid = $orderid  pid -- $pid");
							if( $monitor ) {
								$info['settings'] = $monitor['settings'];
								$flds = $this->productFields( $pid );
								$monitor_types = '';
								if( $flds ) {
									$website_field_id = $monitorType_id = 0;
									for($j=0; $j<count($flds); $j++){
										if( $flds[$j]['fieldname'] == MONITIS_FIELD_WEBSITE ) { 
											$website_field_id = $flds[$j]['id'];
											$info["web_site"] = $flds[$j]['value'];
										}
										if( $flds[$j]['fieldname'] == MONITIS_FIELD_MONITOR) {
											$monitor_types = $flds[$j]['fieldoptions'];
											$monitorType_id = $flds[$j]['id'];
											$info["monitor_type"] = $flds[$j]['value'];
										}
									}
									if( $website_field_id > 0 && $monitorType_id > 0 ) {
										$info["pid"] = $pid;
										$info["serverid"] = $product['serverid'];
										$info["monitor_types"] = $monitor_types;
										$info["product_type"] = 'product';
										return $info;
								
									} 
								} 

							}
						}
					}					
					
				 }
			}
		}  
		return null;

	}
	
	//////////////////////////////////// Automate create addon monitors
	public function _ordersList( $addon_id,  $start, $limit ) {
		
		// limitstart - The record number to start at (default = 0)
		// limitnum - The number of order records to return (default = 25)
		$totalresults = 0;
		$arr = array();

		$values = array( 
			"status"=>"Active", 
			"limitstart"=> $start,
			"limitnum"=> $limit);		// status: Pending, Active, Fraud, Cancelled
		$orders = localAPI( "getorders", $values, $this->adminuser);
//_dump( $orders );
		if( $orders['result'] == 'success' ) {
			$totalresults = $orders['totalresults'];
			$count = $orders['numreturned'];
			$ords = $orders['orders']['order'];
			if( $ords && count($ords) > 0 ) {
				for($i=0; $i<count($ords); $i++) {

					$orderid = $ords[$i]['id'];
					$ordernum = $ords[$i]['ordernum'];
					$lineitem = $ords[$i]['lineitems']['lineitem'];

					for($j=0; $j<count($lineitem); $j++) {
						$item = $lineitem[$j];
						
						if($item['type'] == 'addon') {
							$this->aOrders[] = array(
								'client' => $ords[$i]['name'],
								//'producttype' => $item['producttype'],
								'productname' => $item['product'],
								'orderid' =>$orderid,
								'ordernum' =>$ordernum,
								'user_id' => $ords[$i]['userid'],
								'hostingaddonid'=>$item['relid']
							);
						}
						
					}
				}

				if( $start+$limit < $totalresults ) {
					$this->_ordersList( $addon_id,  $start+$limit, $limit );
				} else {
					return;
				}
			}
		}
	}
	private function hostingaddonids() {
		$arr = array();
		for($i=0; $i<count($this->aOrders); $i++) {
			$arr[] = $this->aOrders[$i]['hostingaddonid'];
		}
		return $arr;
	}
	private function setWhmcsInfo( $addonid, & $arr ) {
		for($i=0; $i<count($arr); $i++) {
			if( $arr[$i]['addon_id'] == $addonid )
				return $arr[$i];
		}
		return null;
	}
	private function setHostingInfo( $hostingaddonid, & $arr ) {
		for($i=0; $i<count($arr); $i++) {
			if( $arr[$i]['hostingaddonid'] == $hostingaddonid )
				return $arr[$i];
		}
		return null;
	}
	
	public function addonOrdersList( $addon_id, $adminuser ) {
		
		$this->adminuser = $adminuser;
		
		$this->aOrders = array();
		$this->_ordersList( $addon_id, 0, 25 );

		$ids = $this->hostingaddonids();
		
		$idsStr = implode(',', $ids);
		$arr = $this->addonServicesByids($idsStr);
		
		//$whmcs_addons = $this->whmcsAddons();
		$mon = $this->addonById($addon_id);

		$all = array();
		for($i=0; $i<count($this->aOrders); $i++) {
			$itm = $this->aOrders[$i];

			$service = $this->setHostingInfo( $this->aOrders[$i]['hostingaddonid'], $arr);
//_dump( $service );
			//$this->aOrders[$i]['service'] = $service;
			//if( $service && $service['serverid'] > 0 ) {
			if( $service && ($addon_id > 0 && $addon_id == $service['addonid']) || $addon_id == 0 ) {	
				$addonid = $service['addonid'];

				//$mon = $this->setWhmcsInfo( $addonid, $whmcs_addons );
				//$this->aOrders[$i]['whmcs'] = $mon;
				//if( $mon ) {
					$info = array(
						'fullInfo'=> array(),
						'orderid'=> $itm['orderid'],
						'ordernum'=> $itm['ordernum'],
						'user_id'=> $itm['user_id']
					);
					$this->productname = $itm['productname'];
					
					$info['fullInfo']['hostingaddonid'] = $itm['hostingaddonid'];
					//$info['fullInfo']['productname'] = $itm['productname'];
					$info['fullInfo']['client'] = $itm['client'];
					$info['fullInfo']['domain'] = $service['domain'];
					$info['fullInfo']['dedicatedip'] = $service['dedicatedip'];
					
					$info['serverid'] = $service['serverid'];
					$info['pid'] = $addonid;
					$info['product_type'] = 'addon';
				
					$type = $mon['type'];
					$info['monitor_type'] = $type;

					$info['settings'] = $mon['settings'];
					if( $type == 'ping'){
						if( !empty( $service['dedicatedip'] )) {
							$info["web_site"] = $service['dedicatedip'];
						} elseif( !empty( $service['domain'] ) ) {
							$info["web_site"] = $service['domain'];
						} 
					} else {
						if( !empty( $service['domain'] ) ) {
							$info["web_site"] = $service['domain'];
						} elseif(!empty( $service['dedicatedip'] )) {
							$info["web_site"] = $service['dedicatedip'];
						} 
					}
					$all[] = $info;
				//}
			}
		}
		return $all;
	}

	public function automateAddMonitorsByAddonid( $addonid=0) {
		
		$adminuser = MonitisConf::getAdminName();
		$products = $this->addonOrdersList( $addonid, $adminuser );
		
		if( $products && count($products) > 0) {
		
			for($i=0; $i<count($products); $i++) {
				$resp = $this->createMonitor( $products[$i] );
				if( $resp['status'] && $resp['status'] == 'error') {
				}
_logActivity("createMonitor by AcceptOrder hook: **** result = ". json_encode( $resp));
//_dump( $resp );
			}
		}

	}
}