<?php

function monitis_embed_externalSnapshot( $monitor_type, $ext_ids ) {

	$params = array('moduleType'=>$monitor_type);
	$resp = MonitisApi::getWidget($params);
	if( $resp && isset($resp['data']) ) {
	return '<script type="text/javascript">
monitis_embed_module_id="'.$resp['data'].'"
monitis_embed_module_width="500";
monitis_embed_module_height="350";
monitis_embed_module_readonly="false";
monitis_embed_module_show_group_filter="false";   /*hide grouop filter in snapshot module*/
monitis_embed_module_monitorIds="'.$ext_ids.'"; /*filter results by testIds*/
</script>
<script type="text/javascript" src="'.MONITISAPIURL_JS.'/sharedModule/shareModule.js"></script>
<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';
	} else {
		return '<div style="width:500px;text-align:center">No data to display</div>';
	}
}

/////////////////
function monitis_embed_internalSnapshot( $monitor_type, $ids ) {

	$params = array('moduleType'=>$monitor_type);
	$resp = MonitisApi::getWidget($params);
	if( $resp && isset($resp['data']) ) {
	return '<script type="text/javascript">
monitis_embed_module_id="'.$resp['data'].'";
monitis_embed_module_width="500";
monitis_embed_module_height="350";
monitis_embed_module_readonly="false";
monitis_embed_module_monitorIds="'.$ids.'"; /*filter results by testIds*/
</script>
<script type="text/javascript" src="'.MONITISAPIURL_JS.'/sharedModule/shareModule.js"></script>
<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';
	} else {
		return '<div style="width:500px;text-align:center">No data to display</div>';
	}
}

class monitisSnapshotsClass {
	
	//private $whmcs_ext = null;
	private $whmcs_int = null;
	
	private $whmcs_ext_ids = null;
	private $whmcs_int_ids = null;
	
	//private $client_id = null;
	
	public function __construct() {}
	private function _idsList( & $list, $fieldName ){
		$ids = array();
		if( count($list) > 0 ) {
			$cnt = count($list);
			for($i=0; $i<$cnt;$i++) {
				$ids[] = $list[$i][$fieldName];
			}
			$ids = array_unique($ids); 
		}
		return $ids;
	}
	//
	public function intSnapshotsIds() {
		$whmcs_int = $this->whmcs_int;
		$cpus_ids = array();
		$mems_ids = array();
		$devs_ids = array();
		
		for($i=0; $i<count($whmcs_int); $i++) {
			$mon = $whmcs_int[$i];
			if( $mon['monitor_type'] == 'cpu') {
				$cpus_ids[] = $mon['monitor_id'];
			} elseif($mon['monitor_type'] == 'memory'){
				$mems_ids[] = $mon['monitor_id'];
			} elseif($mon['monitor_type'] == 'drive'){
				$devs_ids[] = $mon['monitor_id'];
			}
		}
		if( count($cpus_ids) > 0 || count($mems_ids) > 0 || count($devs_ids) > 0 ) {
			$this->whmcs_int_ids =  array(
				'cpu' => implode(",", $cpus_ids),
				'memory' => implode(",", $mems_ids),
				'drive' => implode(",", $devs_ids)
			);
		}
	}
	/////////////////////////////////////////////////

	public function allSnapshots() {
		$whmcsExt = monitisWhmcsServer::ext_monitors();
		if( $whmcsExt ) {
			$this->whmcs_ext_ids = MonitisHelper::idsByField( $whmcsExt, 'monitor_id' );
		}
		
		$this->whmcs_int = monitisWhmcsServer::int_monitors();
		
		if( $this->whmcs_int ) {
			$this->intSnapshotsIds();
		}		
	}
	
	public function get_whmcs_ext_ids() {
		if( $this->whmcs_ext_ids )
			return  implode(",", $this->whmcs_ext_ids);
		else 
			return null;
	}
	public function get_whmcs_int_ids() {
		return $this->whmcs_int_ids;
	}	
}

$oMnts = new monitisSnapshotsClass();
$oMnts->allSnapshots();
$ext_ids = $oMnts->get_whmcs_ext_ids();
$int_ids = $oMnts->get_whmcs_int_ids();
//_dump( $int_ids );

MonitisApp::printNotifications();
?>
<section style="text-align:left">
<?php
if( $ext_ids )
	echo monitis_embed_externalSnapshot( 'externalSnapshot', $ext_ids );

if( $int_ids ) {
	if( !empty($int_ids['cpu']) )
			echo monitis_embed_internalSnapshot( 'cpuSnapshot', $int_ids['cpu'] );
	if( !empty($int_ids['memory']) )
			echo monitis_embed_internalSnapshot( 'memorySnapshot', $int_ids['memory'] );
	if( !empty($int_ids['drive']) )
			echo monitis_embed_internalSnapshot( 'driveSnapshot', $int_ids['drive'] );
}

?>
</section>