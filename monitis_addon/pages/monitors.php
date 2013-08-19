<?php
require_once ('../modules/addons/monitis_addon/lib/serverslisttab.class.php');
//
if( isset($_POST) && isset($_POST['type']) && !empty($_POST['type']) ) {
	$action = $_POST['type'];
	$monitorID = monitisPostInt('monitor_id');
	$monitorType = monitisPost('monitor_type');
	$values = monitisPostInt('values');
	switch( $action ) {
		case 'suspend':
			if( $values == 0 ) 
				$resp = MonitisApi::suspendExternal("$monitorID");
			else
				$resp = MonitisApi::activateExternal("$monitorID");
//_dump($resp);

		break;
		case 'available':
			$table = 'mod_monitis_int_monitors';
			if( $monitorType == 'ping')
				$table = 'mod_monitis_ext_monitors';
			$update['available'] = $values;
			$where = array('monitor_id' => $monitorID);
			update_query($table, $update, $where);
			update_query('mod_monitis_product_monitor', $update, $where);	
		break;
	}
}


$editMode = 'create';
$serverID = monitisGetInt('server_id');
if ($serverID == 0)
	MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=servers');

	
//////////////////////////////////////
$isAgent = 0;

$oWHMCS = new WHMCS_class();
$srv_info = $oWHMCS->serverInfo( $serverID );

$serverObj = $srv_info[0];
$serverName = $serverObj['name'];

$ext_monitors = $oWHMCS->extMonitorsByServerId($serverID);
$int_monitors = $oWHMCS->intAssosMonitors($serverID);
$whmcs = array("ext"=>$ext_monitors, "int"=>$int_monitors);

$resp = MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $serverObj, $whmcs );

//echo "************** ".json_encode($resp)."<br>";

$ext_monitors = $oWHMCS->extMonitorsByServerId($serverID);
$int_monitors = $oWHMCS->intAssosMonitors($serverID);
/*
if( !$ext_monitors ) {
	if( MonitisConf::$settings['ping']['autocreate'] > 0 ) {
		$resp = MonitisApiHelper::addDefaultPing(MONITIS_CLIENT_ID, $serverObj );
	}
	$ext_monitors = $oWHMCS->extMonitorsByServerId($serverID);
}
//$resp = MonitisApiHelper::addDefaultAgents(MONITIS_CLIENT_ID, $serverObj );
//echo "************** internal ".json_encode($resp)."<br>";
*/

$hostname = $serverObj['hostname'];
$oInt = new internalClass(); 
$agentInfo = $oInt->getAgentInfo( $hostname );
//if( $agentInfo && isset($agentInfo['status']) && $agentInfo['status'] != 'stopped' ) {
if( $agentInfo ) {
	$agentKey = $agentInfo['agentKey'];
	$agentId = $agentInfo['agentId'];
	//$whmcs_drives = $oWHMCS->intMonitorsByType( $agentId, 'drive' );
	//$resp = $oInt->associateDrives( $whmcs_drives, $agentInfo, $serverID );
	$isAgent = 1;
} else 
	$isAgent = 0;
	
//$int_monitors = $oWHMCS->intAssosMonitors($serverID);

$createModule = MonitisApp::getModule('CreateMonitorServer');
$createModule->linkText = "Create / modify monitor for this server";
$createModule->serverID = $serverID;
$createModule->serverName = $serverName;
$createModule->editMode = 'create';

$createModule->isAgent = $isAgent;
$createModule->agentId = $agentId;
$createModule->agentKey = $agentKey;
//$createModule->drivesList = $drivesList;
 
$createModuleContent = $createModule->execute();


?>

<div align="left">
	<?php echo $createModuleContent; ?>
	<br />
	<hr/>
</div>
<style>
.sectionblock {
	text-align: center;
}
.sectionblock .monitor {
	padding: 10px;
	text-align: center;
}
.sectionblock .monitor .btn{
	font-size:11px;
	line-height:14px;
	padding: 3px 7px;
}
</style>

<?php
if (count($ext_monitors) < 1 && count($int_monitors)) {
	echo '<br/><br/>';
	//echo '<center><h3>No monitors associated with this server</h3></center>';
	MonitisApp::addWarning("No monitors associated with this server.");
}
MonitisApp::printNotifications();
//_dump($ext_monitors);

$arentName = '';
if( $int_monitors ) {
	$arentName = $agentInfo['agentKey'];
}
?>

<div style="text-align: right;">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=servers">&#8592; Back to servers list</a>
</div>

<div class="dialogTitle"><?php if($serverName!='') echo "Server <b>$serverName</b>"; ?>
<?php if($arentName!='') echo ", server agent ".$agentInfo['status']." </b>" ?>
</div>


