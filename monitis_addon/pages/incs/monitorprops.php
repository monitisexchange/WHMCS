<?
		// $settings, $monitor_type, $prefix
	if( !isset( $monitor_type) ) {
		$monitor_type = 'ping';
	}
	$alertRules = MONITIS_NOTIFICATION_RULE;
	$loaction = '';
	$setts = $settings;
	if( !$setts || !isset( $setts) || empty($setts) ) {
		//$settings = MonitisConf::$settings;
		$setts = MonitisConf::$settings[$monitor_type];
	} else {
		$setts = json_decode( $setts, true );
		//$timeOut = $setts['timeout'];
	}

	$alertRules = $setts['alertRules'];
	if( !isset($alertRules) || empty($alertRules) ) {
		$alertRules = MONITIS_NOTIFICATION_RULE;
	} else {
		$alertRules = json_encode($alertRules);
	}
	
	$timeOut = $setts['timeout'];
	$group = MonitisApiHelper::alertGroupById( $setts['alertGroupId'], $groupList );
	
	if( !isset( $setts['locationIds']) || !empty($setts['locationIds'])) {
		$loaction = implode(',', $setts['locationIds']);
	}
	//
	$alertRules = str_replace('"', "~", $alertRules );
  //  $order_behavior = json_decode($addons[$i]["monitor"]['order_behavior'], true);
	
?>
<table width="330px" cellspacing="1" cellpadding="3" border=0>
	<tr>
		<td class="fieldlabel">Interval:</td>
		<td><select name="interval">
		<?	$newInterval = $setts['interval'];

			$aInterval = explode(',', MonitisConf::$checkInterval);
			for ($int = 0; $int < count($aInterval); $int++) {
				if ($aInterval[$int] == $newInterval) { ?>
					<option value="<?= $aInterval[$int] ?>" selected ><?=$aInterval[$int]?></option>
				<? } else { ?>
					<option value="<?= $aInterval[$int] ?>"><?=$aInterval[$int]?></option>
				<? }
			}
			//$timeOut = $setts['timeout'];
			//if ($monitor_type != 'ping') {
			//	$timeOut = $timeOut*1000;
			//}	
		   ?>
			</select>&nbsp;min.
		</td>
	</tr>
	<tr>
		<td class="fieldlabel">Timeout:</td>
		<td><input type="text" size="15" name="timeout" id="timeout" value="<?=$timeOut?>"/>ms.</td>
	</tr>
	<tr>
		<td class="fieldlabel">Max locations:</td>
		<td><input type="text" size="15" id="locationsMax<?=$prefix?>"  name="locationsMax" value="<?=$setts['locationsMax']?>" /></td>
	</tr>
	<tr class="monitisMultiselect">
		<td class="fieldlabel" >Check locations:</td>
		<td>
			<label><span class="monitisMultiselectText" id="locationsize<?=$prefix?>" ><?php echo sizeof($setts['locationIds']); ?></span> locations</label>
			<input type="button" class="monitisMultiselectTrigger" value="Select" element_prefix="<?=$prefix?>" />
			<input type="hidden" name="locationIDs" id="locationIDs<?=$prefix?>" value="<?=$loaction?>" />
		</td>
	</tr>
	
	<tr  style="display:none">
		 <td class="fieldlabel" >Alert:</td>
		<td>
			<input type="button" class="notificationRule" value="<?=$group['title']?>" product="<?=$prefix?>" />
			<input type="hidden" name="alertGroupId" class="notificationGroupId_<?=$prefix?>" value="<?=$group['id']?>" />
			<input type="hidden" name="groupname" class="notificationGroupName_<?=$prefix?>" value="<?=$group['name']?>" />
			<input type="hidden" name="alertRules" id="notifications<?=$prefix?>" class="notificationRule_<?=$prefix?>" value='<?=$alertRules?>' />
		</td>
	</tr>
</table>
									