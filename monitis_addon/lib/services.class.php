<?
class servicesClass extends WHMCS_product_db {

	public function __construct () {}

	public function product_by_order( $orderid ) {
	
		$service = $this->serviceByOrderId($orderid);
		if( $service ) {
			$serviceid = $service['serviceid'];
			$userid = $service['user_id'];

			$values["clientid"] = $userid;
			$values["serviceid"] = $serviceid;
			$results = localAPI( "getclientsproducts", $values, "admin" );

			if( $results && $results['result'] == 'success') {
				$products = $results['products']['product'];
				$pid = $products[0]['pid'];
				$monitor = $this->isWhmcsProduct( $pid );
//L::ii( 'product_by_order ' .   json_encode($monitor) );
				if( $monitor ) {
					$flds = $this->productFields( $pid );
					$monitor_types = '';
					
					if( $flds ) {
						$website_field_id = $monitorType_id = 0;
						for($j=0; $j<count($flds); $j++){
							if( $flds[$j]['fieldname'] == MONITIS_FIELD_WEBSITE ) { 
								$website_field_id = $flds[$j]['id'];
								$service["web_site"] = $flds[$j]['value'];
							}
							if( $flds[$j]['fieldname'] == MONITIS_FIELD_MONITOR) {
								$monitor_types = $flds[$j]['fieldoptions'];
								$monitorType_id = $flds[$j]['id'];
								$service["monitor_type"] = $flds[$j]['value'];
							}
						}
						if( $website_field_id > 0 && $monitorType_id > 0 ) {
							$service["pid"] = $pid;
							$service["monitor_types"] = $monitor_types;
							return $service;
					
						} else return null;
					} else return null;
				} else return null;

			} else return null;
		} else return null;
	}
	public function addToWhmcs( $monitor_type, & $product ) {
		
		if($monitor_type == 'ping')
			$monitor_id = MonitisApiHelper::addWebPing( $product );
		else
			$monitor_id = MonitisApiHelper::addDefaultWeb( $product, $monitor_type );
//L::ii( 'addToWhmcs monitor_id = '  .   $monitor_id);

logActivity("MONITIS LOG ***** add monitor (addToWhmcs) **** monitor_type = $monitor_type ");

		if( $monitor_id > 0 ) {

			$type = 'external';
			$params = array('moduleType'=>$type,'monitorId'=>$monitor_id);

			$resp = MonitisApi::getWidget($params);
			if( $resp && !$resp['error'] ) {
				$monitor = $this->productMonitorById($monitor_id);
				$publicKey = $resp['data'];
				$values = array(
					'product_id' => $product['pid'], 
					'type' => $product["product_type"], 
					'monitor_id' => $monitor_id,
					'monitor_type' => $monitor_type,
					'user_id' => $product['user_id'],
					'orderid' => $product['orderid'],
					'ordernum' => $product['ordernum'],
					'publicKey' => $publicKey
				);
//L::ii( 'addToWhmcs values = '  .   json_encode( $values));
				if( !$monitor ) {
					insert_query('mod_monitis_product_monitor', $values);
				} else {
					//$where = array("monitor_id" => $monitor_id);
					//update_query('mod_monitis_product_monitor', $values, $where);	
				}
			} else {
			
//L::ii( 'createMonitors monitor_type = ' .$monitor_type . ' Error ' .   $resp['error']);

			}
		}
	}
	public function createMonitors( $product ) {
		if( $product ) {
			//$monitor_types = $product['monitor_types'];
			$monitor_type = $product['monitor_type'];
			
			$product["tag"] = $product['user_id'] . '_whmcs';
			//$product["product_type"] = 'product';

//L::ii( 'createMonitors monitor_type = ' .  $monitor_type );
//L::ii( 'createMonitors monitor_type = ' .  json_encode( $product) );

//_dump($product);

			$this->addToWhmcs( $product['monitor_type'],  $product );
		}
	}
	
	public function deactiveMonitorByOrder( $orderid ) {
		$this->_deactiveMonitorByOrder($orderid);
	}
	
