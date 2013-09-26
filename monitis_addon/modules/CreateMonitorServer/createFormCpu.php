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
$agentId = monitisGet('agentId');
$monitorID = intval(monitisPostInt('module_CreateMonitorServer_monitorID'));
$singletype = monitisPostInt('module_CreateMonitorServer_singletype');
$monitorType = 'cpu';
$serverName = $ipaddress = $hostname = '';
$whmcs_cpus = null;
if($serverID > 0){

	$oWHMCS = new WHMCS_class();
	
	$srv_info = $oWHMCS->serverInfo( $serverID );
	$server = $srv_info[0];
	$hostname=$server['hostname'];
	$serverName = $server['name'];
	$ipaddress = $server['ipaddress'];
	
	if( $agentId > 0 ) {
		$whmcs_cpus = $oWHMCS->intMonitorsByType( $agentId, $monitorType );
		$whmcs_cpus = $whmcs_cpus[0];
	}

}

if( $whmcs_cpus ) {
	//$action_type = 'edit';
	//L::ii( 'intMonitorsByType whmcs_cpus = ' .  json_encode( $whmcs_cpus) );
	$monitorID = $whmcs_cpus['monitor_id']; 
}

$isEdit = $monitorID;

$disabled = $readonly = '';
$action_type = 'create';

$_agentKey = $hostname;
$_name = 'cpu@'.$hostname;
$_tag = $hostname.'_whmcs';

$_agentPlatform = MonitisConf::$newAgentPlatform;

$cpu =  MonitisConf::$settings['cpu'][$_agentPlatform];

$_kernelMax = $cpu['kernelMax'];
$_idleMin = $cpu['idleMin'];
$_ioWaitMax = $cpu['ioWaitMax'];
$_niceMax = $cpu['niceMax'];
$_usedMax = $cpu['usedMax'];

// WINDOWS
$_userMax = $cpu['userMax'];


if ($isEdit > 0) {
	//$monitor = MonitisApi::getExternalMonitorInfo($monitorID);
	$readonly = 'readonly="readonly"';
	$disabled = 'disabled="disabled"';
	
	$action_type = 'edit';

	$monitor = MonitisApi::getCPUMonitor( $monitorID );
	$_agentPlatform = $monitor['agentPlatform'];
	
	$_name = $monitor['name'];
	$_tag = $monitor['tag'];
	$_agentKey = $monitor['agentKey'];
	
	$_kernelMax = $monitor['kernelMax'];
		
	$_idleMin = $monitor['idleMin'];
	if( isset($monitor['ioWaitMax']) )
		$_ioWaitMax = $monitor['ioWaitMax'];
	if( isset($monitor['iowaitMax']) )
		$_ioWaitMax = $monitor['iowaitMax'];
		
	
	$_niceMax = $monitor['niceMax'];
	if( isset($monitor['userMax']) )
		$_usedMax = $monitor['userMax'];
	if( isset($monitor['usedMax']) )
		$_usedMax = $monitor['usedMax'];	// ?????????????????
	//_dump($monitor);
}

?>
<style>
.fieldlabel {
	font-weight:bold;
}
</style>
<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>
<form action="" method="post" id="editMonitorForm">
	<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
		<tr>
			<td class="fieldlabel" width="30%">Monitor type</td>
			<td class="fieldarea">
<? if ($isEdit && $editMode == 'edit') { ?>
				<?=$monitorType?>
				<input type="hidden" name="type" size="50" value="<?=$monitorType?>" />
<? } else {?>
                               <? if($singletype){ ?>
                                <span>CPU</span>
                               <? } else{?>
				<select name="type" onchange="javascript: m_CreateMonitorServer.loadCreateForm(this.value);">
					<optgroup label="External monitors">
						<option value="ping">Ping</option>
					</optgroup>
					<optgroup label="Internal monitors">
						<option value="cpu" selected="selected">CPU</option>
						<option value="memory">Memory</option>
						<option value="drive">Drive</option>
					</optgroup>
				</select>
                               <? } ?>
<?}?>
	
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name</td>
			<td class="fieldarea"><?=$_name?><input type="hidden" name="name" size="50" value="<?=$_name?>" /></td>
		</tr>

		<tr>
			<td class="fieldlabel">Agent Key</td>
			<td class="fieldarea"><?=$_agentKey?><input type="hidden" name="agentKey" size="50" value="<?=$_agentKey?>"/></td>
		</tr>
		<tr>
			<td class="fieldlabel">Agent platform</td>
			<td class="fieldarea">

			<?=$_agentPlatform?>
			<input type="hidden" name="agentPlatform" size="50" placeholder="Agent Platform of the monitor" value="<?=$_agentPlatform?>" <?=$readonly?> />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Kernel Max:</td>
			<td class="fieldarea"><input type="text" name="kernelMax" size="50" placeholder="Kernel Max for the test" value="<?=$_kernelMax?>" /></td>
		</tr>

<? if ($_agentPlatform == 'LINUX') { ?>		
		<tr>
			<td class="fieldlabel">Used Max:</td>
			<td class="fieldarea"><input type="text" name="usedMax" size="50" placeholder="used Max for the test" value="<?=$_usedMax?>" /></td>
		</tr>
		<tr>
			<td class="fieldlabel">idle Min:</td>
			<td class="fieldarea"><input type="text" name="idleMin" size="50" placeholder="used Max for the test" value="<?=$_idleMin?>" /></td>
		</tr>
		<tr>
			<td class="fieldlabel">IO Wait Max:</td>
			<td class="fieldarea"><input type="text" name="ioWaitMax" size="50" placeholder="io Wait Max for the test" value="<?=$_ioWaitMax?>" /></td>
		</tr>
		<tr id="niceMax_id" style="display:<?php if($_agentPlatform != 'LINUX') echo 'none';?>">
			<td class="fieldlabel">Nice Max:</td>
			<td class="fieldarea"><input type="text" name="niceMax" size="50" placeholder="nice Max for the test" value="<?=$_niceMax?>" /></td>
		</tr>
<?} elseif ($_agentPlatform == 'WINDOWS') {?>
		<tr>
			<td class="fieldlabel">User Max:</td>
			<td class="fieldarea"><input type="text" name="userMax" size="50" placeholder="used Max for the test" value="<?=$_userMax?>" /></td>
		</tr>
		
<?} elseif ($_agentPlatform == 'OPENSOLARIS') {?>

<?}?>
		<tr>
			<td class="fieldlabel"></td>
			<td class="fieldarea">
				<input type="button" value="<?php echo $isEdit ? 'Save' : 'Create' ?>" onclick="javascript: m_CreateMonitorServer.submitForm('editMonitorForm');">
			</td>
		</tr>
	</table>
	<input type="hidden" name="tag" value="<?=$_tag?>" />
	
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