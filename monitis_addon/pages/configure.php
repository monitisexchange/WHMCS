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
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}

$newAgentPlatform = MonitisConf::$newAgentPlatform;
$monitorAvailable = MonitisConf::$monitorAvailable;
$maxLocations = MonitisConf::$maxLocations;

$old_ping =  MonitisConf::$settings['ping'];
$old_cpu =  MonitisConf::$settings['cpu'][$newAgentPlatform];
$old_memory =  MonitisConf::$settings['memory'][$newAgentPlatform];
$old_drive = MonitisConf::$settings['drive'];

$locationIds = $old_ping['locationIds'];

 
//_dump($tpl);

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

	// PING monitor settings
	$newsets['ping'] = array(
		'interval'	=>	isset($_POST['interval']) ? intval($_POST['interval']) : $old_ping['interval'],
		'timeout'	=>	isset($_POST['timeout']) ? intval($_POST['timeout']) : $old_ping['timeout'],
		'locationIds'	=>	$locationIds //isset($_POST['locationIds']) ? explode(',', $_POST['locationIds']) : $old_ping['locationIds']
	);
	$newsets['ping']['available'] = (!isset($_POST['available_ping']) ) ? 0 : 1;
	
	
	// CPU monitor settings
	$tpl = json_decode( MONITIS_CPU_MONITOR_PROPS_TPL, true);
	foreach( $tpl[$platform] as $key=>$val ) {
		$newsets['cpu'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_cpu[$key];
	}
	$newsets['cpu']['available'] = (!isset($_POST['available_cpu']) ) ? 0 : 1;
	
	
	
	// MEMORY monitor settings
	$tpl = json_decode( MONITIS_MEMORY_MONITOR_PROPS_TPL, true);
	foreach( $tpl[$platform] as $key=>$val ) {
		$newsets['memory'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_memory[$key];
	}
	$newsets['memory']['available'] = (!isset($_POST['available_memory']) ) ? 0 : 1;
	
	// DRIVE monitor settings
	$newsets['drive'] = array(
		'freeLimit'	=>	isset($_POST['freeLimit_drive']) ? intval($_POST['freeLimit_drive']) : $old_drive['freeLimit']
	);
	$newsets['drive']['available'] = (!isset($_POST['available_drive']) ) ? 0 : 1;
	
	
	//$monitorAvailable = (!isset($_POST['monitorAvailable']) ) ? 0 : $_POST['monitorAvailable'];
	//$newsets['available'] = $monitorAvailable;
	
	//$maxLocations = (!isset($_POST['max_locations']) ) ? 0 : $_POST['max_locations'];
	//$newsets['max_locations'] = $maxLocations;
//_dump($newsets);
	
	$newsets_json = json_encode($newsets);
	//$oldsets_json = json_encode(MonitisConf::$settings);
	$result = array();
	


	//if( $newsets_json != $oldsets_json ) {
	$result['settings'] = $newsets_json;
	//}
	//$result['monitorAvailable'] = $monitorAvailable;

	
	if ($saveNewServerMonitors != MonitisConf::$newServerMonitors) {
		$result['newServerMonitors'] = $saveNewServerMonitors;
	}
	
//	if( $result && count($result) > 0) {
//_dump($result);

	MonitisConf::update_settings( MONITIS_CLIENT_ID, $result );
	
	
//	}
	
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
//$isDrive = false;

if(in_array('ping', $newServerMonitors))	$isPing = true;
if(in_array('cpu', $newServerMonitors))	$isCPU = true;
if(in_array('memory', $newServerMonitors))	$isMemory = true;
//if(in_array('drive', $newServerMonitors))	$isDrive = true;

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
	line-height:26px;
}
</style>
<script>
/*
$(document).ready(function(){

	$(".form .monitortypeswitcher").change(function(){
		var divid = $(this).attr('divid');
		if( $(this).prop("checked") )
			$("#"+divid).slideDown("slow");	// $("#"+divid).fadeIn("slow");	//
		else
			$("#"+divid).slideUp("slow");		//$("#"+divid).fadeOut("slow"); // 
	});
});
*/

</script>
<center>
	<form action="" method="post">
		<table class="form" width="100%" cellspacing=2 cellpadding=3>
			
			<!-- tr><th class="title">Available to customer:&nbsp;&nbsp;<input type="checkbox" name="monitorAvailable" value="1" <? if($monitorAvailable > 0) echo 'checked=checked' ?> /></th></tr>
			<tr><th class="title">Maximum locations:&nbsp;&nbsp;<input type="text" name="max_locations" value="<?=$maxLocations?>" size="5" /></th></tr -->

			<tr>
				<td class="fieldarea11" style="text-align:center;">	
					<table class="form monitisDataporps" border=0 width="100%">
						<tr class="">
							<th colspan=3 class="title">External Monitors</th>
						</tr>
						<tr>
							<td colspan="3" class="subtitle">Ping</td>
						</tr>
						<tr><td>
						<div id="Ping_settings_id">
						<table   bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel">Automate:</td><td  class="fieldarea">
								<input type="checkbox" name="newServerMonitors[]" value="ping" <?php if(in_array('ping', $newServerMonitors)) echo 'checked=checked'; ?> divid="Ping_settings_id" class="monitortypeswitcher"  />
							</td></tr>
							
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_ping" value="ping" <?php if(in_array('ping', $newServerMonitors) && MonitisConf::$settings['ping']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
							
							<tr><td class="fieldlabel">Interval:</td><td class="fieldarea">
							<select name="interval">
								<?
								$aInterval = explode(',', MonitisConf::$checkInterval);
								for($i=0; $i<count($aInterval); $i++) {
									if($aInterval[$i] == MonitisConf::$settings['ping']['interval'] ) {
								?>
									<option value="<?=$aInterval[$i]?>" selected ><?=$aInterval[$i]?></option>
								<?	} else { ?>
									<option value="<?=$aInterval[$i]?>"><?=$aInterval[$i]?></option>
								<?	}
								}?>
								</select>&nbsp;min.
								<!-- input type="text" size="15" name="interval" value="<?php echo MonitisConf::$settings['ping']['interval'] ?>" / -->
							</td></tr>
							<tr><td class="fieldlabel">Timeout:</td><td class="fieldarea"><input type="text" size="15" name="timeout" value="<?php echo MonitisConf::$settings['ping']['timeout'] ?>" />&nbsp;ms. &nbsp;(1-5000)</td></tr>
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
						</div>
						</td></tr>						
					</table>
					<table class="form monitisDataporps" border=0 width="100%">
						<tr class="">
							<th colspan=2 class="title">Internal Monitors 
							<input type="hidden" name="agentPlatform" value="<?=$newAgentPlatform?>" />
							</th>
						</tr>
						<tr>
							<td class="subtitle" width="33%">CPU</td>
							<td class="subtitle" width="33%">Memory</td>
							<td class="subtitle" width="33%">Drive</td>
						</tr>
<? 
$cpu = MonitisConf::$settings['cpu'][$newAgentPlatform];
$memory = MonitisConf::$settings['memory'][$newAgentPlatform];
$drive = MonitisConf::$settings['drive'];

if( $newAgentPlatform == 'LINUX' ) { 
?>
						<tr>
						<td width="33%" valign="top"><div id="CPU_settings_id" ><table bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel" width="50%">Automate:</td><td  class="fieldarea"><input type="checkbox" name="newServerMonitors[]" value="cpu" <?php if( $isCPU ) echo 'checked=checked'; ?> class="monitortypeswitcher"  /></td></tr>
							
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_cpu" value="cpu" <?php if(in_array('cpu', $newServerMonitors) && MonitisConf::$settings['cpu']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
					

							<tr><td class="fieldlabel" width="50%">User Max:</td><td class="fieldarea" width="50%"><input type="text" size="5" name="usedMax" value="<?=$cpu['usedMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Kernel Max:</td><td class="fieldarea"><input type="text" size="5" name="kernelMax" value="<?=$cpu['kernelMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Min allowed value for idle:</td><td class="fieldarea"><input type="text" size="5" name="idleMin" value="<?=$cpu['idleMin']?>" /></td></tr>
							<tr><td class="fieldlabel">Max value for iowait:</td><td class="fieldarea"><input type="text" size="5" name="ioWaitMax" value="<?=$cpu['ioWaitMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Max allowed value for nice:</td><td class="fieldarea"><input type="text" size="5" name="niceMax" value="<?=$cpu['niceMax']?>" /></td></tr>
						</table></div></td>
						<td width="33%" valign="top"><div id="Memory_settings_id" ><table bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel" width="50%">Automate:</td><td  class="fieldarea"><input type="checkbox" name="newServerMonitors[]" value="memory" <?php if( $isMemory ) echo 'checked=checked'; ?> class="monitortypeswitcher"  /></td></tr>
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_memory" value="memory" <?php if(in_array('memory', $newServerMonitors) && MonitisConf::$settings['memory']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
						
							
							<tr><td class="fieldlabel" width="50%">Free memory limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit" value="<?=$memory['freeLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Free swap limit:</td><td class="fieldarea"><input type="text" size="5" name="freeSwapLimit" value="<?=$memory['freeSwapLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Buffered limit:</td><td class="fieldarea"><input type="text" size="5" name="bufferedLimit" value="<?=$memory['bufferedLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Cached memory limit:</td><td class="fieldarea"><input type="text" size="5" name="cachedLimit" value="<?=$memory['cachedLimit'] ?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>
						</table></div></td>
						<td width="33%" valign="top"><div id="Drive_settings_id" ><table bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_drive" value="drive" <?php if( MonitisConf::$settings['drive']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
						
							<tr><td class="fieldlabel" width="50%">Free memory limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit_drive" value="<?=$drive['freeLimit']?>" />&nbsp;GB</td></tr>
							<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>
						</table></div></td>
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