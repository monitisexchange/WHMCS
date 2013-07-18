<monitis_data>
<?php
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}
//_dump($locations);

$serverID = monitisGet('server_id');
$editMode = monitisGet('editMode');
$agentId = monitisGet('agentId');

//$monitorID = intval(monitisPostInt('module_CreateMonitorServer_monitorID'));
$monitorID = intval(monitisPostInt('module_CreateMonitorServer_monitorID'));
$monitorType = 'memory';
$serverName = $ipaddress = $hostname = '';
if($serverID > 0){

	$oWHMCS = new WHMCS_class( MONITIS_CLIENT_ID );
	//$mInt = $oWHMCS->intServerMonitors( $serverID, $monitorType );
	$srv_info = $oWHMCS->serverInfo( $serverID );
	$server = $srv_info[0];
	$hostname=$server['hostname'];
	$serverName = $server['name'];
	$ipaddress = $server['ipaddress'];
	
	$mInt = $oWHMCS->intMonitorsByType( $agentId, $monitorType );
	if( $mInt ) {
		$monitorID = $mInt[0]['monitor_id'];
	}
	//$serverName = $mInt[0]['name'];
	//$ipaddress = $mInt[0]['ipaddress'];
	//$hostname = $mInt[0]['hostname'];
}


$isEdit = $monitorID > 0;

$agentPlatform = MonitisConf::$newAgentPlatform;

$memory =  MonitisConf::$settings['memory'][$agentPlatform];
$disabled = $readonly = '';

$agentKey = $hostname;
$name = 'memory@'.$hostname;
$tag = $hostname.'_whmcs';

$action_type = 'create';

$freeLimit = $memory['freeLimit'];					//  for WINDOWS, LINUX, OPENSOLARIS agents
$freeSwapLimit = $memory['freeSwapLimit'];			//  for WINDOWS, LINUX, OPENSOLARIS agents

//  for LINUX
$cachedLimit = $memory['cachedLimit'];		
$bufferedLimit = $memory['bufferedLimit'];

// WINDOWS
$freeVirtualLimit = $memory['freeVirtualLimit'];	//  for WINDOWS  agents

if ($isEdit) {
	//$monitor = MonitisApi::getExternalMonitorInfo($monitorID);
	$readonly = 'readonly="readonly"';
	$disabled = 'disabled="disabled"';
	
	$action_type = 'edit';
	$monitor = MonitisApi::getMemoryInfo( $monitorID );
	
	$name = $monitor['name'];
	$tag = ( isset($monitor['tag']) ) ? $monitor['tag'] : $hostname.'_whmcs';
	
	$agentKey = $monitor['agentKey'];
	$agentPlatform = $monitor['agentPlatform'];
	$freeLimit = $monitor['freeLimit'];
	$freeSwapLimit = $monitor['freeSwapLimit'];
	$freeVirtualLimit = $monitor['freeVirtualLimit'];
	$bufferedLimit = $monitor['bufferedLimit'];
	$cachedLimit = $monitor['cachedLimit'];
	//_dump($monitor);
}

//_dump($monitor);
//$serverID = monitisGet('server_id');
//echo "serverID = $serverID; monitorID = $monitorID; <br>";
//echo "Module ---- modules/CreateMonitorServer/createFormMemory.php <br>";
?>

<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>

<form action="" method="post">
	<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
		<tr>
			<td class="fieldlabel" width="30%">Monitor type</td>
			<td class="fieldarea">
			
<? if ($isEdit && $editMode == 'edit') { ?>
				<input type="text" name="type" size="50" placeholder="Monitor type" value="<?=$monitorType?>" <?=$readonly?> />
<? } else {?>
				<select name="type" onchange="javascript: m_CreateMonitorServer.loadCreateForm(this.value);">
					<optgroup label="External monitors">
						<option value="ping">Ping</option>
					</optgroup>
					<optgroup label="Internal monitors">
						<option value="cpu">CPU</option>
						<option value="memory" selected="selected">Memory</option>
						<option value="drive">Drive</option>
					</optgroup>
				</select>
<?}?>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name</td>
			<td class="fieldarea"><?=$name?>
				<input type="hidden" name="name" size="50" placeholder="Name of the monitor" value="<?=$name?>"  />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Agent Key</td>
			<td class="fieldarea"><?=$agentKey?>
				<input type="hidden" name="agentKey" size="50" placeholder="Agent Key of the monitor" value="<?=$agentKey?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Agent platform</td>
			<td class="fieldarea"><?=$agentPlatform?>
				<input type="hidden" name="agentPlatform" size="50" value="<?=$agentPlatform?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Free Limit:</td>
			<td class="fieldarea"><input type="text" name="freeLimit" size="50" placeholder="Free limit for the test" value="<?=$freeLimit?>" /></td>
		</tr>
		<tr>
			<td class="fieldlabel">Free Swap Limit:</td>
			<td class="fieldarea"><input type="text" name="freeSwapLimit" size="50" placeholder="Free swap limit the test" value="<?=$freeSwapLimit?>" /></td>
		</tr>
		
<? if ($_agentPlatform == 'LINUX') { ?>
		<tr>
			<td class="fieldlabel">Buffered Limit:</td>
			<td class="fieldarea"><input type="text" name="bufferedLimit" size="50" placeholder="Buffered limit for the test" value="<?=$bufferedLimit?>" /></td>
		</tr>
		<tr>
			<td class="fieldlabel">Cached Limit:</td>
			<td class="fieldarea"><input type="text" name="cachedLimit" size="50" placeholder="cached Limit for the test" value="<?=$cachedLimit?>" /></td>
		</tr>
<?} elseif ($_agentPlatform == 'WINDOWS') {?>
		<tr>
			<td class="fieldlabel">Free Virtual Limit:</td>
			<td class="fieldarea"><input type="text" name="freeVirtualLimit" size="50" placeholder="Free virtual limit for the test" value="<?=$freeVirtualLimit?>" /></td>
		</tr>
<?} elseif ($_agentPlatform == 'OPENSOLARIS') {?>

<?}?>

		<tr>
			<td class="fieldlabel"></td>
			<td class="fieldarea">
				<input type="button" value="<?php echo $isEdit ? 'Save' : 'Create' ?>" onclick="javascript: m_CreateMonitorServer.submitForm();">
			</td>
		</tr>
	</table>
	<input type="hidden" name="tag" value="<?=$tag?>" />
	
	<input type="hidden" name="action_type" value="<?=$action_type?>" />
	<input type="hidden" name="monitor_type" value="<?=$monitorType?>" />
	<input type="hidden" name="monitor_id" value="<?=$monitorID?>" />
	<input type="hidden" name="server_id" value="<?=$serverID?>" />
	<input type="hidden" name="server_ipaddress" value="<?=$ipaddress?>" />
	<input type="hidden" name="server_hostname" value="<?=$hostname?>" />
	
	<input type="hidden" name="module_CreateMonitorServer_monitorID" value="<?=$monitorID?>" />
	<input type="hidden" name="module_CreateMonitorServer_action" value="createSubmited" />
</form>
</monitis_data>