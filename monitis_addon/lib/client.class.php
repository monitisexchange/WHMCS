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
		
//logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds getclientsproducts prdcts = ". json_encode($prdcts));
		if( $prdcts && $prdcts["result"] == 'success' && $prdcts["products"]["product"] ) {
			$products = $prdcts["products"]["product"];
			$srvrs = array();
//logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds products = ". json_encode($products));
			if( $products ) {
				for( $i=0; $i<count( $products ); $i++) {
					$product = $products[$i];
					if( $product["status"] == 'Active' && $product["serverid"] > 0) {
						//$srvrs[] = intval($product["serverid"]);
						$srvrs[] = array(
							'serverid'=> intval($product["serverid"]),
							'pid'=> intval($product["pid"]),
							'name'=> $product["name"],
							'groupname'=> $product["groupname"]
						);
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
/*
	private function client_setInternal( $server_id, & $int ) {
		
		$pubkeys = array();
		for( $i=0; $i<count($int); $i++ ) {
			if( $int[$i]["server_id"] == $server_id ) {
				$pubkeys[] = $int[$i]["publickey"];
			}
		}
		return $pubkeys;
	}
*/
	private function getMonitor( $server_id, & $arr ) {
		
		$monitors = array();
		for( $i=0; $i<count($arr); $i++ ) {
			if( $arr[$i]["server_id"] == $server_id ) {
				$monitors[] = $arr[$i];
			}
		}
		return $monitors;
	}	
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

	public function clientMonitors( $client_id, $adminuser ) {
	
		$srvrs = $this->client_serversIds( $client_id, $adminuser );

logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus client_serversIds srvrs = ". json_encode($srvrs));
		$result = array();

		if( $srvrs && $srvrs["status"] == 'ok' && count($srvrs["data"]) > 0 ) {

			$all_srvrs = $srvrs["data"];
			$serdersIds = $this->_idsList( $all_srvrs, 'serverid' );
			$srvrsIds = implode(',', $serdersIds);
			
			$ext = $this->servers_ext($srvrsIds, 1 );	// 1 - availble
			$int = $this->servers_int($srvrsIds, 1 );

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
//_dump($all_srvrs);
		} else {
			$result["status"] = 'error';
			$result["msg"] = $srvrs["msg"];
		}

		return $result;
	}
}
?>