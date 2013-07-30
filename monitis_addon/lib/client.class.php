<?
class monitisclientClass extends WHMCS_class {

	public function __construct () { }
	
	private function client_serversIds( $client_id, $adminuser ) {
	//$client_id=56;
		$result = array();
		$command = "getclientsproducts";
		//$adminuser = "chadmin";
		$values["clientid"] = $client_id;
		$prdcts = localAPI($command,$values,$adminuser);
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds getclientsproducts prdcts = ". json_encode($prdcts));
		if( $prdcts && $prdcts["result"] == 'success' && $prdcts["products"]["product"] ) {
			$products = $prdcts["products"]["product"];
			$srvrs = array();
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds products = ". json_encode($products));
			if( $products ) {
				for( $i=0; $i<count( $products ); $i++) {
					$product = $products[$i];
					if( $product["status"] == 'Active' && $product["serverid"] > 0) {
						$srvrs[] = intval($product["serverid"]);
					}
				}
				if( count($srvrs) > 0 ) {
					$srvrs = array_unique($srvrs);
					$result["status"] = 'ok';
					$result["data"] = $srvrs;
					return $result;
				} else {
				
				}
			}
			$result["status"] = 'error';
			$result["msg"] = 'No active products';
		} else {
			$result["status"] = 'error';
			if( isset($prdcts["message"]) && $prdcts["message"] != '')
				$result["msg"] = 'WHMCS error: '.$prdcts["message"];
			else
				$result["msg"] = 'No monitors';
			 
		}
		return $result;
	}
	
	private function client_setInternal( $server_id, & $int ) {
		
		$pubkeys = array();
		for( $i=0; $i<count($int); $i++ ) {
			if( $int[$i]["server_id"] == $server_id ) {
				//return $int[$i];
				$pubkeys[] = $int[$i]["publickey"];
			}
		}
		return $pubkeys;
	}
	
	public function clientMonitors( $client_id, $adminuser ) {
	
		$servers = $this->client_serversIds( $client_id, $adminuser );
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds servers = ". json_encode($servers));
		$result = array();
		if( $servers && $servers["status"] == 'ok' && count($servers["data"]) > 0 ) {
			$srvrsIds = implode(',', $servers["data"]);
			$ext = $this->servers_ext($srvrsIds);
			$int = $this->servers_int($srvrsIds);
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds ext = ". json_encode($ext));
			if( $ext && count($ext) > 0 ) {
				if( count($int) > 0 ) {
					for( $i=0; $i<count($ext); $i++ ) {
						$ext[$i]["internals"] = $this->client_setInternal( $ext[$i]["server_id"], $int );
					}
				}
				$result["status"] = 'ok';
				$result["data"] = $ext;
			} else {
				$result["status"] = 'error';
				$result["msg"] = 'No monitors for the active products, or they are not available';
			}

			//return $ext;
		} else {
			$result["status"] = 'error';
			$result["msg"] = $servers["msg"];
		}
		return $result;
	}
	
	public function embed_module( $publicKey ) {
		return '<script type="text/javascript">
		monitis_embed_module_id="'.$publicKey.'";
		monitis_embed_module_width="770";
		monitis_embed_module_height="350";
		monitis_embed_module_readonlyChart ="false";
		monitis_embed_module_readonlyDateRange="false";
		monitis_embed_module_datePeriod="0";
		monitis_embed_module_view="1";
		</script>
		<script type="text/javascript" src="https://api.monitis.com/sharedModule/shareModule.js"></script>
		<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';
	}
}
?>