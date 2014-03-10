<?php
define('MONITIS_CPU_MONITOR_PROPS_TPL', '{
	"LINUX":{"usedMax":"n","kernelMax":"n","idleMin":"n","ioWaitMax":"n","niceMax":"n"},
	"WINDOWS":{"usedMax":"n","kernelMax":"n"},
	"OPENSOLARIS":{"usedMax":"n","kernelMax":"n"}
}');

define('MONITIS_MEMORY_MONITOR_PROPS_TPL', '{
	"LINUX":{"freeLimit":"n","freeSwapLimit":"n","bufferedLimit":"n","cachedLimit":"n"},
	"WINDOWS":{"freeLimit":"n","freeSwapLimit":"n","freeVirtualLimit":"n"},
	"OPENSOLARIS":{"freeLimit":"n","freeSwapLimit":"n"}
}');



$isNewAcc = monitisGetInt('isNewAcc');

$locations = MonitisConf::$locations;

$newAgentPlatform = MonitisConf::$newAgentPlatform;
$old_ping = MonitisConf::$settings['ping'];
$old_cpu = MonitisConf::$settings['cpu'][$newAgentPlatform];
$old_memory = MonitisConf::$settings['memory'][$newAgentPlatform];
$old_drive = MonitisConf::$settings['drive'];

$locationIds = $old_ping['locationIds'];
$action_type = monitisPost('action_type');



