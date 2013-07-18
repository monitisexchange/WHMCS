<?
class servicesClass extends WHMCS_product_db {
	public function __construct () {}


	public function product_by_order( $orderid ) {

	
		$service = $this->serviceByOrderId($orderid);

		if( $service ) {
			$serviceid = $service['serviceid'];
			$userid = $service['user_id'];


			$command = "getclientsproducts";
			$adminuser = "admin";
			$values["clientid"] = $userid;
			$values["serviceid"] = $serviceid;
			 
			$results = localAPI($command,$values,$adminuser);

			if( $results && $results['result'] == 'success') {
				$products = $results['products']['product'];
				$pid = $products[0]['pid'];
				if( !$pid ) return null;

				$monitor = $this->isWhmcsProduct( $pid );

				if( $monitor ) {
					$flds = $this->productFields( $pid );
					$monitor_types = '';
					if( $flds ) {

						//$monitorType = false;
						//$website_field = false;
						$website_field_id = $monitorType_id = 0;
						for($j=0; $j<count($flds); $j++){
							if( $flds[$j]['fieldname'] == MONITIS_FIELD_WEBSITE ) { 
								//$website_field = true;
								$website_field_id = $flds[$j]['id'];
								$service["web_site"] = $flds[$j]['value'];
							}
							if( $flds[$j]['fieldname'] == MONITIS_FIELD_MONITOR) {
								//$monitorType = true;
								$monitor_types = $flds[$j]['fieldoptions'];
								$monitorType_id = $flds[$j]['id'];
								$service["monitor_type"] = $flds[$j]['value'];
							}
						}
						if( $website_field_id > 0 && $monitorType_id > 0 ) {

							$service["pid"] = $pid;
							$service["monitor_types"] = $monitor_types;
//_dump( $service );
							return $service;
					
						} else return null;
					} else return null;
				} else return null;

			} else return null;
		} else return null;
	}
	private function addToWhmcs( $monitor_type,  & $product ) {
		

		if($monitor_type == 'ping')
			$monitor_id = MonitisApiHelper::addWebPing( $product );
		else
			$monitor_id = MonitisApiHelper::addDefaultWeb( $product, $monitor_type );
			
		if( $monitor_id > 0 ) {

			//$type = $monitor_type;
			//if( $type == 'ping') 
			$type = 'external';
			$params = array('moduleType'=>$type,'monitorId'=>$monitor_id);

			$resp = MonitisApi::getWidget($params);

			if( $resp && !$resp['error'] ) {
				$monitor = $this->productMonitorById($monitor_id);
				$publicKey = $resp['data'];
				$values = array(
					'product_id' => $product['pid'], 
					'type' => 'product', 
					'monitor_id' => $monitor_id,
					'monitor_type' => $monitor_type,
					'user_id' => $product['user_id'],
					'orderid' => $product['orderid'],
					'ordernum' => $product['ordernum'],
					'publicKey' => $publicKey
				);
				if( !$monitor ) {
					insert_query('mod_monitis_product_monitor', $values);
				} else {
					//$where = array("monitor_id" => $monitor_id);
					//update_query('mod_monitis_product_monitor', $values, $where);	
				}
			}
		}
	}
	public function createMonitors( $product ) {
		if( $product ) {
			$monitor_types = $product['monitor_types'];
			$monitor_type = $product['monitor_type'];
			$product["tag"] = $product['user_id'] . '_whmcs';
			
			//if( $monitor_type ) {
				$this->addToWhmcs( $product['monitor_type'],  $product );
			//} 

		}
	}
	///////////////////////////////////////////////////////////////// Addon

	public function addon_by_service( $vars ) {
		$serviceid = $vars['id'];
		$addon_service = $this->addonServiceById($serviceid);
		if($addon_service) {
			$service = $this->serviceById($addon_service['hostingaddonsid']);
			if( $service ) {
				//$addon_service["domain"] = $service["domain"];
				//$addon_service["domainstatus"] = $service["domainstatus"];
				
				$command = "getclientsproducts";
				$adminuser = "admin";
				$values["clientid"] = $service['userid'];
				$values["serviceid"] = $service['id'];
				 
				$results = localAPI($command,$values,$adminuser);
			
			}
		}

	}
}

?>