<script>
function setParameters(form, type, monitor_id, monitor_type, values) {
	form.type.value = type;
	form.monitor_id.value = monitor_id;
	form.monitor_type.value = monitor_type;
	form.values.value = values;
}
</script>
<section class="sectionblock">
<form action="" method="post" id="setMonitorForm">
<?
//m_CreateMonitorServer 
	if( $ext_monitors ) {

_logActivity("monitors tab **** ext_monitors = " . json_encode($ext_monitors) );
		$oSrvrs = new serversListTab();
		$pings = $oSrvrs->externalSnapShotsStatus( $ext_monitors );
		
		//$pings = $oSrvrs->externalSnapShots( $ext_monitors );
//_logActivity("monitors tab **** pings = " . json_encode($pings) );
		$ping = null;
		if( $pings && count($pings) > 0 ) 
			$ping = $pings[0]['status'];
		
		for($i=0; $i<count($ext_monitors); $i++) {
			//echo MonitisApiHelper::embed_module($ext_monitors[$i]['monitor_id'], 'external');
			$item = $ext_monitors[$i];
			$monitor_id = $item['monitor_id'];
			$monitor_type = $item['monitor_type'];
			$publickey = $item['publickey'];

//$ext = MonitisApi::getExternalSnapshot($monitor_id);
//_dump( $ext );
			if( $ping && $publickey) {
				echo '<figure class="monitor">';
				//echo MonitisApiHelper::embed_module_by_pubkey( $publickey, 800, 350 );
				echo monitis_embed_module( $publickey, 800, 350 );
				echo '<div>
				<input type="button" value="Edit" onclick="m_CreateMonitorServer.trigger('.$monitor_id.', \''.$monitor_type.'\');" class="btn" />';
				//if( $ping ) {
					if( $ping['status'] == 'suspended' ) {
						echo '<input type="submit" value="Activate" 
						onclick="setParameters(this.form, \'suspend\', '.$monitor_id.', \''.$monitor_type.'\', 1);" class="btn btn-success"  />';
					} else {
						echo '<input type="submit" value="Suspend" 
						onclick="setParameters(this.form, \'suspend\', '.$monitor_id.', \''.$monitor_type.'\', 0);" class="btn btn-suspended"  />';				
					}
				
				
					if( $item['available'] > 0 ) {
						echo '<input type="submit" value="Not available to customer" 
						onclick="setParameters(this.form, \'available\', '.$monitor_id.', \''.$monitor_type.'\', 0);" class="btn btn-suspended"  />';
					} else {
						echo '<input type="submit" value="Available to customer" 
						onclick="setParameters(this.form, \'available\', '.$monitor_id.', \''.$monitor_type.'\', 1);" class="btn btn-success"  />';
					}
					echo '<input type="button" value="Delete" onclick="m_CreateMonitorServer.loadMessagBox('.$monitor_id.', \''.$monitor_type.'\' );" class="btn btn-danger"  />';
				//} else {

				//}
				echo '</div></figure>';
			} else {
				//$oWHMCS->removeExternalMonitorsById($monitor_id);
echo "Unlink monitor $monitor_id ";			
			}
		}
	}
	
	if( $int_monitors ) {
//_dump( $agentInfo );

		$label = 'active';
		if( $agentInfo['status'] == 'stopped' ) {
			$label = 'closed';
		}
		
		for($i=0; $i<count($int_monitors); $i++) {
			//echo MonitisApiHelper::embed_module($ext_monitors[$i]['monitor_id'], 'external');
			$item = $int_monitors[$i];
			$monitor_id = $item['monitor_id'];
			$monitor_type = $item['monitor_type'];
			$publickey = $item['publickey'];
			
			if( $publickey) {
				echo '<figure class="monitor">';
				//echo MonitisApiHelper::embed_module_by_pubkey( $publickey, 800, 350 );
				echo monitis_embed_module( $publickey, 800, 350 );
				// btn-success
				echo '<div>
				<input type="button" value="Edit" onclick="m_CreateMonitorServer.trigger('.$monitor_id.', \''.$monitor_type.'\');" class="btn"  />';
				if( $item['available'] > 0 ) {
					echo '<input type="submit" value="Not available to customer" 
					onclick="setParameters(this.form, \'available\', '.$monitor_id.', \''.$monitor_type.'\', 0);" class="btn btn-suspended"  />';
				} else {
					echo '<input type="submit" value="Available to customer" 
					onclick="setParameters(this.form, \'available\', '.$monitor_id.', \''.$monitor_type.'\', 1);" class="btn btn-success"  />';
				}
				echo '<input type="button" value="Delete" onclick="m_CreateMonitorServer.loadMessagBox('.$monitor_id.', \''.$monitor_type.'\' );" class="btn btn-danger"  />
				</div>';
				echo '</figure>';
			}
			
		}
	}
?>
<input type="hidden" name="type" value="" />
<input type="hidden" name="monitor_id" value="" />
<input type="hidden" name="monitor_type" value="" />
<input type="hidden" name="values" value="" />
</form>
</section>

