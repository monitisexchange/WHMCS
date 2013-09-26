<?php
include 'incs/servicetop.php';


class monitisAddonClass extends whmcs_db {

    public function __construct() {}
	
	public function addonsList() {
		$sql = 'SELECT id, name, mod_monitis_addon.*
		FROM tbladdons
		LEFT JOIN mod_monitis_addon on ( tbladdons.id = mod_monitis_addon.addon_id )';
		return $this->query_count( $sql );
	}
	
    public function deactivateAddon($addon_id) {
        //return update_query('mod_monitis_addon', array('is_active'=>0 ), array('addon_id'=>$addon_id));
		$sql = 'DELETE FROM mod_monitis_addon WHERE addon_id='.$addon_id;
		return $this->query_del( $sql );
    }
	
    public function insertAddon($addonId, $settings, $monitor_type) {
		//$values = array('addon_id'=>$addonId, 'type'=>$monitor_type, 'settings'=>$settings, 'is_active'=>1);
		$values = array('addon_id'=>$addonId, 'type'=>$monitor_type, 'settings'=>$settings, 'status'=>'active');
		return insert_query('mod_monitis_addon', $values);
    }
	
    public function updateAddonSettings($addonId, $settings, $type) {
        $value = array('settings' => $settings, 'type' => $type);
        $where = array('addon_id' => $addonId);
        return update_query('mod_monitis_addon', $value, $where);
    }
}


$oAddon = new monitisAddonClass();

$action = monitisPost('action');

 
//_dump($_POST);
if ($action ) {

	$monitor_type = monitisPost('monitor_type');
	$addonId = monitisPostInt('addonId');
	
	$timeout = monitisPostInt("timeout");

		$locs = explode(',', $_POST["locationIDs"]);
		$loc = array_map("intval", $locs);
		

		$setting = json_encode(MonitisConf::$settings);
		$setting = json_decode( $setting, true );
		$set = $setting[$monitor_type];

		$set['timeout'] = $timeout;
		$set['interval'] = $_POST["interval"]; 
		$set['locationIds'] = $loc;
		$set['locationsMax'] = (!$_POST["locationsMax"]) ? 0 : $_POST["locationsMax"];
		
		//$set['group'] = array( 'id'=>$_POST["groupid"], 'name'=>$_POST["groupname"] );
		
		$set['alertGroupId'] = $_POST["alertGroupId"];
		$rule = $_POST['alertRules'];
		$rule = str_replace("~", '"', $rule);
		$set["alertRules"] = json_decode( $rule, true);

		$new_setting = json_encode( $set );
	  
		switch ($action) {
			case 'activate':
				$oAddon->insertAddon($addonId, $new_setting, $monitor_type );
				//update_query('mod_monitis_addon', array('is_active'=>1 ), array('addon_id'=>$addonId));
			break;
			case 'update':
				$oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type );
			break;

			case 'deactivate':
				//update_query('mod_monitis_addon', array('is_active'=>0 ), array('addon_id'=>$addonId));
				 $oAddon->deactivateAddon($addonId );
			break;
			case 'automate':
				$oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type );
				header('location: ' . MONITIS_APP_URL . '&monitis_page=addonsResult&addonid='.$addonId);
			break;
			case 'create':
				$oAddon->insertAddon($addonId, $new_setting, $monitor_type );
			break;
		}
	
}

$addons = $oAddon->addonsList();
//_dump($addons);


MonitisApp::printNotifications();
?>
<script type="text/javascript">
$(document).ready(function(){
	$('.monitis-addons [name=monitor_type]').change(function(){
		var type = $(this).val();
		var from, to;
		if(type == 'ping'){
			from = 1;
			to = 5000;
		}
		else{
			from = 1000;
			to = 50000;
		}
		$(this).closest('tr').find('.from').text(from);
		$(this).closest('tr').find('.to').text(to);
		$(this).closest('tr').find('[name=timeout]').blur();
	});
	$('.monitis-addons [name=monitor_type]').change();
	$('.monitis-addons [name=timeout]').blur(function(){
		var val = parseInt($(this).val());
		if(isNaN(val)) val = 0;
		var from = $(this).parent().find('.from').text();
		var to = $(this).parent().find('.to').text();
		if(val < from){
			$(this).val(from);
		}
		else if(val > to){
			$(this).val(to);
		}
		else{
			$(this).val(val);
		}
	});
});
</script>

<table class="monitis-addons datatable" border=0 cellspacing="1" cellpadding="3">
    <tr>
        <th width="20px">&nbsp;</th>
        <th class="title" style="width:150px;">Addon Name</th>

        <th class="title" style="width:120px;">Monitor type</th>
        <th class="title" style="width:380px;">Monitor settings</th>
        <th>&nbsp;</th>
    </tr>
    <?
   
    if ($addons && count($addons) > 0) {

        $allTypes = explode(",", MONITIS_EXTERNAL_MONITOR_TYPES);

        for ($i = 0; $i < count($addons); $i++) {
            $addonId = $addons[$i]['id'];
			$prefix = $addonId;
            $monitor_type = $allTypes[0];
            
			$settings = null;
			$isActive = false;
			if (isset($addons[$i]["type"]) && !empty($addons[$i]["type"]) ) {
                $monitor_type = $addons[$i]["type"];
				$settings = $addons[$i]["settings"];
				$isActive = true;
            } 
            ?>
            <tr>
                <td style="width:20px;">&nbsp;</td>
                <td style="width:150px;border-right:solid 1px #ebebeb"><?php echo $addons[$i]['name'] ?></td>
                <td valign="center" align="left" colspan="4" style="padding:0px;">
                    <form method="post" action="">
                        <table cellspacing="1" cellpadding="6" border=0 style="width:100%;min-width:1000px">
                            <tr>     
                                <td class="type" width="120px">
                                    <select name="monitor_type" width="80px">
                                        <?

                                        for($a = 0; $a < count($allTypes); $a++) {
                                            $selected = ( $allTypes[$a] == $monitor_type) ? 'selected' : '';
                                            ?>
                                            <option class="type" value="<?=$allTypes[$a]?>" <?=$selected ?>><?=strtoupper($allTypes[$a])?></option> 
                                        <? } ?>
                                    </select>
                                </td>
                                <td width="320px" style="padding-left:10px;">
								
<? /* $settings, $monitor_type, $prefix */ 
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
					<table width="380px" cellspacing="1" cellpadding="3" border=0>
						<tr>
							<td class="fieldlabel" style="width: 100px;">Interval:</td>
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
							<td><input type="text" size="15" name="timeout" value="<?=$timeOut?>"/> (<span class="from">1000</span> — <span class="to">50000</span> ms.)</td>
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
							
                                </td>
                                <td class="actions">
                                    <? if ( $isActive ) { ?>

                                        <input type="hidden" name="action" value="update" />
                                        <div><input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'" class="btn btn-danger" /></div>
										<div><input type="submit" value="Create Monitors" onclick="this.form.action.value='automate'" class="btn" /></div>
										<div><input type="submit" value="Update" onclick="this.form.action.value = 'update'" class="btn" /></div>
                                    <? } else { ?>
                                        <div><input type="hidden" name="action" value="create" class="btn" /></div>
                                        <div><input type="submit" value="Activate" class="btn btn-success" /></div>
									<? } ?>
                                </td>
                            </tr>
							</table>
                        <input type="hidden" name="addonId" value="<?=$addonId?>" />
                    </form>
                </td>
            </tr>
        <?
        }
    }
    ?>
</table>
