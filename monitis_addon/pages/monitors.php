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
$int_monitors = $oWHMCS->intServerMonitorsAll($serverID);


if( !$ext_monitors && !$int_monitors  ) {

	MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $srv_info[0] );
	$ext_monitors = $oWHMCS->extServerMonitors($serverID);
	$int_monitors = $oWHMCS->intServerMonitorsAll($serverID);
}

$hostname = $srv_info[0]['hostname'];
$oInt = new internalClass(); 
$agentInfo = $oInt->getAgentInfo( $hostname );

//
//$driveIds = '';
if( $agentInfo) {
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
.section_block {
	border: 1px solid #ccc; 
	padding: 20px 5px; 
	margin: 2px; 
	text-align: left;
}
</style>

<?php
if (count($ext_monitors) < 1 && count($int_monitors)) {
	echo '<br/><br/>';
	//echo '<center><h3>No monitors associated with this server</h3></center>';
	MonitisApp::addWarning("No monitors associated with this server.");
}
	MonitisApp::printNotifications();
?>

<div style="text-align: right;">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=servers">&#8592; Back to servers list</a>
</div>

<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>

<section class="section_block">
<?
	if( $ext_monitors ) {
		for($i=0; $i<count($ext_monitors); $i++) {
			//monitis_embed_module($ext_monitors[$i]['monitor_id'], $ext_monitors[$i]['monitor_type']);
			//echo monitis_embed_module($ext_monitors[$i]['monitor_id'], 'external');
			echo MonitisApiHelper::embed_module($ext_monitors[$i]['monitor_id'], 'external');
		}
	}

	if( $int_monitors ) {
		if( $int_monitors['cpu']) {
			//echo monitis_embed_module( $int_monitors['cpu']['monitor_id'], 'cpu');
			echo MonitisApiHelper::embed_module( $int_monitors['cpu']['monitor_id'], 'cpu');
		}
		if( $int_monitors['memory']) {
			//echo monitis_embed_module( $int_monitors['memory']['monitor_id'], 'memory');
			echo MonitisApiHelper::embed_module( $int_monitors['memory']['monitor_id'], 'memory');
		}
		if( isset( $agentInfo['driveMonitors'] ) && count($agentInfo['driveMonitors']) > 0 ) {
			$driveMonitors = $agentInfo['driveMonitors'];
			for($i=0; $i<count($driveMonitors); $i++) {
				//echo monitis_embed_module( $driveMonitors[$i], 'drive');
				echo MonitisApiHelper::embed_module( $driveMonitors[$i], 'drive');
			}
		}
	}
?>
</section>