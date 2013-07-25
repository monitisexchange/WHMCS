<?
class monitisclientClass extends WHMCS_class {

	public function __construct () { }
	
	private function client_serversIds( $client_id ) {
	
		$command = "getclientsproducts";
		$adminuser = "admin";
		$values["clientid"] = $client_id;
		$prdcts = localAPI($command,$values,$adminuser);
		if( $prdcts && $prdcts['result'] == 'success' && $prdcts['products']['product'] ) {
			$products = $prdcts['products']['product'];
			$srvrs = array();
			if( $products ) {
				for( $i=0; $i<count( $products ); $i++) {
					$product = $products[$i];
					if( $product['status'] == 'Active' && $product['serverid'] > 0) {
						$srvrs[] = $product['serverid'];
					}
				}
			}
			if( count($srvrs) > 0 )
				$srvrs = array_unique($srvrs);
			return $srvrs;
		}
		return null;
	}
	
	private function client_setInternal( $server_id, & $int ) {
		
		$pubkeys = array();
		for( $i=0; $i<count($int); $i++ ) {
			if( $int[$i]['server_id'] == $server_id ) {
				//return $int[$i];
				$pubkeys[] = $int[$i]['publickey'];
			}
		}
		return $pubkeys;
	}
	
	public function clientMonitors( $client_id ) {
	
		$srvrs = $this->client_serversIds( $client_id );
		if( $srvrs && count($srvrs) > 0 ) {
			$srvrsIds = implode(',', $srvrs);
			$ext = $this->servers_ext($srvrsIds);
			$int = $this->servers_int($srvrsIds);
			
			if( $ext && count($ext) > 0 && count($int) > 0 ) {
				for( $i=0; $i<count($ext); $i++ ) {
					$ext[$i]['internals'] = $this->client_setInternal( $ext[$i]['server_id'], $int );
				}
				return $ext;
			}
		}
		return null;
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