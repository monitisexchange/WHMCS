<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
    if (empty($value))
        unset($locations[$key]);
}

$oMProduct = new productClass();

$action = monitisPost('action');
$productId = monitisPostInt('productId');
$monitor_type = monitisPost('monitor_type');
if ($action && $productId > 0) {
    if ($_POST) {

        $post = array();
        foreach ($_POST as $key => $val) {
            if (!empty($key)) {
                $post[$key] = $val;
            }
        }

        $loc = json_decode('[' . str_replace(array("[", "]"), "", $post["locationIDs" . $productId]) . ']', true);
		$timeout = $post["timeout" . $productId];
        if ( $timeout > 5000) {
            $timeout = 5000;
        }

        $set = array('interval' => $post["interval" . $productId], 'timeout' => $timeout, 'locationIds' => $loc,
            'max_locations' => (!$post["max_locations" . $productId]) ? 0 : $post["max_locations" . $productId], 'available' => 1);

        $new_setting = json_encode($set);
    }

    if (!empty($monitor_type)) {

        $website_id = monitisPostInt('website_id');
        $monType_id = monitisPostInt('monType_id');
        $monitor_type = implode(",", $monitor_type);

        $website_values = array(
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => MONITIS_FIELD_WEBSITE,
            'fieldtype' => 'text',
            'description' => '',
            'required' => 'on',
            'showorder' => 'on',
            'showinvoice' => 'on'
        );
        $monitor_values = array(
            'type' => 'product',
            'relid' => $productId,
            'fieldname' => MONITIS_FIELD_MONITOR,
            'fieldtype' => 'dropdown',
            'description' => '',
            'fieldoptions' => $monitor_type,
            'required' => 'on',
            'showorder' => 'on',
            'showinvoice' => 'on'
        );

        switch ($action) {
            case 'activate':

                $oMProduct->updateField($website_id, $website_values);
                $oMProduct->updateField($monType_id, $monitor_values);
                $oMProduct->activateProduct($productId);
                $oMProduct->updateProductSettings($productId, $new_setting);

                break;
            case 'deactivate':
                $oMProduct->deactivateProduct($productId);
                break;
            case 'update':
                $oMProduct->updateField($website_id, $website_values);
                $oMProduct->updateField($monType_id, $monitor_values);

                $new_setting = json_encode($set);
                $oMProduct->updateProductSettings($productId, $new_setting);

                break;
            case 'setMonitorType':
                if ($website_id == 0) {
                    $website_id = insert_query('tblcustomfields', $website_values);
                } else {
                    $oMProduct->updateField($website_id, $website_values);
                }
                if ($monType_id == 0) {
                    $monType_id = insert_query('tblcustomfields', $monitor_values);
                } else {
                    $oMProduct->updateField($monType_id, $monitor_values);
                }
                $oMProduct->activateProduct($productId);
                break;
        }
    } else {
        MonitisApp::addError('Monitor type is required');
    }
}

$products = $oMProduct->getproducts();
?>
<?php MonitisApp::printNotifications(); ?>
<style>
.datatable th.title{
	text-align:left;
	padding-left:10px;
}
.datatable .customfields ul{
	list-style-type: none;
	width:200px;
	float:left;
	margin: 0px;
	padding: 2px 5px;
}
.datatable .customfields li{
	padding: 3px 0px;
	margin: auto 0px;
}

.datatable .monitor_setts{
	padding:5px 0px 5px 40px;
	border:1px solid #ebebeb;
}
</style>