if ($action_type == 'saveConfig') {

	if (isset($_POST['locationIDs']) && !empty($_POST['locationIDs'])) {
		$arr = $_POST['locationIDs'];
		$locationIds = array_map("intval", $arr);
	}

	$platform = $_POST['agentPlatform'];

	$newsets = MonitisConf::$settings;
	// PING monitor settings
	$newsets['ping']['interval'] = isset($_POST['interval']) ? intval($_POST['interval']) : $old_ping['interval'];
	$newsets['ping']['timeout'] = isset($_POST['timeout']) ? intval($_POST['timeout']) : $old_ping['timeout'];
	$newsets['ping']['locationIds'] = $locationIds;


	// CPU monitor settings
	$tpl = json_decode(MONITIS_CPU_MONITOR_PROPS_TPL, true);
	foreach ($tpl[$platform] as $key => $val) {
		$newsets['cpu'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_cpu[$key];
	}


	// MEMORY monitor settings
	$tpl = json_decode(MONITIS_MEMORY_MONITOR_PROPS_TPL, true);
	foreach ($tpl[$platform] as $key => $val) {
		$newsets['memory'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_memory[$key];
	}

	// DRIVE monitor settings
	$newsets['drive']['freeLimit'] = isset($_POST['freeLimit_drive']) ? intval($_POST['freeLimit_drive']) : $old_drive['freeLimit'];

	$mtypes = explode(",", MONITIS_ADMIN_MONITOR_TYPES);

	$mprops = array('autocreate', 'available', 'suspendmsg');

	for ($i = 0; $i < count($mtypes); $i++) {

		$mtype = $mtypes[$i];

		for ($p = 0; $p < count($mprops); $p++) {
			if ($mprops[$p] == 'suspendmsg')
				$newsets[$mtype][$mprops[$p]] = (!isset($_POST[$mprops[$p] . '_' . $mtype]) ) ? 'Monitor suspended' : $_POST[$mprops[$p] . '_' . $mtype];
			else
				$newsets[$mtype][$mprops[$p]] = (!isset($_POST[$mprops[$p] . '_' . $mtype]) ) ? 0 : 1;
		}
	}

	$newsets_json = json_encode($newsets);
	MonitisConf::update_settings($newsets_json);


	if ($action_type == 'applyAll') {
		$oNot = new notificationsClass();
		$oNot->autoApplyAlertsToAll($_POST['apply_monitor_type']);
	}

	if ($isNewAcc)
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=syncservers');
} else {
	if ($isNewAcc)
		MonitisApp::addMessage('Default monitor settings that will apply to newly provisioned servers.');
}


$ping = MonitisConf::$settings['ping'];
$cpu = MonitisConf::$settings['cpu'][$newAgentPlatform];
$memory = MonitisConf::$settings['memory'][$newAgentPlatform];
$drive = MonitisConf::$settings['drive'];


$firstTime = ($isNewAcc > 0) ? '&isNewAcc=1' : '';


MonitisApp::printNotifications();
?>
<style type="text/css">
.form .title {
	width:70%;
	text-align:left;
	font-size:1.1em;
	font-weight: bold;
	color: #555555;
	padding: 20px 0px 10px 0px;
}
.form .fieldarea hr {
	width:30%;
	float:left;
}
</style>

<form action="<?php echo MONITIS_APP_URL ?>&monitis_page=tabadmin&sub=settings<?php echo $firstTime ?>" method="post" enctype="application/x-www-form-urlencoded">

	<table class="form" width="100%" border=0>
		<tr><td style="width:30%">&nbsp;</td><td class="title">Ping</td></tr>
		<tr>
			<td class="fieldlabel">Automate monitor creation:</td>
			<td  class="fieldarea">
				<input type="checkbox" name="autocreate_ping" value="ping" <?php if ($ping['autocreate'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Create a monitor when a new server is added</label>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Available to clients:</td>
			<td class="fieldarea">
				<input type="checkbox" name="available_ping" value="ping" <?php if ($ping['available'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Monitor results will be available in client area</label>
			</td>
		</tr>

		<tr style="display:none"><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
				<input type="text" name="suspendmsg_ping" value="<?php echo $ping['suspendmsg'] ?>" class="monitortypeswitcher" size="30" /></td></tr>

		<tr><td>&nbsp;</td><td class="fieldarea"><hr /></td></tr>

		<tr><td class="fieldlabel">Check interval:</td><td class="fieldarea">
				<select name="interval">
					<?php
					$aInterval = explode(',', MonitisConf::$checkInterval);
					for ($i = 0; $i < count($aInterval); $i++) {
						if ($aInterval[$i] == $ping['interval']) {
							?>
							<option value="<?php echo $aInterval[$i] ?>" selected ><?php echo $aInterval[$i] ?></option>
						<?php } else { ?>
							<option value="<?php echo $aInterval[$i] ?>"><?php echo $aInterval[$i] ?></option>
							<?php
						}
					}
					?>
				</select>&nbsp;min.
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Timeout:</td>
			<td class="fieldarea">
				<input type="text" size="15" name="timeout" value="<?php echo $ping['timeout'] ?>" />&nbsp;ms. &nbsp;(1-5000)
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check locations:</td>
			<td class="fieldarea">
				<div class="monitisMultiselect">
					<span class="monitisMultiselectText"><u>{count}</u> selected</span>
					<input type="button" class="monitisMultiselectTrigger btn" value="Select" />
					<div class="monitisMultiselectInputs" inputName="locationIDs[]">
						<?php
						for ($i = 0; $i < count($locationIds); $i++) {
							echo '<input type="hidden" name="locationIDs[]" value="' . $locationIds[$i] . '"/>';
						}
						?>
					</div>
					<div class="monitisMultiselectDialog"  title="Monitoring locations">
						<table style="width:100%;" cellpadding=10>
							<tr>
								<?php foreach ($locations as $countryName => $country) { ?>
									<td style="vertical-align: top;">
										<div class="column-title">
											<?php echo $countryName ?>
										</div>
										<hr/>
										<?php foreach ($country as $location) { ?>
											<div>
												<input type="checkbox" name="locationIDs[]" value="<?php echo $location['id']?>">
												<?php echo $location['fullName'] ?>
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

		<tr><td style="width:30%">&nbsp;</td><td class="title">CPU</td></tr>
		<tr>
			<td class="fieldlabel">Automate monitor creation:</td>
			<td  class="fieldarea">
				<input type="checkbox" name="autocreate_cpu" value="cpu" <?php if (MonitisConf::$settings['cpu']['autocreate'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Create a monitor when a new server is added</label>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Available to clients:</td>
			<td class="fieldarea">
				<input type="checkbox" name="available_cpu" value="cpu" <?php if (MonitisConf::$settings['cpu']['available'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Monitor results will be available in client area</label>
			</td>
		</tr>


		<tr style="display:none"><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
				<input type="text" name="suspendmsg_cpu" value="<?php echo MonitisConf::$settings['cpu']['suspendmsg'] ?>" class="monitortypeswitcher" size="30" /></td></tr>


		<tr><td>&nbsp;</td><td class="fieldarea"><hr /></td></tr>

		<tr><td class="fieldlabel">User Max:</td><td class="fieldarea" width="50%"><input type="text" size="5" name="usedMax" value="<?php echo $cpu['usedMax']?>" /></td></tr>
		<tr><td class="fieldlabel">Kernel Max:</td><td class="fieldarea"><input type="text" size="5" name="kernelMax" value="<?php echo $cpu['kernelMax']?>" /></td></tr>
		<tr><td class="fieldlabel">Idle Min:</td><td class="fieldarea"><input type="text" size="5" name="idleMin" value="<?php echo $cpu['idleMin'] ?>" /></td></tr>
		<tr><td class="fieldlabel">Iowait Max:</td><td class="fieldarea"><input type="text" size="5" name="ioWaitMax" value="<?php echo $cpu['ioWaitMax'] ?>" /></td></tr>
		<tr><td class="fieldlabel">Nice Max:</td><td class="fieldarea"><input type="text" size="5" name="niceMax" value="<?php echo $cpu['niceMax'] ?>" /></td></tr>


		<tr><td style="width:30%">&nbsp;</td><td class="title">Memory</td></tr>
		<tr>
			<td class="fieldlabel">Automate monitor creation:</td>
			<td class="fieldarea">
				<input type="checkbox" name="autocreate_memory" value="memory" <?php if (MonitisConf::$settings['memory']['autocreate'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Create a monitor when a new server is added</label>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Available to clients:</td>
			<td class="fieldarea">
				<input type="checkbox" name="available_memory" value="memory" <?php if (MonitisConf::$settings['memory']['available'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Monitor results will be available in client area</label>
			</td>
		</tr>

		<tr style="display:none"><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
				<input type="text" name="suspendmsg_memory" value="<?php echo MonitisConf::$settings['memory']['suspendmsg'] ?>" class="monitortypeswitcher" size="30" /></td></tr>


		<tr><td>&nbsp;</td><td class="fieldarea"><hr /></td></tr>


		<tr><td class="fieldlabel">Free Limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit" value="<?php echo $memory['freeLimit'] ?>" />&nbsp;MB</td></tr>
		<tr><td class="fieldlabel">Swap Limit:</td><td class="fieldarea"><input type="text" size="5" name="freeSwapLimit" value="<?php echo $memory['freeSwapLimit'] ?>" />&nbsp;MB</td></tr>
		<tr><td class="fieldlabel">Buffered Limit:</td><td class="fieldarea"><input type="text" size="5" name="bufferedLimit" value="<?php echo $memory['bufferedLimit'] ?>" />&nbsp;MB</td></tr>
		<tr><td class="fieldlabel">Cached Limit:</td><td class="fieldarea"><input type="text" size="5" name="cachedLimit" value="<?php echo $memory['cachedLimit'] ?>" />&nbsp;MB</td></tr>
		<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>


		<tr><td style="width:30%">&nbsp;</td><td class="title">Drive</td></tr>
		<tr>
			<td class="fieldlabel">Automate monitor creation:</td>
			<td class="fieldarea">
				<input type="checkbox" name="autocreate_drive" value="drive"  class="monitortypeswitcher" disabled />
				<label>Create a monitor when a new server is added</label>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Available to clients:</td>
			<td class="fieldarea">
				<input type="checkbox" name="available_drive" value="drive" <?php if ($drive['available'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" />
				<label>Monitor results will be available in client area</label>
			</td>
		</tr>

		<tr style="display:none"><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
				<input type="text" name="suspendmsg_drive" value="<?php echo $drive['suspendmsg'] ?>" class="monitortypeswitcher" size="30" /></td></tr>

		<tr><td>&nbsp;</td><td class="fieldarea"><hr /></td></tr>


		<tr><td class="fieldlabel">Free Limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit_drive" value="<?php echo $drive['freeLimit'] ?>" />&nbsp;GB</td></tr>
		<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>

		<tr><td style="width:30%">&nbsp;</td><td class="title"><input type="submit" value="Save" class="btn monitis_link_button" /></td></tr>
	</table>


	<input type="hidden" name="agentPlatform" value="<?php echo $newAgentPlatform ?>" />
	<input type="hidden" name="saveConfig" value="1" />
	<input type="hidden" name="action_type" value="saveConfig" />
	<input type="hidden" name="apply_monitor_type" value="" />
</form>
<div id="monitis_notification_dialog_div"></div>
