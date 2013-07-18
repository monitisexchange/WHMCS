<?php
$isNewAcc = monitisGetInt('isNewAcc');
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}

$newAgentPlatform = MonitisConf::$newAgentPlatform;

$old_ping =  MonitisConf::$settings['ping'];
$old_cpu =  MonitisConf::$settings['cpu'][$newAgentPlatform];
$old_memory =  MonitisConf::$settings['memory'][$newAgentPlatform];

$locationIds = $old_ping['locationIds'];


if (monitisPost('saveConfig')) {
	$saveNewServerMonitors = isset($_POST['newServerMonitors']) ? $_POST['newServerMonitors'] : array();
	$saveNewServerMonitors = implode(',', $saveNewServerMonitors);
	
//_dump($_POST);

	if( isset($_POST['locationIDs']) && !empty( $_POST['locationIDs']) ) {
		//$arr = explode(',', $_POST['locationIDs']);
		$arr = $_POST['locationIDs'];
		//$locationIds = doInt($arr);
		$locationIds = array_map( "intval", $arr );
//_dump($locationIds);
	}
	
	$platform = $_POST['agentPlatform'];
	
	$new = json_encode(MonitisConf::$settings);
	$newsets = json_decode($new, true);

	$newsets['ping'] = array(
		'interval'	=>	isset($_POST['interval']) ? intval($_POST['interval']) : $old_ping['interval'],
		'timeout'	=>	isset($_POST['timeout']) ? intval($_POST['timeout']) : $old_ping['timeout'],
		'locationIds'	=>	$locationIds //isset($_POST['locationIds']) ? explode(',', $_POST['locationIds']) : $old_ping['locationIds']
	);
	

	
	foreach( $newsets['cpu'][$platform] as $key=>$val ) {
		$newsets['cpu'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_cpu[$key];
	}
	
//_dump( $newsets['cpu'] );

	foreach( $newsets['memory'][$platform] as $key=>$val ) {
		$newsets['memory'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_cpu[$key];
	}

	$newsets_json = json_encode($newsets);
	$oldsets_json = json_encode(MonitisConf::$settings);
	$result = array();

	if( $newsets_json != $oldsets_json ) {
		$result['settings'] = $newsets_json;
	}
	
	if ($saveNewServerMonitors != MonitisConf::$newServerMonitors) {
		//MonitisConf::update('newServerMonitors', $saveNewServerMonitors);
		$result['newServerMonitors'] = $saveNewServerMonitors;
		//MonitisConf::update_settings( MONITIS_CLIENT_ID, array('newServerMonitors' => $saveNewServerMonitors ) );
	}
	
	if( $result && count($result) > 0) {
//_dump($result);
		MonitisConf::update_settings( MONITIS_CLIENT_ID, $result );
	}
	
	if ($isNewAcc)
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=syncExistingServers');

} else {
	if ($isNewAcc)
		MonitisApp::addMessage('Now please review plugin settings and click on "Save" button');
}

$newServerMonitors = explode(',', MonitisConf::$newServerMonitors);
$isPing = false;
$isCPU = false;
$isMemory = false;

if(in_array('ping', $newServerMonitors))	$isPing = true;
if(in_array('cpu', $newServerMonitors))	$isCPU = true;
if(in_array('memory', $newServerMonitors))	$isMemory = true;

//_dump($newServerMonitors);
?>
<?php MonitisApp::printNotifications(); 
// Automatically create following monitors when<br/>creating new servers on WHMCS
?>
<style>
table.form {
	border: 1px solid #ebebeb;
}

.form .title {
	color:#006699;
	font-size: 14px;
	font-family: Arial;
	font-weight: bold;
	padding: 15px 0px;
	border-bottom: solid 1px #ebebeb;
}

.form .subtitle {
	/*width:250px;*/
	font-size: 14px;
	font-family: Arial;
	font-weight: bold;
}
.form  .monitisDataporps .fieldlabel{
	color:#000;
}
</style>
<center>
	<form action="" method="post">
		<table class="form" width="100%" cellspacing=2 cellpadding=3>
			<tr>
				<td class="fieldarea11" style="text-align:center;">	
					<table class="form monitisDataporps" border=0 width="100%">
						<tr class="">
							<th colspan=3 class="title">External Monitors</th>
						</tr>
						<tr>
							<td colspan="3" class="subtitle"><input type="checkbox" name="newServerMonitors[]" value="ping" <?php if(in_array('ping', $newServerMonitors)) echo 'checked=checked'; ?> 
							onchange="var node=document.getElementById('Ping_settings_id'); if(this.checked) node.style.display=''; else node.style.display='none';" /> Ping</td>
						</tr>
						<tr><td>
						<table id="Ping_settings_id" <?php if( !$isPing ) echo 'style="display:none"'; ?> bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel">Interval:</td><td class="fieldarea"><input type="text" size="15" name="interval" value="<?php echo MonitisConf::$settings['ping']['interval'] ?>" /></td></tr>
							<tr><td class="fieldlabel">Timeout:</td><td class="fieldarea"><input type="text" size="15" name="timeout" value="<?php echo MonitisConf::$settings['ping']['timeout'] ?>" /></td></tr>
<tr>
<td class="fieldlabel">Check locations:</td>
<td class="fieldarea">
	<div class="monitisMultiselect">
		<span class="monitisMultiselectText"><u>{count}</u> locations selected</span>
		<input type="button" class="monitisMultiselectTrigger" value="Select" />
		<div class="monitisMultiselectInputs" inputName="locationIDs[]">
			<?php 
			for($i=0; $i<count( $locationIds ); $i++) {
				echo '<input type="hidden" name="locationIDs[]" value="' . $locationIds[$i] . '"/>';
			}
			?>
		</div>
		<div class="monitisMultiselectDialog">
			<table style="width:100%;" cellpadding=10>
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
						</table>
						
						</td></tr>						
					</table>
					<table class="form monitisDataporps" border=0 width="100%">
						<tr class="">
							<th colspan=2 class="title">Internal Monitors 
							<input type="hidden" name="agentPlatform" value="<?=$newAgentPlatform?>" />
							</th>
						</tr>
						<tr>
							<td class="subtitle" width="50%"><input type="checkbox" name="newServerMonitors[]" value="cpu" <?php if( $isCPU ) echo 'checked=checked'; ?>
							onchange="var node=document.getElementById('CPU_settings_id'); if(this.checked) node.style.display=''; else node.style.display='none';" /> CPU</td>
							<td class="subtitle" width="50%"><input type="checkbox" name="newServerMonitors[]" value="memory" <?php if( $isMemory ) echo 'checked=checked'; ?> 
							onchange="var node=document.getElementById('Memory_settings_id'); if(this.checked) node.style.display=''; else node.style.display='none';" /> Memory</td>
						</tr>
<? 
$cpu = MonitisConf::$settings['cpu'][$newAgentPlatform];
$memory = MonitisConf::$settings['memory'][$newAgentPlatform];

if( $newAgentPlatform == 'LINUX' ) { 
?>
						<tr>
						<td width="50%" valign="top"><table id="CPU_settings_id" <?php if( !$isCPU ) echo 'style="display:none"'; ?> bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel" width="50%">User Max:</td><td class="fieldarea" width="50%"><input type="text" size="5" name="usedMax" value="<?=$cpu['usedMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Kernel Max:</td><td class="fieldarea"><input type="text" size="5" name="kernelMax" value="<?=$cpu['kernelMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Min allowed value for idle:</td><td class="fieldarea"><input type="text" size="5" name="idleMin" value="<?=$cpu['idleMin']?>" /></td></tr>
							<tr><td class="fieldlabel">Max value for iowait:</td><td class="fieldarea"><input type="text" size="5" name="ioWaitMax" value="<?=$cpu['ioWaitMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Max allowed value for nice:</td><td class="fieldarea"><input type="text" size="5" name="niceMax" value="<?=$cpu['niceMax']?>" /></td></tr>
						</table></td>
						<td width="50%" valign="top"><table id="Memory_settings_id" <?php if( !$isMemory ) echo 'style="display:none"'; ?> bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel" width="50%">Free memory limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit" value="<?=$memory['freeLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Free swap limit:</td><td class="fieldarea"><input type="text" size="5" name="freeSwapLimit" value="<?=$memory['freeSwapLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Buffered limit:</td><td class="fieldarea"><input type="text" size="5" name="bufferedLimit" value="<?=$memory['bufferedLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Cached memory limit:</td><td class="fieldarea"><input type="text" size="5" name="cachedLimit" value="<?=$memory['cachedLimit'] ?>" />&nbsp;MB</td></tr>
						</table></td>
						</tr>
<?}?>
					</table>
				</td>
			</tr>
			<tr>
				<td class="title" align="center">
					<input type="submit" value="Save" class="btn btn-primary" />
				</td>
			</tr>
		</table>
		<input type="hidden" name="saveConfig" value="1" />
	</form>
</center>