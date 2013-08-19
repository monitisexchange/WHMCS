<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');

$order_title = json_decode(MONITIS_ORDER_BEHAVIOR_TITLE, true);
$default_order_behavior = json_decode(MONITIS_ORDER_BEHAVIOR, true);

$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
    if (empty($value))
        unset($locations[$key]);
}

$oMProduct = new productClass();


$action = monitisPost('action');
$productId = monitisPostInt('productId');
$monitor_type = monitisPost('monitor_type');

//_dump($_POST);
if ($action && $productId > 0) {
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
                $oMProduct->updateProductSettings($productId, $new_setting, $order_behavior);

                break;
            case 'deactivate':
                $oMProduct->deactivateProduct($productId);
                break;
            case 'update':
                $oMProduct->updateField($website_id, $website_values);
                $oMProduct->updateField($monType_id, $monitor_values);
                $oMProduct->updateProductSettings($productId, $new_setting, $order_behavior);

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
.datatable {
	width:100%;
	min-width:1000px;
}
.datatable td {
	overflow:hidden;
}
.datatable th.title{
	text-align:left;
	padding-left:10px;
}
.datatable .customfields ul{
	list-style-type: none;
	width:130px;
/*	float:left;*/
	margin: 0px;
	padding: 2px 5px;
}
.datatable .customfields li{
	padding: 3px 0px;
	margin: auto 0px;
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
</style>


<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <tr>
        <th width="20px">&nbsp;</th>
        <th class="title" style="width:150px;">Product Name</th>

        <th class="title" style="width:150px;">Monitor type</th>
        <th class="title" style="width:320px;">Monitor settings</th>
        <th class="title" style="width:250px;">Order action behavior</th>
        <th>&nbsp;</th>
    </tr>
    <?
    if ($products && count($products) > 0) {
        $totalresults = $products[0]['total'];
        $allTypes = explode(",", MONITIS_MONITOR_TYPES);
        for ($i = 0; $i < count($products); $i++) {
            $productId = $products[$i]['id'];
            ?>
            <tr>
                <td>&nbsp;</td>
                <td width="150px" style="border-right:solid 1px #ebebeb;"><?php echo $products[$i]['name'] ?></td>
                <td class="customfields" valign="center" align="left" colspan="4" style="padding:0px;">
                    <form method="post" action="">
                        <table style="width:100%;min-width:1000px"cellspacing="1" cellpadding="6" border=0>
                            <tr>
                                <?
                                $exist = false;
                                $types = null;
                                $order_behavior = null;
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
                                    //  $settings = json_decode($products[$i]['settings'], true);             
                                }

                                if ($products[$i]['settings']) {
                                    $settings = json_decode($products[$i]['settings'], true);
                                    $order_behavior = json_decode($products[$i]['order_behavior'], true);
                                } else {
                                    $settings = MonitisConf::$settings;
                                }
                                ?>
                             
                                <td width="150px"><ul>
                                <?
                                $loaction = json_encode($settings['ping']['locationIds']);
                                for ($a = 0; $a < count($allTypes); $a++) {

                                    $monitor_type = $allTypes[$a];
                                    $prefix = $productId;
									
									$checked = 'checked';
									if($types) {
										$checked = (in_array($allTypes[$a], $types)) ? 'checked' : '';
									}
                                    ?>             
										<li style="width: 130px; min-height: 25px;">
											<span><input type="checkbox" value="<?= $allTypes[$a] ?>" name="monitor_type[]" <?= $checked ?> /></span>
											<span class="type" style="clear:both;"><?php echo strtoupper($allTypes[$a]) ?> monitor</span>
										</li>       
								<? } ?>
                                    </ul></td>   

                                <td width="320px" >
                                    <table width="330px" cellspacing="1" cellpadding="3" border=0>
                                        <tr>
                                            <td class="fieldlabel">Interval:</td>
                                            <td><select name="interval">
											<?
											$aInterval = explode(',', MonitisConf::$checkInterval);
											for ($int = 0; $int < count($aInterval); $int++) {
												if ($aInterval[$int] == $settings['ping']['interval']) {
													?>
														<option value="<?= $aInterval[$int] ?>" selected ><?= $aInterval[$int] ?></option>
													<? } else { ?>
														<option value="<?= $aInterval[$int] ?>"><?= $aInterval[$int] ?></option>
														<?
													}
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
                                <td width="250px">
                                 <table cellspacing="1" cellpadding="3" width="260px">
                                <? foreach ($default_order_behavior as $key => $val) {  ?>
									<tr>
										<td class="fieldlabel" style="width:100px"><?= ucfirst($key)?>:</td>
										<td>
											<select style="min-width:110px" name=<?= $key ?>  >
												<? foreach ($default_order_behavior[$key] as $k => $v) {
													$selected = ( ($order_behavior && $k == $order_behavior[$key]) || (!$order_behavior && $default_order_behavior[$key][$k] > 0 )) ? 'selected' : ''; 
												?>    
												<option value="<?= $k ?>" <?= $selected ?> > <?= $order_title[$k] ?></option>
												<? } ?>
											</select>
										</td>
									</tr> 
                                <? } ?>
								</table> 

                                </td>
                                <td class="actions">
                                 <? if ($exist) { ?>
                                        <? if ($products[$i]['isWhmcsItem']) { ?>
                                            <input type="hidden" name="action" value="update" />
                                            <div><input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'" class="btn-danger"  /></div>
                                            <div><input type="submit" value="Update" onclick="this.form.action.value = 'update'" /></div>
										<? } else { ?>
                                            <div><input type="hidden" name="action" value="activate" /></div>
                                            <div><input type="submit" value="Activate" onclick="this.form.action.value = 'activate'" class="btn-success" /></div>

										<? } ?>
                                        <input type="hidden" name="website_id" value="<?= $website_id ?>" />
                                        <input type="hidden" name="monType_id" value="<?= $monType_id ?>" />
                                        <!-- input type="submit" value="Delete" onclick="this.form.action.value='delete'" / -->
                                 <? } else { ?>

                                        <div><input type="hidden" name="action" value="setMonitorType" /></div>
                                        <div><input type="submit" value="Activate" class="btn-success" /></div>
                                 <? } ?>
                                </td>
                       
                            </tr>
                        </table>
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
                                        $('#' + locsize).text(selectedCount+"  locations");
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
                                        if (selectedCount > parseInt(max_loc)) {
                                            event.preventDefault();
                                            $('#maxlocationsmsgid').css('color', '#ff0000');
                                        } else {
                                            $('#maxlocationsmsgid').css('color', '#000000')
                                        }

                                    });
                                }
</script>
