<?
class servicesClass extends WHMCS_product_db {

	public function __construct () {}

	public function createMonitor( & $product ) {
		
		$monitor = null;
		$monitor_type = $product['monitor_type'];
		$product["tag"] = $product['user_id'] . '_whmcs';
		
		if($monitor_type == 'ping')
			$monitor = MonitisApiHelper::addWebPing( $product );
		else
			$monitor = MonitisApiHelper::addDefaultWeb( $product );

		$result = array(
			"status" => 'ok',
			"monitor_type" => $monitor_type
		); 
		
		if( $monitor ) {
			$monitor_id = $monitor['testId'];
			
			$result["monitor_id"] = $monitor_id;
			
			$type = 'external';
			$params = array('moduleType'=>$type,'monitorId'=>$monitor_id);

			$resp = MonitisApi::getWidget($params);
			if( $resp && !$resp['error'] ) {
				$mon_monitor = $this->productMonitorById($monitor_id);
				
				$publicKey = $resp['data'];
				$values = array(
					'server_id' => $product['serverid'], 
					'product_id' => $product['pid'], 
					'type' => $product["product_type"], 
					'monitor_id' => $monitor_id,
					'monitor_type' => $monitor_type,
					'user_id' => $product['user_id'],
					'orderid' => $product['orderid'],
					'ordernum' => $product['ordernum'],
					'publicKey' => $publicKey
				);
				if( !$mon_monitor ) {
					insert_query('mod_monitis_product_monitor', $values);
					MonitisApiHelper::addServerAvailable( $product['serverid'] );
					
					$result["status"] = 'ok';
					$result["msg"] = 'success';
				} else {
					$result["status"] = 'warning';
					$result["msg"] = 'This monitor already exists.';
				}
			} else {
				$result["status"] = 'error';
				$result["action"] = 'getWidget';
				$result["msg"] = $resp['error'];
			}
		} else {
			$result["status"] = 'error';
			$result["action"] = 'addMonitor';
			$result["msg"] = $monitor['error'];
		}
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
						$mon = $this->isMonAddon($addonid);
						if( $mon ) {
							$type = $mon['type'];
							$info['monitor_type'] = $type;
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
							$monitor = $this->isWhmcsProduct( $pid );
_logActivity("product_by_order: product **** orderid = $orderid  pid -- $pid");
							if( $monitor ) {
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
}