<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <tr>
        <th width="20">&nbsp;</th>
        <th class="title" style="width:150px;">Product Name</th>

        <th class="title" style="width:220px;">Monitor type</th>
        <th class="title" style="width:300px;">Monitor settings</th>
        <!--th class="title" style="width:150px;">Available to customer</th-->
        <th>&nbsp;</th>
    </tr>
    <?
    if ($products && count($products) > 0) {
        $totalresults = $products[0]['total'];
        $allTypes = explode(",", MONITIS_MONITOR_TYPES);
        for ($i = 0; $i < count($products); $i++) {
            $productId = $products[$i]['id'];

            $settings = MonitisConf::$settings;

            if ($products[$i]['monitorType'] && $products[$i]['settings'] != '') {
                $settings = json_decode($products[$i]['settings'], true);
            }
            ?>
            <tr>
                <td>&nbsp;</td>
                <td><?php echo $products[$i]['name'] ?></td>
                <td class="customfields" valign="center" align="left" colspan="4">
                    <form method="post" action="">
                        <table width="100%" cellspacing="1" cellpadding="3" style="text-align: left;" border=0>
                            <tr>
        <?
        $exist = false;
        if ($products[$i]['monitorType']) {
            $exist = true;
            $customfields = $products[$i]['customfields'];
            $website = $monType = null;
            $website_id = $monType_id = 0;

            for ($j = 0; $j < count($customfields); $j++) {
                if ($customfields[$j]['fieldname'] == MONITIS_FIELD_WEBSITE) {
                    $website = $customfields[$j];
                    $website_id = $customfields[$j]['id'];
                }
                if ($customfields[$j]['fieldname'] == MONITIS_FIELD_MONITOR) {
                    $monType = $customfields[$j];
                    $monType_id = $customfields[$j]['id'];
                }
            }
            $types = explode(",", $monType['fieldoptions']);
        }
        ?>
                                <td width="60px"><ul>
                                <?
                                for ($a = 0; $a < count($allTypes); $a++) {

                                    $monitor_type = $allTypes[$a];
                                    $prefix = $productId;

                                    $loaction = json_encode($settings[$monitor_type]['locationIds']);
                                    if (!empty($settings['locationIds'])) {
                                        $loaction = json_encode($settings['locationIds']);
                                    }

                                    $checked = (in_array($allTypes[$a], $types)) ? 'checked' : '';
                                    ?>             

                                            <li style="width: 130px; min-height: 25px;">
                                                <span><input type="checkbox" value="<?= $allTypes[$a] ?>" name="monitor_type[]" <?= $checked ?> /></span>
                                                <span class="type" style="clear:both;"><?php echo strtoupper($allTypes[$a]) ?> monitor</span>  


                                            </li>       
        <? } ?>
                                    </ul></td>   

                                <td width="300px" style="padding-left:20px;">
                                    <table width="98%" cellspacing="0" cellpadding="0" style="text-align: left;" border=0>
                                        <tr>
                                            <td class="fieldlabel">Interval:</td>
                                            <td><select name="interval<?= $prefix ?>">
							<?
							$aInterval = explode(',', MonitisConf::$checkInterval);
							for ($int = 0; $int < count($aInterval); $int++) {
								if ($aInterval[$int] == $settings['interval']) {
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
                                                <input type="text" size="15" name="timeout<?=$prefix?>" id="timeout" value="<?= ($settings['timeout']) ? $settings['timeout'] : $settings['ping']['timeout'] ?>"/>ms.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fieldlabel">Max locations:</td>
                                            <td>
                                                <input type="text" size="15" id="max_locations<?=$prefix?>"  name="max_locations<?=$prefix?>" value="<?=$settings['max_locations']?>" />
                                            </td>
                                        </tr>
                                        <tr class="monitisMultiselect">
                                            <td class="fieldlabel" >Check locations:</td>
                                            <td>
                                                <span class="monitisMultiselectText" id="locationsize<?= $prefix ?>" ><?php echo sizeof(($settings['locationIds']) ? $settings['locationIds'] : $settings['ping']['locationIds'] ) . '  ' . "locations"; ?></span>
                                                <input type="button" id="selectTrigger<?= $prefix ?>" class="monitisMultiselectTrigger" value="Select" element_prefix="<?= $prefix ?>" locations="<?= $loaction ?>" />
                                                <input type="hidden" name="locationIDs<?= $prefix ?>" id="locationIDs<?= $prefix ?>" value="<?= $loaction ?> " />
                                                <div class="monitisMultiselectInputs" id="monitisMultiselectInputs<?= $prefix ?>" ></div>
                                            </td>
                                        </tr>
                                    </table>								

                                </td>

                                <td style="padding-left:40px;">
						<? if ($exist) { ?>
                                        <? if ($products[$i]['isWhmcsItem']) { ?>
                                            <input type="hidden" name="action" value="update" />
                                            <input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'" class="btn-danger"  />
                                            <input type="submit" value="Update" onclick="this.form.action.value = 'update'" />
						<? } else { ?>
                                            <input type="hidden" name="action" value="activate" />
                                            <input type="submit" value="Activate" onclick="this.form.action.value = 'activate'" class="btn-success"  />

						<? } ?>
                                        <input type="hidden" name="website_id" value="<?= $website_id ?>" />
                                        <input type="hidden" name="monType_id" value="<?= $monType_id ?>" />
                                        <!-- input type="submit" value="Delete" onclick="this.form.action.value='delete'" / -->
						<? } else { ?>

                                        <input type="hidden" name="action" value="setMonitorType" />
                                        <input type="submit" value="Activate" class="btn-success" />
						<? } ?>
                                </td>

                            </tr></table>
                        <input type="hidden" name="productId" value="<?= $productId ?>" />
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
		location_click();
		$('.monitisMultiselectTrigger').click(function(event) {
			var prefix = $(this).attr("element_prefix");
			var loc_ids = $(this).attr("locations");
			var loc = eval(loc_ids);
			locationDialog(prefix, loc);
		});
	});

	function location_click() {

		$('.monitor_setts').each(function() {
			$(this).hide();
		});
		$('.type').click(function() {
			$(this).next().slideToggle();
			event.preventDefault();
		});
	}

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
				}
			});
			if (max_loc < selectedCount) {
				ids = ids.slice(0, parseInt(max_loc));
			}
			var ids_str = ids.toString();
			$('#' + loc_ids).val(ids_str);
			$('#selectTrigger' + prefix).attr('locations', '[' + ids_str + ']');
			if (max_loc < selectedCount) {
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
			str += '<td style="vertical-align: top;"><div style="font-weight: bold; color: #71a9d2;">' + name + '</div><hr/>';
			var column = countryname[name];
			for (var location in column) {
				var checked = ($.inArray(column[location].id, loc_ids) !== -1) ? "checked" : "";
				str += ' <div><input type="checkbox" name="locationIDs[]" id="locationIDs" ' + checked + '   value="' + column[location].id + '"   > ' + column[location].fullName + '  </div>';
			}
			str += '</td>';
		}
		str += '</tr></table></div>';

		$('#monitisMultiselectInputs' + prefix).html(str);

		initMonitisMultiselect('#monitisMultiselectInputs' + prefix, prefix);
		
		$('.monitisMultiselectDialog input[type="checkbox"]').click(function(event) {
			var selectedCount = $('.monitisMultiselectDialog input[type=checkbox]:checked').size();
//console.log(selectedCount);
//console.log($('.monitisMultiselectDialog input[type=checkbox]:checked').length);
//console.log('max_loc = '+ max_loc + ' selectedCount= ' +selectedCount);

			if ( selectedCount > parseInt(max_loc)) {
				event.preventDefault();
				$('#maxlocationsmsgid').css('color','#ff0000');
			} else {
				$('#maxlocationsmsgid').css('color','#000000')
			}
		});
	}
</script>
