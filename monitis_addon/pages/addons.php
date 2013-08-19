<?php
MonitisApp::printNotifications();
$order_title = json_decode(MONITIS_ORDER_BEHAVIOR_TITLE, true);
$default_order_behavior = json_decode(MONITIS_ORDER_BEHAVIOR, true);
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
    if (empty($value))
        unset($locations[$key]);
}

require_once ('../modules/addons/monitis_addon/lib/product.class.php');

class addonClass extends WHMCS_product_db {

    public function __construct() {
        
    }

    private function isMonitorType($addon_id, & $maddons) {
        for ($i = 0; $i < count($maddons); $i++) {
            if ($maddons[$i]['addon_id'] == $addon_id)
                return $maddons[$i];
        }
        return null;
    }

    public function addonsList() {
        $all = $this->allAddons();
        $maddons = $this->whmcsAddons();
        for ($i = 0; $i < count($all); $i++) {
            $info = $this->isMonitorType($all[$i]['id'], $maddons);
            $all[$i]['monitor'] = $info;
        }
        return $all;
    }

    public function deactivateAddon($addon_id) {
        $this->deleteAddon($addon_id);
    }

    public function updateAddonSettings($addon_id, $settings, $type, $order_behavior) {
        $value = array('settings' => $settings, 'type' => $type, 'order_behavior'=>$order_behavior);
        $where = array('addon_id' => $addon_id);
        return update_query('mod_monitis_addon', $value, $where);
    }

}

$oAddon = new addonClass();

$action = monitisPost('action');
$addonId = monitisPostInt('addonId');
//_dump($_POST);
if ($action && $addonId > 0) {

        if ($_POST) {

        $post = array();
        foreach ($_POST as $key => $val) {
            if (!empty($key)) {
                $post[$key] = $val;
            }
        }

        $loc = json_decode('[' . str_replace(array("[", "]"), "", $post["locationIDs"]) . ']', true);
        $timeout = monitisPostInt("timeout");
        if ($timeout > 5000) {
            $timeout = 5000;
        }


        $set = MonitisConf::$settings;

        $allTypes = explode(",", MONITIS_MONITOR_TYPES);
        for ($i = 0; $i < count($allTypes); $i++) {

            if ($allTypes[$i] != 'ping') {
                $set[$allTypes[$i]]['timeout'] = intval($timeout / 1000);
            } else {
                $set[$allTypes[$i]]['timeout'] = $timeout;
            }

            $set[$allTypes[$i]]['interval'] = $post["interval"];            
            $set[$allTypes[$i]]['locationIds'] = $loc;
            $set['max_locations'] = (!$post["max_locations"]) ? 0 : $post["max_locations"];
        }

        $new_setting = json_encode($set);
        $order_behavior = json_encode(array('active' => $post['active'], 'pending' => $post['pending'], 'cancelled' => $post['cancelled'], 'fraud' => $post['fraud']));
    }
    
    $monitor_type = monitisPost('monitor_type');
    switch ($action) {
        case 'activate':
            $values = array(
                'addon_id' => $addonId,
                'type' => $monitor_type,
                'status' => 'active'
            );
            insert_query('mod_monitis_addon', $values);
            $oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type, $order_behavior );

		break;

        case 'update':
			  
            $oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type, $order_behavior);
		break;

        case 'deactivate':
            //echo "**** action = $action ****** addonId=$addonId **** <br>";
            $oAddon->deactivateAddon($addonId);
		break;
		case 'automate':
//echo "**** action = $action ****** addonId=$addonId **** <br>";
			$oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type, $order_behavior);
			header('location: ' . MONITIS_APP_URL . '&monitis_page=addonsResult&addonid='.$addonId);
		break;
    }
}

$addons = $oAddon->addonsList();
//_dump($addons);
?>
<style>

.datatable {
	width:100%;
	min-width:1000px;
}
.datatable td {
	overflow:hidden;
}

.datatable .actions div {
	text-align:left;
	width:150px;
	margin-bottom: 10px;
	padding-left:30px;
}