	public function test_by_order( $orderid, $iOrder ) {
	
		//$values['id'] = $orderid;
		//$values['status'] = 'Active';
		//$values = array( "id"=> $orderid  );		// status: Pending, Active, Fraud, Cancelled
		//$iOrder = localAPI( "getorders", $values, "admin");
//L::ii( '******** test_by_order ****** orderid = '. $orderid. ' *****************  order = ' .  json_encode( $iOrder) );
		$info = array();
		
//logActivity("***** test_by_order: **** orderid = $orderid ". json_encode( $iOrder));

		if( $iOrder && $iOrder['result'] == 'success') {
			$info['orderid'] = $orderid;
			$ord = $iOrder['orders']['order'];
			$order = $ord[0];

			if( $order ) {
logActivity("MONITIS LOG ***** test_by_order: **** orderid = $orderid  order -- success");

				 $info['ordernum'] = $order["ordernum"];
				 $info['user_id'] = $order["userid"];
				 $items = $order['lineitems']['lineitem'];
				 
				 for( $i=0; $i<count($items); $i++ ) {
					$item = $items[$i];
					$order_type = $item['type'];	// addon / product
					$producttype = $item['producttype'];	// Addon / "Other Product/Service
					
logActivity("MONITIS LOG ***** test_by_order: **** orderid = $orderid  order_type -- $order_type");
					
//logActivity('***** test_by_order: **** status='.$item['status'].' * orderid='.$orderid.'****** ordernum='.$info['ordernum'].' *** order_type = '.$order_type);
			
					//if( $order_type == 'addon' && $item['status'] == 'Active' ) {
					if( $order_type == 'addon' ) {
					
//echo '** test_by_order addon ** items_count='.count($items).' * orderid='.$orderid.'****** ordernum='.$info['ordernum'].' *******<br>';
//L::ii( '** test_by_order addon ** items_count='.count($items).' * orderid='.$orderid.'****** ordernum='.$info['ordernum'].' **************  order = ' .  json_encode( $order) );
					
						
						$service = $this->addonService($orderid);
						$addonid = $service['addonid'];
						$mon = $this->isMonAddon($addonid);
						if( $mon ) {
							$type = $mon['type'];
							$info['monitor_type'] = $type;
							$info['pid'] = $addonid;
							$info['product_type'] = 'addon';

logActivity("MONITIS LOG ***** test_by_order: **** orderid = $orderid  addonid -- $addonid");

							if( $type == 'ping' && !empty( $service['dedicatedip'] ) ) {
								$info["web_site"] = $service['dedicatedip'];
							} elseif ( !empty( $service['domain'] ) ) {
								$info["web_site"] = $service['domain'];
							} else {
								return null;
							}
//L::ii( '****** addon ******** test_by_order 12 ***********************  order = ' .  json_encode( $info) );

							return $info;
//echo "hostingaddons ********  dedicatedip = $dedicatedip **** domain = $domain **** hostingid = $hostingid **** addonid = $addonid ********";
						}

					//} else if( $order_type == 'product' && $item['status'] == 'Active'  ) {
					} else if( $order_type == 'product' ) {
						$serviceid = $item['relid'];

						$values = array( "serviceid"=> $serviceid, "clientid" => $info['user_id'] );
						$prdcts = localAPI( "getclientsproducts", $values, "admin" );
						


						if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
							$products = $prdcts['products']['product'];
//echo '** test_by_order product ** products_count='.count($products).' * orderid='.$orderid.'****** ordernum='.$info['ordernum'].' *** serviceid='.$serviceid.'****<br>';



							$product = $products[0];
							$pid = $product['pid'];
logActivity("MONITIS LOG ***** test_by_order: **** orderid = $orderid  pid -- $pid");
//_dump($products );
							if( !$pid ) return null;
							$monitor = $this->isWhmcsProduct( $pid );

							if( $monitor ) {
//echo '** test_by_order product ** monitor='.count($products).' * orderid='.$orderid.'****** ordernum='.$info['ordernum'].' *** pid='.$pid.'****<br>';
/*
$flds = $product['customfields']['customfield'];
//$monitor_types = '';
if( $flds && count($flds ) > 0) {
	$website_field_id = $monitorType_id = 0;
	for($j=0; $j<count($flds); $j++){
		if( $flds[$j]['name'] == MONITIS_FIELD_WEBSITE ) { 
			$website_field_id = $flds[$j]['id'];
			$info["web_site"] = $flds[$j]['value'];
		}
		if( $flds[$j]['name'] == MONITIS_FIELD_MONITOR) {
			//$monitor_types = $flds[$j]['fieldoptions'];
			$monitorType_id = $flds[$j]['id'];
			$info["monitor_type"] = $flds[$j]['value'];
		}
	}
	if( $website_field_id > 0 && $monitorType_id > 0 ) {
echo '** test_by_order product *** website_field_id='.$website_field_id.'****** monitorType_id='.$monitorType_id.' *** pid='.$pid.'****<br>';

		$info["pid"] = $pid;
		//$info["monitor_types"] = $monitor_types;
		$info["product_type"] = 'product';
//_dump($info );
L::ii( '****** product ******** test_by_order 12 ***********************  order = ' .  json_encode( $info) );
		return $info;

	} 
}
*/						
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
//echo '** test_by_order product *** website_field_id='.$website_field_id.'****** monitorType_id='.$monitorType_id.' *** pid='.$pid.'****<br>';

										$info["pid"] = $pid;
										$info["monitor_types"] = $monitor_types;
										$info["product_type"] = 'product';
//_dump($info );
//L::ii( '****** product ******** test_by_order 12 ***********************  order = ' .  json_encode( $info) );
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