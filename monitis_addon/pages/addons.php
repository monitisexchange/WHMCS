<?php
MonitisApp::printNotifications();
// Automatically create following monitors when<br/>creating new servers on WHMCS
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

    public function updateAddonSettings($addon_id, $settings, $type) {
        $value = array('settings' => $settings, 'type' => $type);
        $where = array('addon_id' => $addon_id);
        return update_query('mod_monitis_addon', $value, $where);
    }

}

$oAddon = new addonClass();

$action = monitisPost('action');
$addonId = monitisPostInt('addonId');

if ($action && $addonId > 0) {

    if ($_POST) {

        $post = array();
        foreach ($_POST as $key => $val) {
            if (!empty($key)) {
                $post[$key] = $val;
            }
        }

        $loc = json_decode('[' . str_replace(array("[", "]"), "", $post["locationIDs" . $addonId]) . ']', true);
		
		$timeout = $post["timeout" . $addonId];
        if ( $timeout > 5000) {
            $timeout = 5000;
        }

        $set = array('interval' => $post["interval" . $addonId], 'timeout' => $timeout, 'locationIds' => $loc,
            'max_locations' => (!$post["max_locations" . $addonId]) ? 0 : $post["max_locations" . $addonId], 'available' => 1);

        $new_setting = json_encode($set);
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
            $oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type);

		break;

        case 'update':
			  
            $oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type);
		break;

        case 'deactivate':
            //echo "**** action = $action ****** addonId=$addonId **** <br>";
            $oAddon->deactivateAddon($addonId);
		break;
		case 'automate':
//echo "**** action = $action ****** addonId=$addonId **** <br>";
			$oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type);
			header('location: ' . MONITIS_APP_URL . '&monitis_page=addonsResult&addonid='.$addonId);
		break;
    }
}

$addons = $oAddon->addonsList();
//_dump($addons);
?>
<style>
    
.automate {
	width:500px;
	margin: 0px auto ;
}
.automate td, .automate th{
	text-align:left;
}
.automate th{
	width:120px;
}
.datatable th.title{
	text-align:left;
	padding-left:10px;
}
</style>
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <tr>
        <th width="20">&nbsp;</th>
        <th class="title" style="width:150px;">Addon Name</th>

        <th class="title" style="width:150px;">Monitor type</th>
        <th class="title" style="width:310px;">Monitor settings</th>

        <th>&nbsp;</th>
    </tr>
    <?
    if ($addons && count($addons) > 0) {

        $allTypes = explode(",", MONITIS_MONITOR_TYPES);

        for ($i = 0; $i < count($addons); $i++) {
            $addonId = $addons[$i]['id'];
            $type = $allTypes[0];

            if (isset($addons[$i]["monitor"]) && $addons[$i]["monitor"] && !empty($addons[$i]["monitor"]["settings"])) {
                $type = $addons[$i]["monitor"]["type"];

                $settings1 = json_decode($addons[$i]["monitor"]["settings"], true);
            } else {
                $settings1 = MonitisConf::settingsByType('ping');
            }
            ?>
            <tr>
                <td>&nbsp;</td>
                <td><?php echo $addons[$i]['name'] ?></td>
                <td class="customfields" valign="center" align="left" colspan="4">
                    <form method="post" action="">
                        <table width="100%" cellspacing="1" cellpadding="3" style="text-align: left;" border=0>
                            <tr>
        <? ?>

                                <td class="type" width="150px">
                                    <select name="monitor_type">
        <?
        //$monitor = $addons[$i]['monitor'];
        $loaction = '';
        if (!empty($settings1['locationIds']))
            $loaction = json_encode($settings1['locationIds']);
        for ($a = 0; $a < count($allTypes); $a++) {
            $monitor_type = $allTypes[$a];
            $prefix = $addonId;
            $selected = ( $allTypes[$a] == $type) ? 'selected' : '';
            ?>
                                            <option class="type" value="<?= $allTypes[$a] ?>" <?= $selected ?>><?= strtoupper($allTypes[$a]) ?></option> 
                                        <? } ?>
                                    </select>
                                </td>
                                <td width="300px" style="padding-left:20px;">
                                    <table width="98%" cellspacing="0" cellpadding="0" style="text-align: left;" border=0>
                                        <tr>
                                            <td class="fieldlabel">Interval:</td>
                                            <td><select name="interval<?= $prefix ?>">
										
									<?	$newInterval = $settings1['interval'];

										$aInterval = explode(',', MonitisConf::$checkInterval);
										for ($int = 0; $int < count($aInterval); $int++) {
											if ($aInterval[$int] == $newInterval) {
									?>
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
                                            <td>
                                                <input type="text" size="15" name="timeout<?= $prefix ?>" id="timeout" value="<?= $settings1['timeout'] ?>"/>ms.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fieldlabel">Max locations:</td>
                                            <td>
                                                <input type="text" size="15" id="max_locations<?= $prefix ?>"  name="max_locations<?= $prefix ?>" value="<?= $settings1['max_locations'] ?>" />
                                            </td>
                                        </tr>
                                        <tr class="monitisMultiselect">
                                            <td class="fieldlabel" >Check locations:</td>
                                            <td>
                                                <span class="monitisMultiselectText" id="locationsize<?= $prefix ?>" ><?php echo sizeof($settings1['locationIds']) . '  ' . "locations"; ?></span>
                                                <input type="button" id="selectTrigger<?= $prefix ?>" class="monitisMultiselectTrigger" value="Select" element_prefix="<?= $prefix ?>" locations="<?= $loaction ?>" />
                                                <input type="hidden" name="locationIDs<?= $prefix ?>" id="locationIDs<?= $prefix ?>" value="<?= $loaction ?> " />
                                                <div class="monitisMultiselectInputs" id="monitisMultiselectInputs<?= $prefix ?>" ></div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                <td style="padding-left: 40px;">
                                    <? if (isset($addons[$i]["monitor"]) && $addons[$i]["monitor"]) { ?>

                                        <input type="hidden" name="action" value="update" />
                                        <input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'" class="btn-danger"  />
                                        <input type="submit" value="Update" onclick="this.form.action.value = 'update'" />
										<input type="submit" value="Create Monitors" onclick="this.form.action.value = 'automate'" />
                                    <? } else { ?>
                                        <input type="hidden" name="action" value="activate" />
                                        <input type="submit" value="Activate" class="btn-success"  />

                                    <? } ?>
                                </td>
                            </tr></table>
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
				updateInput(prefix);
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
			$('#' + locsize).text(selectedCount);
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