.datatable th.title{
	text-align:left;
	padding-left:10px;
}
</style>
<table class="datatable" border=0 cellspacing="1" cellpadding="3">
    <tr>
        <th width="20px">&nbsp;</th>
        <th class="title" style="width:150px;">Addon Name</th>

        <th class="title" style="width:120px;">Monitor type</th>
        <th class="title" style="width:320px;">Monitor settings</th>
        <th class="title" style="width:250px;">Order action behavior</th>
        <th>&nbsp;</th>
    </tr>
    <?
   
    if ($addons && count($addons) > 0) {

        $allTypes = explode(",", MONITIS_MONITOR_TYPES);

        for ($i = 0; $i < count($addons); $i++) {
            $addonId = $addons[$i]['id'];
            $type = $allTypes[0];
            
		if (isset($addons[$i]["monitor"]) && $addons[$i]["monitor"]) {
                $type = $addons[$i]["monitor"]["type"];

                $settings = json_decode($addons[$i]["monitor"]["settings"], true);
                $order_behavior = json_decode($addons[$i]["monitor"]['order_behavior'], true);
            } else {
                $settings = MonitisConf::$settings;;
                $order_behavior=null;
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
                                        //$monitor = $addons[$i]['monitor'];
                                        $loaction = '';
                                        if (!empty($settings['ping']['locationIds']))
                                            $loaction = json_encode($settings['ping']['locationIds']);
                                        for ($a = 0; $a < count($allTypes); $a++) {
                                            $monitor_type = $allTypes[$a];
                                            $prefix = $addonId;
                                            $selected = ( $allTypes[$a] == $type) ? 'selected' : '';
                                            ?>
                                            <option class="type" value="<?=$allTypes[$a]?>" <?=$selected ?>><?=strtoupper($allTypes[$a])?></option> 
                                        <? } ?>
                                    </select>
                                </td>
                                <td width="320px" style="padding-left:10px;">
                                    <table width="330px" cellspacing="1" cellpadding="3" border=0>
                                        <tr>
                                            <td class="fieldlabel">Interval:</td>
                                            <td><select name="interval">
                                            <?	$newInterval = $settings['ping']['interval'];

                                                $aInterval = explode(',', MonitisConf::$checkInterval);
                                                for ($int = 0; $int < count($aInterval); $int++) {
													if ($aInterval[$int] == $newInterval) { ?>
														<option value="<?= $aInterval[$int] ?>" selected ><?= $aInterval[$int] ?></option>
													<? } else { ?>
														<option value="<?= $aInterval[$int] ?>"><?= $aInterval[$int] ?></option>
													<? }
                                                }
                                               ?>
                                                </select>&nbsp;min.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fieldlabel">Timeout:</td>
                                            <td><input type="text" size="15" name="timeout" id="timeout" value="<?= $settings['ping']['timeout'] ?>"/>ms.</td>
                                        </tr>
                                        <tr>
                                            <td class="fieldlabel">Max locations:</td>
                                            <td><input type="text" size="15" id="max_locations<?= $prefix ?>"  name="max_locations" value="<?= $settings['max_locations'] ?>" /></td>
                                        </tr>
                                        <tr class="monitisMultiselect">
                                            <td class="fieldlabel" >Check locations:</td>
                                            <td>
                                                <span class="monitisMultiselectText" id="locationsize<?= $prefix ?>" ><?php echo sizeof($settings['ping']['locationIds']) . '  ' . "locations"; ?></span>
                                                <input type="button" id="selectTrigger<?= $prefix ?>" class="monitisMultiselectTrigger" value="Select" element_prefix="<?= $prefix ?>" locations="<?= $loaction ?>" />
                                                <input type="hidden" name="locationIDs" id="locationIDs<?= $prefix ?>" value="<?= $loaction ?> " />
                                                <div class="monitisMultiselectInputs" id="monitisMultiselectInputs<?= $prefix ?>" ></div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="250px" style="padding-left:10px;">
                                <table cellspacing="1" cellpadding="3" width="260px">
                                <? foreach ($default_order_behavior as $key => $val) {  ?>
									<tr>
										<td class="fieldlabel" style="width:100px"><?=ucfirst($key) ?>:</td>
										<td>
											<select style="min-width:110px" name=<?= $key ?>  >
												<? 
												foreach ($default_order_behavior[$key] as $k => $v) {                                                           
												  $selected = ( ($order_behavior && $k == $order_behavior[$key]) || (!$order_behavior && $default_order_behavior[$key][$k] > 0 )) ? 'selected' : ''; 
												?>    
												<option value="<?=$k ?>" <?= $selected ?> ><?= $order_title[$k] ?></option>

												<? } ?>
											</select>
										</td>
									</tr> 
                                <? } ?>
                                    </table> 

                                </td>
                                <td class="actions">
                                    <? if (isset($addons[$i]["monitor"]) && $addons[$i]["monitor"]) { ?>

                                        <input type="hidden" name="action" value="update" />
                                        <div><input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'" class="btn-danger" /></div>
										<div><input type="submit" value="Create Monitors" onclick="this.form.action.value='automate'" /></div>
										<div><input type="submit" value="Update" onclick="this.form.action.value = 'update'" /></div>
                                    <? } else { ?>
                                        <div><input type="hidden" name="action" value="activate" /></div>
                                        <div><input type="submit" value="Activate" class="btn-success" /></div>

                                    <? } ?>
                                </td>
                            </tr>
							</table>
                        <input type="hidden" name="addonId" value="<?= $addonId ?>" />
                    </form>
                </td>
            </tr>
        <?
        }
    }
    ?>
</table>
<input type="hidden" name="saveProductConfig" value="1" />

<script>
	var countryname = <? echo json_encode($locations); ?>;
	$(document).ready(function() {
		$('.monitisMultiselectTrigger').click(function(event) {
			var prefix = $(this).attr("element_prefix");
			var loc_ids = $(this).attr("locations");
			var loc = eval(loc_ids);
			locationDialog(prefix, loc);
		});
	});

	function initMonitisMultiselect(container, prefix) {
		var dialog = $(container).find(".monitisMultiselectDialog").dialog({
			width: 600,
			autoOpen: false,
			modal: true,
			buttons: {
				'Select': {
					text: 'Select',
					class: 'btn',
					click: function() {
						updateInput(prefix);
						$(this).dialog("close");
					}
				}
			},
			close: function() {
				$(this).remove();
			}
		});

		dialog.dialog('open');

		function updateInput(prefix) {
			var selectedCount = 0;
			var loc_ids = 'locationIDs' + prefix;
			var locsize = 'locationsize' + prefix;
			var ids = [];
			var max_loc = $("#max_locations" + prefix).val();

			$(dialog).find('input[type="checkbox"]').each(function(index) {
				if ($(this).is(':checked')) {
					selectedCount++;
					ids.push($(this).val());
					$(this).prop("checked", true);
				}
			});

			if (parseInt(max_loc) < parseInt(selectedCount)) {
				ids = ids.slice(0, parseInt(max_loc));
			}
			var ids_str = ids.toString();

			$('#' + loc_ids).val(ids_str);
			$('#selectTrigger' + prefix).attr('locations', '[' + ids_str + ']');
			if (parseInt(max_loc) < parseInt(selectedCount)) {
				selectedCount = max_loc;
			}
			$('#' + locsize).text(selectedCount+' locations');
		}
		updateInput(prefix);
	}

	function locationDialog(prefix, loc_ids) {
		var max_loc = $("#max_locations" + prefix).val();
		var str = '<div  class="monitisMultiselectDialog"><table  style="width:100%" cellpadding=10><tr><td id="maxlocationsmsgid">Maximum ' + max_loc + ' locations can be selected</td></tr><tr>';

		for (var name in countryname) {
			str += '<td style="vertical-align: top;"><div style="font-weight: bold; color: #71a9d2;">' + name + '</div><hr/>'

			var column = countryname[name];
			for (var location in column) {

				var checked = ($.inArray(column[location].id, loc_ids) !== -1) ? "checked" : "";

				str += ' <div><input type="checkbox" name="locationIDs[]" id="locationIDs" ' + checked + '   value="' + column[location].id + '"   > ' + column[location].fullName + '  </div>'

			}
			str += '</td>';
		}
		str += '</tr></table></div>';

		$('#monitisMultiselectInputs' + prefix).html(str);
		initMonitisMultiselect('#monitisMultiselectInputs' + prefix, prefix);
		
		$('.monitisMultiselectDialog input[type="checkbox"]').click(function(event) {
		 
			var selectedCount = $('.monitisMultiselectDialog input[type=checkbox]:checked').size();
			if ( selectedCount > parseInt(max_loc)) {
				event.preventDefault();
				$('#maxlocationsmsgid').css('color','#ff0000');
			} else {
				$('#maxlocationsmsgid').css('color','#000000')
			}
		});
	}
</script>
