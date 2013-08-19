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

//_logActivity("load_config **** action = <b>load_config</b><p>".$row['settings']."</p>");

$newAgentPlatform = MonitisConf::$newAgentPlatform;

$old_ping =  MonitisConf::$settings['ping'];
$old_cpu =  MonitisConf::$settings['cpu'][$newAgentPlatform];
$old_memory =  MonitisConf::$settings['memory'][$newAgentPlatform];
$old_drive = MonitisConf::$settings['drive'];

$locationIds = $old_ping['locationIds'];

if (monitisPost('saveConfig')) {
	
	if( isset($_POST['locationIDs']) && !empty( $_POST['locationIDs']) ) {
		$arr = $_POST['locationIDs'];
		$locationIds = array_map( "intval", $arr );
	}
	
	$platform = $_POST['agentPlatform'];
	
	$new = json_encode(MonitisConf::$settings);
	$newsets = json_decode($new, true);

	// PING monitor settings
	$newsets['ping'] = array(
		'interval'	=>	isset($_POST['interval']) ? intval($_POST['interval']) : $old_ping['interval'],
		'timeout'	=>	isset($_POST['timeout']) ? intval($_POST['timeout']) : $old_ping['timeout'],
		'locationIds'	=>	$locationIds 
	);
	
	// CPU monitor settings
	$tpl = json_decode( MONITIS_CPU_MONITOR_PROPS_TPL, true);
	foreach( $tpl[$platform] as $key=>$val ) {
		$newsets['cpu'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_cpu[$key];
	}
	
	
	// MEMORY monitor settings
	$tpl = json_decode( MONITIS_MEMORY_MONITOR_PROPS_TPL, true);
	foreach( $tpl[$platform] as $key=>$val ) {
		$newsets['memory'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $old_memory[$key];
	}

	
	// DRIVE monitor settings
	$newsets['drive'] = array(
		'freeLimit'	=>	isset($_POST['freeLimit_drive']) ? intval($_POST['freeLimit_drive']) : $old_drive['freeLimit']
	);
	/*//$newsets['drive']['autocreate'] = (!isset($_POST['autocreate_drive']) ) ? 0 : 1;
	$newsets['drive']['autolink'] = (!isset($_POST['autolink_drive']) ) ? 0 : 1;
	$newsets['drive']['available'] = (!isset($_POST['available_drive']) ) ? 0 : 1;
	$newsets['drive']['suspendmsg'] = (!isset($_POST['suspendmsg_drive']) ) ? 0 : 1;*/
	
	$mtypes = array('ping','cpu','memory','drive');
	$mprops = array('autocreate','autolink','available','suspendmsg');
	for($i=0; $i<count($mtypes); $i++){
		for($p=0; $p<count($mprops); $p++){
			if( $mprops[$p] == 'suspendmsg' )
				$newsets[$mtypes[$i]][$mprops[$p]] = (!isset($_POST[$mprops[$p].'_'.$mtypes[$i]]) ) ? 'Monitor suspended' : $_POST[$mprops[$p].'_'.$mtypes[$i]];
			else
				$newsets[$mtypes[$i]][$mprops[$p]] = (!isset($_POST[$mprops[$p].'_'.$mtypes[$i]]) ) ? 0 : 1;
		}
	}
	
	$newsets_json = json_encode($newsets);

	MonitisConf::update_settings( MONITIS_CLIENT_ID, $newsets_json );
	

	if ($isNewAcc)
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=syncExistingServers');

} else {
	if ($isNewAcc)
		MonitisApp::addMessage('Now please review plugin settings and click on "Save" button');
}

$ping = MonitisConf::$settings['ping'];
$cpu = MonitisConf::$settings['cpu'][$newAgentPlatform];
$memory = MonitisConf::$settings['memory'][$newAgentPlatform];
$drive = MonitisConf::$settings['drive'];


//_dump($ping);
MonitisApp::printNotifications(); 
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

						<table   bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel">Automate monitor creation:</td><td  class="fieldarea">
								<input type="checkbox" name="autocreate_ping" value="ping" <?php if( $ping['autocreate'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							<tr><td class="fieldlabel">Link monitor:</td><td  class="fieldarea">
								<input type="checkbox" name="autolink_ping" value="ping" <?php if( $ping['autolink'] > 0) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_ping" value="ping" <?php if( $ping['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							<tr><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
								<input type="text" name="suspendmsg_ping" value="<?=$ping['suspendmsg']?>" class="monitortypeswitcher" size="30" /></td></tr>
								
							<tr><td>&nbsp;</td><td class="fieldlabel"><hr /></td></tr>
							
							<tr><td class="fieldlabel">Interval:</td><td class="fieldarea">
							<select name="interval">
								<?
								$aInterval = explode(',', MonitisConf::$checkInterval);
								for($i=0; $i<count($aInterval); $i++) {
									if($aInterval[$i] == $ping['interval'] ) {
								?>
									<option value="<?=$aInterval[$i]?>" selected ><?=$aInterval[$i]?></option>
								<?	} else { ?>
									<option value="<?=$aInterval[$i]?>"><?=$aInterval[$i]?></option>
								<?	}
								}?>
								</select>&nbsp;min.
							</td></tr>
							<tr><td class="fieldlabel">Timeout:</td><td class="fieldarea"><input type="text" size="15" name="timeout" value="<?php echo $ping['timeout'] ?>" />&nbsp;ms. &nbsp;(1-5000)</td></tr>
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
							<td class="subtitle" width="33%">CPU</td>
							<td class="subtitle" width="33%">Memory</td>
							<td class="subtitle" width="33%">Drive</td>
						</tr>
<? 
if( $newAgentPlatform == 'LINUX' ) { 
?>
						<tr>
						<td width="33%" valign="top"><table bgcolor="#ffffff" width="100%">
							
							<tr><td class="fieldlabel" width="50%">Automate monitor creation:</td><td  class="fieldarea">
								<input type="checkbox" name="autocreate_cpu" value="cpu" <? if( MonitisConf::$settings['cpu']['autocreate'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							<tr><td class="fieldlabel" width="50%">Link monitor:</td><td  class="fieldarea">
								<input type="checkbox" name="autolink_cpu" value="cpu" <? if( MonitisConf::$settings['cpu']['autolink'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_cpu" value="cpu" <? if( MonitisConf::$settings['cpu']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher" /></td></tr>
							
							<tr><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
								<input type="text" name="suspendmsg_cpu" value="<?=MonitisConf::$settings['cpu']['suspendmsg']?>" class="monitortypeswitcher" size="30" /></td></tr>
							
							<tr><td>&nbsp;</td><td class="fieldlabel"><hr /></td></tr>
							
							<tr><td class="fieldlabel" width="50%">User Max:</td><td class="fieldarea" width="50%"><input type="text" size="5" name="usedMax" value="<?=$cpu['usedMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Kernel Max:</td><td class="fieldarea"><input type="text" size="5" name="kernelMax" value="<?=$cpu['kernelMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Min allowed value for idle:</td><td class="fieldarea"><input type="text" size="5" name="idleMin" value="<?=$cpu['idleMin']?>" /></td></tr>
							<tr><td class="fieldlabel">Max value for iowait:</td><td class="fieldarea"><input type="text" size="5" name="ioWaitMax" value="<?=$cpu['ioWaitMax']?>" /></td></tr>
							<tr><td class="fieldlabel">Max allowed value for nice:</td><td class="fieldarea"><input type="text" size="5" name="niceMax" value="<?=$cpu['niceMax']?>" /></td></tr>
						</table>
						</td>
						<td width="33%" valign="top"><table bgcolor="#ffffff" width="100%">
							
							<tr><td class="fieldlabel" width="50%">Automate monitor creation:</td><td class="fieldarea">
								<input type="checkbox" name="autocreate_memory" value="memory" <? if( MonitisConf::$settings['memory']['autocreate'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  /></td></tr>
							<tr><td class="fieldlabel" width="50%">Link monitor:</td><td class="fieldarea">
								<input type="checkbox" name="autolink_memory" value="memory" <? if( MonitisConf::$settings['memory']['autolink'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  /></td></tr>
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_memory" value="memory" <?php if( MonitisConf::$settings['memory']['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
							<tr><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
								<input type="text" name="suspendmsg_memory" value="<?=MonitisConf::$settings['memory']['suspendmsg']?>" class="monitortypeswitcher" size="30" /></td></tr>
								
							<tr><td>&nbsp;</td><td class="fieldlabel"><hr /></td></tr>
							<tr><td class="fieldlabel" width="50%">Free memory limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit" value="<?=$memory['freeLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Free swap limit:</td><td class="fieldarea"><input type="text" size="5" name="freeSwapLimit" value="<?=$memory['freeSwapLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Buffered limit:</td><td class="fieldarea"><input type="text" size="5" name="bufferedLimit" value="<?=$memory['bufferedLimit']?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">Cached memory limit:</td><td class="fieldarea"><input type="text" size="5" name="cachedLimit" value="<?=$memory['cachedLimit'] ?>" />&nbsp;MB</td></tr>
							<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>
						</table>
						</td>
						
						
						<td width="33%" valign="top"><table bgcolor="#ffffff" width="100%">
							<tr><td class="fieldlabel" width="50%">Automate monitor creation:</td><td class="fieldarea">
								<input type="checkbox" name="autocreate_drive" value="drive"  class="monitortypeswitcher" disabled /></td></tr>
							<tr><td class="fieldlabel" width="50%">Link monitor:</td><td class="fieldarea">
								<input type="checkbox" name="autolink_drive" value="drive" <? if( $drive['autolink'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  /></td></tr>
							<tr><td class="fieldlabel">Available to customer:</td><td class="fieldarea">
								<input type="checkbox" name="available_drive" value="drive" <?php if( $drive['available'] > 0 ) echo 'checked=checked'; ?> class="monitortypeswitcher"  />
							</td></tr>
							<tr><td class="fieldlabel">Suspended monitor message:</td><td class="fieldarea">
								<input type="text" name="suspendmsg_drive" value="<?=$drive['suspendmsg']?>" class="monitortypeswitcher" size="30" /></td></tr>

							<tr><td>&nbsp;</td><td class="fieldlabel"><hr /></td></tr>
							<tr><td class="fieldlabel" width="50%">Free memory limit:</td><td  class="fieldarea"><input type="text" size="5" name="freeLimit_drive" value="<?=$drive['freeLimit']?>" />&nbsp;GB</td></tr>
							<tr><td class="fieldlabel">&nbsp;</td><td class="fieldarea">&nbsp;</td></tr>
						</table>
						</td>
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