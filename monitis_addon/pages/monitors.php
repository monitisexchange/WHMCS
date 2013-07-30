<?php

$editMode = 'create';
$serverID = monitisGetInt('server_id');
if ($serverID == 0)
	MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=servers');

	
//////////////////////////////////////
$isAgent = 0;

$oWHMCS = new WHMCS_class( MONITIS_CLIENT_ID );
$srv_info = $oWHMCS->serverInfo( $serverID );
$serverName = $srv_info[0]['name'];

$ext_monitors = $oWHMCS->extServerMonitors($serverID);
//$int_monitors = $oWHMCS->intServerMonitorsAll($serverID);
$int_monitors = $oWHMCS->intAssosMonitors($serverID);


if( !$ext_monitors && !$int_monitors  ) {

	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $srv_info[0] );
	$ext_monitors = $oWHMCS->extServerMonitors($serverID);
	//$int_monitors = $oWHMCS->intServerMonitorsAll($serverID);
	$int_monitors = $oWHMCS->intAssosMonitors($serverID);
	
}

$hostname = $srv_info[0]['hostname'];
$oInt = new internalClass(); 
$agentInfo = $oInt->getAgentInfo( $hostname );
//_dump( $agentInfo );
//
//$driveIds = '';
if( $agentInfo && isset($agentInfo['status']) && $agentInfo['status'] != 'stopped' ) {
	$agentKey = $agentInfo['agentKey'];
	$agentId = $agentInfo['agentId'];
	$isAgent = 1;
} else 
	$isAgent = 0;

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
	/*border: 1px solid #ccc; 
	padding: 20px 5px; 
	margin: 2px; */
	text-align: left;
}
.sectionblock .monitor {
	width:520px;
	height:350px;
	padding: 10px;
	/*border:1px dotted red;
	float:left;*/
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
?>

<div style="text-align: right;">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=servers">&#8592; Back to servers list</a>
</div>

<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>

<section class="sectionblock">
<form>
<?

	if( $ext_monitors ) {
		for($i=0; $i<count($ext_monitors); $i++) {
			//echo MonitisApiHelper::embed_module($ext_monitors[$i]['monitor_id'], 'external');
			$publickey = $ext_monitors[$i]['publickey'];
			if( $publickey) {
				//echo '<div class="monitor">';
				echo MonitisApiHelper::embed_module_by_pubkey( $publickey, 500, 350 );
				//echo '<div><input type="button" value="Delete" onclick="" class="btn-danger"  /></div>';
				//echo '</div>';
			}
		}
	}
	if( $int_monitors ) {
	
		for($i=0; $i<count($int_monitors); $i++) {
			//echo MonitisApiHelper::embed_module($ext_monitors[$i]['monitor_id'], 'external');
			$publickey = $int_monitors[$i]['publickey'];
			if( $publickey) {
				//echo '<div class="monitor">';
				echo MonitisApiHelper::embed_module_by_pubkey( $publickey, 500, 350 );
				//echo '<div><input type="button" value="Delete" onclick="" class="btn-danger"  /></div>';
				//echo '</div>';
			}
			
		}
	}
?>
</form>
</section>

