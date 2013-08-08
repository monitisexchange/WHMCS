<monitis_data>
<?php
/*
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}
//_dump($locations);
*/

$serverID = monitisGet('server_id');
$editMode = monitisGet('editMode');
$agentKey = monitisGet('agentKey');
$agentId = monitisGet('agentId');

$_driveLetter = monitisPostInt('module_CreateMonitorServer_driveLetter');
$letterIndex = intval(monitisPostInt('module_CreateMonitorServer_letterIndex'));

$monitorID = intval(monitisPostInt('module_CreateMonitorServer_monitorID'));
$monitorType = 'drive';
$serverName = $ipaddress = $hostname = '';
$agentPlatform = MonitisConf::$newAgentPlatform;

$oWHMCS = new WHMCS_class( MONITIS_CLIENT_ID );
$whmcs_drives = null;
if($serverID > 0){
	$srv_info = $oWHMCS->serverInfo( $serverID );
	$server = $srv_info[0];
	$hostname=$server['hostname'];
	$serverName = $server['name'];
	$ipaddress = $server['ipaddress'];
	if( $agentId > 0 ) {
		$whmcs_drives = $oWHMCS->intMonitorsByType( $agentId, $monitorType );
	}
}

$agentInfo = null;


$isEdit = $monitorID > 0;

$drive =  MonitisConf::$settings['drive'];
$disabled = $readonly = '';
$name = $tag = '';
$action_type = 'create';
$freeLimit = $drive['freeLimit'];
$drives = null;

$oInt = new internalClass(); 

$agentInfo = $oInt->getAgentInfo( $hostname );

if( $agentInfo) {

	$agentKey = $agentInfo['agentKey'];
	$drives =  $agentInfo['drives'];
	
	if( $drives ) {

		$currDrive = $drives[$letterIndex];

		$action_type = 'create';
		
		$isEdit = 0;
		// monitor existing
		if( $currDrive['id'] ) {
			$monitorID = $currDrive['id'];
			$tag = $currDrive['tag'];
			$name = $currDrive['name'];
			$action_type = 'associate';
		} else {
			$tag = $agentInfo['tag'];
			$name = 'drive_'.$currDrive['letter'].'@'.$hostname;
			$monitorID = 0;
			$action_type = 'createDrive';
		}
		if( $whmcs_drives && $monitorID > 0 ) {
		
			for($i=0; $i<count($whmcs_drives); $i++) {
				if($whmcs_drives[$i]['monitor_id'] == $monitorID ) {
					$mon = MonitisApi::getDriveInfo( $monitorID );
					
					$freeLimit = $mon['freeLimit'];
					$action_type = 'edit';
					$isEdit = 1;
					break;
				}
				//$monitorID = 0;
			}
		}
		$_driveLetter  = $currDrive['letter'];
	}
} 
?>
<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>

<form action="" method="post" id="editMonitorForm">
	<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
		<tr>
			<td class="fieldlabel" width="30%">Monitor type</td>
			<td class="fieldarea">

				<select name="type" onchange="javascript: m_CreateMonitorServer.loadCreateForm(this.value);">
					<optgroup label="External monitors">
						<option value="ping">Ping</option>
					</optgroup>
					<optgroup label="Internal monitors">
						<option value="cpu">CPU</option>
						<option value="memory">Memory</option>
						<option value="drive" selected="selected">Drive</option>
					</optgroup>
				</select>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name</td>
			<td class="fieldarea"><?=$name?>
				<input type="hidden" name="name" value="<?=$name?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Agent Key</td>
			<td class="fieldarea"><?=$agentKey?>
				<input type="hidden" name="agentKey" size="30" placeholder="Agent Key of the monitor" value="<?=$agentKey?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Free Limit:</td>
			<td class="fieldarea"><input type="text" name="freeLimit" size="20" placeholder="Free limit for the test" value="<?=$freeLimit?>" />&nbsp;GB</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name of the drive:</td>
			<td class="fieldarea">
<? if( $drives) { ?>
				<select name="driveLetter" onchange="javascript: m_CreateMonitorServer.loadCreateDriveForm('drive', this.value);">
<?
		for( $i=0; $i<count($drives); $i++) {
			if( $drives[$i]['letter'] == $_driveLetter )
				echo '<option value="'.$drives[$i]['letter'].'" selected="selected">'.$drives[$i]['letter'].'</option>';
			else {
				echo '<option value="'.$i.'">'.$drives[$i]['letter'].'</option>';
			}
		}
?>
				</select>
<?} else {?>
			<input type="text" name="driveLetter" size="50" placeholder="Name of the drive the test" value="<?=$driveLetter?>" />
<?}?>
			</td>
		</tr>
		
		<tr>
			<td class="fieldlabel"></td>
			<td class="fieldarea">
				<input type="button" value="<?php echo $isEdit ? 'Save' : 'Create drive monitor' ?>" onclick="javascript: m_CreateMonitorServer.submitForm('editMonitorForm');">
			</td>
		</tr>
	</table>
	<input type="hidden" name="tag" value="<?=$tag?>" />
	<input type="hidden" name="agentId" value="<?=$agentId?>" />
	
	<input type="hidden" name="action_type" value="<?=$action_type?>" />
	<input type="hidden" name="monitor_type" value="<?=$monitorType?>" />
	<input type="hidden" name="monitor_id" value="<?=$monitorID?>" />
	<input type="hidden" name="server_id" value="<?=$serverID?>" />
	<input type="hidden" name="server_ipaddress" value="<?=$ipaddress?>" />
	<input type="hidden" name="server_hostname" value="<?=$hostname?>" />
	
	<input type="hidden" name="module_CreateMonitorServer_driveLetter" value="<?=$_driveLetter?>" />
	<input type="hidden" name="module_CreateMonitorServer_letterIndex" value="" />
	<input type="hidden" name="module_CreateMonitorServer_monitorID" value="<?=$monitorID?>" />
	<input type="hidden" name="module_CreateMonitorServer_action" value="createSubmited" />
</form>
</monitis_data>