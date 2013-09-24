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

	$addonId = monitisPostInt('addonId');    
	$monitor_type = monitisPost('monitor_type');

	$locs = explode(',', $_POST["locationIDs"]);
	$loc = array_map("intval", $locs);
	
	$timeout = monitisPostInt("timeout");
	if ($timeout > 5000) {
		$timeout = 5000;
	}
	
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

?>
<table class="datatable" border=0 cellspacing="1" cellpadding="3">
    <tr>
        <th width="20px">&nbsp;</th>
        <th class="title" style="width:150px;">Addon Name</th>

        <th class="title" style="width:120px;">Monitor type</th>
        <th class="title" style="width:320px;">Monitor settings</th>
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
								include 'incs/monitorprops.php'; ?>
							
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
