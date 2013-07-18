<monitis_data>
<?php
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}

//_dump($_GET);

//_dump($locations);
$serverID = monitisGet('server_id');
$editMode = monitisGet('editMode');
$isAgent = monitisGet('isAgent');
$agentId = monitisGet('agentId');
//$agentKey = monitisGet('agentKey');
//$drivesList = monitisGet('drivesList');


$monitorID = monitisPostInt('module_CreateMonitorServer_monitorID');
$serverName = $ipaddress = $hostname = '';
$monitorType = 'ping';
if($serverID > 0){

	$oWHMCS = new WHMCS_class( MONITIS_CLIENT_ID );
	$mExt = $oWHMCS->extServerMonitors( $serverID );

	if( $mExt) {
		$serverName = $mExt[0]['name'];
		$monitorID = $mExt[0]['monitor_id'];
		$hostname = $mExt[0]['hostname'];
		$ipaddress = $mExt[0]['ipaddress'];
	} else {
		$mExt = $oWHMCS->serverInfo( $serverID );
		
//_dump($mExt);

		$mExt['client_id'] = MONITIS_CLIENT_ID;
		MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $mExt[0] );
		$mExt = $oWHMCS->extServerMonitors( $serverID );
//_dump($mExt);
		$serverName = $mExt[0]['name'];
		$monitorID = $mExt[0]['monitor_id'];
		$hostname = $mExt[0]['hostname'];
		$ipaddress = $mExt[0]['ipaddress'];		

//echo "************** mExt ************* monitorID = $monitorID ***** <br>";	

	}
}

$isEdit = $monitorID > 0;
$disabled = $readonly = '';
$action_type = 'create';
$ping = MonitisConf::$settings['ping'];
$_name = '';
$_url = $ipaddress.'_ping';
$_tag = $server['name'].'_whmcs';
$_timeout = $ping['timeout'];

if ($isEdit) {
	$monitor = MonitisApi::getExternalMonitorInfo($monitorID);
	$readonly = 'readonly="readonly"';
	$disabled = 'readonly="readonly"';
	//_dump($monitor);
	$_timeout = $monitor['timeout'];
	$_name = $monitor['name'];
	$_url = $monitor['url'];
	$_tag = $monitor['tag'];
	$action_type = 'edit';
} 
?>
<style>
.fieldlabel {
	font-weight:bold;
}
</style>
<div class="dialogTitle"><?php if($serverName!='') echo "<b>Server name:</b> $serverName"; ?></div>
<form action="" method="post">
	<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
		<tr>
			<td class="fieldlabel" width="30%">Monitor type</td>
			<td class="fieldarea">

<? if( isset($isAgent) && $isAgent == 1 ) { ?>
				<select name="type" onchange="javascript: m_CreateMonitorServer.loadCreateForm(this.value);">
					<optgroup label="External monitors">
						<option value="ping" selected="selected">Ping</option>
					</optgroup>
					<optgroup label="Internal monitors">
						<option value="cpu">CPU</option>
						<option value="memory">Memory</option>
						<option value="drive">Drive</option>
					</optgroup>
				</select>

<? } else {?>
				<?=$monitorType?>
				<input type="hidden" name="type" size="50" placeholder="Monitor type" value="<?=$monitorType?>"  />
<?}?>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name</td>
			<td class="fieldarea"><?=$_name?>
				<input type="hidden" name="name" size="50" value="<?=$_name?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Url</td>
			<td class="fieldarea"><?=$_url?>
				<input type="hidden" name="url" size="50" value="<?=$_url?>"  />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check interval</td>
			<td class="fieldarea">
				<select name="interval">
					<option value="1" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 1) echo 'selected="selected"'; ?>>1</option>
					<option value="3" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 3) echo 'selected="selected"'; ?>>3</option>
					<option value="5" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 5) echo 'selected="selected"'; ?>>5</option>
					<option value="10" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 10) echo 'selected="selected"'; ?>>10</option>
					<option value="15" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 15) echo 'selected="selected"'; ?>>15</option>
					<option value="20" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 20) echo 'selected="selected"'; ?>>20</option>
					<option value="30" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 30) echo 'selected="selected"'; ?>>30</option>
					<option value="40" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 40) echo 'selected="selected"'; ?>>40</option>
					<option value="60" <?php if ($isEdit && $monitor['locations'][0]['checkInterval'] == 60) echo 'selected="selected"'; ?>>60</option>
				</select> min.
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Test timeout in</td>
			<td class="fieldarea">
				<input type="text" name="timeout" size="20" value="<?=$_timeout?>" /> ms.
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check locations</td>
			<td class="fieldarea">
				<div class="monitisMultiselect">
					<span class="monitisMultiselectText"><u>{count}</u> locations selected</span>
					<input type="button" class="monitisMultiselectTrigger" value="Select" />
					<div class="monitisMultiselectInputs" inputName="locationIDs[]">
						<?php 
						foreach ($monitor['locations'] as $location) {
							echo '<input type="hidden" name="locationIDs[]" value="' . $location['id'] . '"/>';
						} ?>
					</div>
					<div class="monitisMultiselectDialog">
						<table style="width: 100%;" cellpadding=10>
							<tr>
								<?php foreach ($locations as $countryName => $country) { ?>
								<td style="vertical-align: top;">
									<div style="font-weight: bold; color: #71a9d2;">
										<?php echo $countryName; ?>
									</div>
									<hr/>
									<?php foreach ($country as $location) { ?>
										<div>
											<input type="checkbox" name="locationIDs[]" value="<?php echo $location['id']; ?>">
											<?php echo $location['fullName']; ?>
										</div>
									<?php } ?>
								</td>
								<?php } ?>
							</tr>
						</table>
					</div>
				</div>
			</td>
		</tr>
		<!-- tr>
			<td class="fieldlabel">Tag</td>
			<td class="fieldarea">
				<input type="text" name="tag" size="50" placeholder="Tag of the monitor" value="<?=$_tag?>" <?=$readonly?> />
			</td>
		</tr -->
		<tr>
			<td class="fieldlabel"></td>
			<td class="fieldarea">
				<input type="button" value="<?php echo $isEdit ? 'Save' : 'Create' ?>" onclick="javascript: m_CreateMonitorServer.submitForm();">
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