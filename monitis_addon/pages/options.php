<?php
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
    if (empty($value))
        unset($locations[$key]);
}
$contactGroups = MonitisApi::getContactGroupList();

if(isset($_GET['add-option'])){
	$optionId = (int) $_GET['add-option'];
	$settings = addslashes('{"interval": 1, "timeout": 10000, "locationsMax": 5, "locationIds": [1,9,10], "alertGroupId": 0, "alertRules": {"period":{"always":{"value":1,"params":null},"specifiedTime":{"value":0,"params":{"timeFrom":"00:00:00","timeTo":"23:59:00"}},"specifiedDays":{"value":0,"params":{"weekdayFrom":{"day":1,"time":"00:00:00"},"weekdayTo":{"day":7,"time":"23:59:00"}}}},"notifyBackup":1,"continuousAlerts":1,"failureCount":2}}');
	$query = '
		INSERT INTO `mod_monitis_options`
		(`option_id`, `type`, `settings`)
		VALUES ('.$optionId.',"http", "'.$settings.'")
	';
	if(mysql_query($query)){
		$success = 'Option has been added.';
	}
}
else if(isset($_POST['action'])){
	if($_POST['action'] == 'save'){
		$optionId = (int)$_POST['option_id'];
		$type = mysql_real_escape_string($_POST['type']);
		$settings = mysql_real_escape_string($_POST['settings']);
		if($_POST['is_active'] === ''){
			$isActive = null;
		}
		else{
			$isActive = $_POST['is_active'] == 1 ? '1' : '0';
		}
		$query = '
			UPDATE `mod_monitis_options`
			SET type = \''.$type.'\', settings = \''.$settings.'\'
			'. ($isActive !== null ? ', is_active = '.$isActive : '') .'
			WHERE option_id = '.$optionId.'
		';
		if(mysql_query($query)){
			if($isActive === null){
				$success = 'Option settings have been saved.';
			}
			elseif($isActive){
				$success = 'Option have been activated.';
			}
			else{
				$success = 'Option have been deactivated.';
			}
		}
		else{
			$error = 'An error occurred. Try again later.';
		}
	}
	elseif($_POST['action'] == 'remove'){
		/* its is for restoring changes, real removeing in the php entries */
		$optionIdToRemove = (int)$_POST['option_id'];
	}
	elseif($_POST['action'] == 'add'){
		$optionId = (int)$_POST['option_id'];
		$type = mysql_real_escape_string($_POST['type']);
		$settings = mysql_real_escape_string($_POST['settings']);
		$query = '
			INSERT INTO `mod_monitis_options`
			(option_id, type, settings)
			VALUES (\''.$optionId.'\', \''.$type.'\', \''.$settings.'\')
		';
		if(mysql_query($query)){
			$success = 'Option have been restored.';
		}
	}
}

$query = '
	SELECT groups.id AS group_id, groups.name AS group_name, configs.id AS config_id, configs.optionname AS config_name,
		options.id AS option_id, options.optionname AS option_name,
		moptions.type, moptions.settings, moptions.is_active
	FROM `mod_monitis_options` AS moptions
		LEFT JOIN `tblproductconfigoptionssub` AS options
			ON options.id = moptions.option_id
		LEFT JOIN `tblproductconfigoptions` AS configs
			ON configs.id = options.configid
		LEFT JOIN `tblproductconfiggroups` AS groups
			ON groups.id = configs.gid
';
$result = mysql_query($query);

$contactGroups = MonitisApi::getContactGroupList();

$groups = array();
while($row = mysql_fetch_assoc($result)){
	if(!is_array($groups[$row['group_id']])){
		$groups[$row['group_id']] = array(
			'name' => $row['group_name'],
			'configs'=> array() 
		);
	}
	if(!is_array($groups[$row['group_id']]['configs'][$row['config_id']])){
		$groups[$row['group_id']]['configs'][$row['config_id']] = array(
			'name' => $row['config_name'],
			'options' => array()
		);
	}
	if($row['option_id'] != $optionIdToRemove){
		$settings = json_decode(html_entity_decode($row['settings']), true);
		$settings['locationsCount'] = count($settings['locationIds']);
		$settings['locationIds'] = join($settings['locationIds'], ',');
		$settings['alertRules'] = str_replace('"', '~', json_encode($settings['alertRules']));
		$settings['alertGroupName'] = MonitisApiHelper::groupNameByGroupId($settings['alertGroupId'], $contactGroups);
	}
	else{
		$settings = str_replace('"', '~', $row['settings']);
	}
	$groups[$row['group_id']]['configs'][$row['config_id']]['options'][$row['option_id']] = array(
		'name' => $row['option_name'],
		'type' => $row['type'],
		'is_active' => $row['is_active'],
		'settings' => $settings
	);
}

$query = '
	SELECT groups.id AS group_id, groups.name AS group_name, configs.id AS config_id, configs.optionname AS config_name, options.id AS option_id, options.optionname AS option_name
	FROM `tblproductconfigoptionssub` AS options
		LEFT JOIN `tblproductconfigoptions` AS configs
			ON configs.id = options.configid
		LEFT JOIN `tblproductconfiggroups` AS groups
			ON groups.id = configs.gid		
';
$result = mysql_query($query);

$tree = array();
while($row = mysql_fetch_assoc($result)){
	if(!is_array($tree[$row['group_id']])){
		$tree[$row['group_id']] = array(
			'name' => $row['group_name'],
			'configs'=> array()
		);
	}
	if(!is_array($tree[$row['group_id']]['configs'][$row['config_id']])){
		$tree[$row['group_id']]['configs'][$row['config_id']] = array(
			'name' => $row['config_name'],
			'options' => array()
		);
	}
	
	//Check is this option in already added options
	if(
		isset($groups[$row['group_id']]) &&
		isset($groups[$row['group_id']]['configs'][$row['config_id']]) &&
		isset($groups[$row['group_id']]['configs'][$row['config_id']]['options'][$row['option_id']])
	){
		//Check is this option just removed
		if(isset($optionIdToRemove) && $row['option_id'] == $optionIdToRemove){
			$hasMonitor = false;
		}
		else{
			$hasMonitor = true;
		}
	}
	else{
		$hasMonitor = false;
	}
	
	$tree[$row['group_id']]['configs'][$row['config_id']]['options'][$row['option_id']] = array(
		'name' => $row['option_name'],
		'hasMonitor' => $hasMonitor
	);
}


if(isset($optionIdToRemove)){
	$query = '
		DELETE FROM `mod_monitis_options`
		WHERE option_id = '.$optionIdToRemove.'
	';
	if(mysql_query($query)){	
		$success = 'Option have been removed.';
	}
	else{
		
		$error = 'An error occurred. Try again later.';
	}
}

?>

<style type="text/css">
.monitis-options{
	text-align: left;
}
.monitis-options .datatable th{
	text-align: left;
	padding-left: 10px;
}
.monitis-options .datatable .actions div{
	padding: 0 0 10px 30px;
}
.add-option .title ul{
	padding: 0;
	margin: 0;
}
.add-option .title ul li{
	display: inline-block;
	width: 46px;
	background-color: #1A4D80;
	color: white;
	font-weight: bold;
	padding: 2px;
	margin: 1px;

	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}
.add-option .elements ul{
	margin: 0;
	padding: 0;
}
.add-option .elements ul ul{
	display: none;
}
.add-option .title{
	margin: 1px;
	padding: 2px 4px;
	background-color: #1A4D80;
	color: white;

	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}
.add-option .elements li{
	margin: 5px 2px;
}
.add-option .elements span{
	display: inline;
	cursor: pointer;
	border-bottom: 1px dashed #1A4D80;
	color: #1A4D80;
}
.add-option .elements .selected{
	font-weight: bold;
}
.add-option .selectable .disabled{
	color: #CCCCCC;
}
.add-option .selectable li{
	margin: 7px 0px;
}
.add-option .selectable span{
	border-bottom: none;
	padding: 2px 2px;

	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}
.add-option .selectable .selected{
	background: #1A4D80;
	color: white;
}
.monitis-options .note, .add-option .note{
	margin: 10px 0;
	padding: 10px;
	background-color: #FCF8E3;
	border: 1px solid #EFC987;
	font-weight: bold;
	color: #C09853;
	
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}
.monitis-options .note.success{
	background-color: #D9E6C3;
	border: 1px solid #77AB13;
	color: #69990f;
}
.monitis-options .note.error{
	background-color: #F2D4CE;
	border: 1px solid #AE432E;
	color: #CC0000;
}
.monitis-options .template{
	display: none;
}
.datatable{
	width: 100%;
}
.layout:after{
	content: '.';
	display: block;
	clear: both;
	height: 0;
	visibility: hidden;
}
.layout-33{
	float: left;
	width: 33.3%;
}
</style>

<script type="text/javascript">
var countryname = <?=json_encode($locations);?>;
var contactGroups = <?=json_encode($contactGroups);?>;
$(document).ready(function(){
	
	$('.monitis-options .locations').click(function(event){
		var opt = {
			parentId: "window-locations",
			max_loc: $(this).closest('table').find('[name=locations_max]').val(),
			loc_ids: $(this).attr('data-location-ids')
		};
		new monitisLocationDialogClass( opt, $.proxy(function(locations, count){
			$(this).attr('data-location-ids', locations);
			$(this).parent().find('.count').html(count);
		}, this));
	});

	$('.monitis-options .alert').click(function(event) {
		var settings = $(this).attr('data-rules');
		//console.log(sets_json);

		if(settings){
			var group = {
				id: $(this).attr('data-group-id'),
				name: $(this).attr('data-group-name'),
				list: contactGroups
			}
			var obj = new monitisNotificationRuleClass(settings, group, $.proxy(function(settings, group){
				$(this).attr('data-rules', settings);
				$(this).attr('data-group-id', group.id);
				$(this).attr('data-group-name', group.name);
				var title = ( group.id > 0) ? group.name : group.name;
				$(this).val(title);
			}, this));
		}
	});
	$('.add-option .groups span').click(function(){
		$('.add-option .note').hide();
		$('.add-option .groups span').removeClass('selected');
		$(this).addClass('selected');
		var ul = $(this).parent().children('ul');
		$('.add-option .options, .add-option .values').html('');
		$('.add-option .options').append(ul.clone());
	});
	$('.add-option .options').delegate('span', 'click', function(){
		$('.add-option .note').hide();
		$('.add-option .options span').removeClass('selected');
		$(this).addClass('selected');
		var ul = $(this).parent().children('ul');
		$('.add-option .values').html('');
		$('.add-option .values').append(ul.clone());
	});
	$('.add-option .values').delegate('span', 'click', function(){
		$('.add-option .note').hide();
		if($(this).hasClass('disabled')){
			//$(this).closest('.add-option').find('.note').show().html('Options with silver titles is alrwady added.');
			 return;
		}
		$('.add-option .values span').removeClass('selected');
		$(this).addClass('selected');
	});
	$('[name=add-option]').click(function(){
		$('.add-option').dialog({
			title: $('.add-option').attr('data-title'),
			height: 400,
			width: 500,
			modal: true,
			open: function(){
				$(this).find('.selected').removeClass('selected');
			},
			buttons: [
				{
					'text': 'Add',
					'class': 'btn',
					'click': function(){
						$('.note').hide();
						if($(this).find('.values .selected').length != 0){
							window.location = window.location + '&add-option=' + $(this).find('.values .selected').attr('data-id');
							//$(this).dialog('close');
						}
						else{
							$(this).find('.note').show().html('Please select option.');
						}
					}
				},
				{
					'text': 'Cancel',
					'class': 'btn',
					'click': function(){
						$(this).dialog('close');
					}
				}
			]
		})
	});
	function monitisOptionsSave($option, isActive){
		if(isActive == undefined) isActive = '';
		$('#actions [name=action]').val('save');
		$('#actions [name=option_id]').val($option.attr('data-id'));
		$('#actions [name=type]').val($option.find('[name=type]').val());
		$('#actions [name=is_active]').val(isActive);
		var settings = '{';
		settings += '"interval":"'+$option.find('[name=interval]').val()+'"';
		settings += ',"timeout":"'+$option.find('[name=timeout]').val()+'"';
		settings += ',"locationsMax":"'+$option.find('[name=locations_max]').val()+'"';
		settings += ',"locationIds":['+$option.find('.locations').attr('data-location-ids')+']';
		settings += ',"alertGroupId":"'+$option.find('.alert').attr('data-group-id')+'"';
		settings += ',"alertRules":'+$option.find('.alert').attr('data-rules').replace(/\~/g, '"')+'';
		settings += '}';
		$('#actions [name=settings]').val(settings);
		$('#actions').submit();
	}
	$('.monitis-options .activate').click(function(){
		var $option = $(this).closest('.option');
		monitisOptionsSave($option, 1);
	});
	$('.monitis-options .save').click(function(){
		var $option = $(this).closest('.option');
		monitisOptionsSave($option);	
	});
	$('.monitis-options .deactivate').click(function(){
		var $option = $(this).closest('.option');
		monitisOptionsSave($option, 0);
	});
	$('.monitis-options .remove').click(function(){
		var $option = $(this).closest('.option');
		$('#actions [name=action]').val('remove');
		$('#actions [name=option_id]').val($option.attr('data-id'));	
		$('#actions [name=type]').val('');
		$('#actions [name=is_active]').val('');
		$('#actions [name=settings]').val('');
		$('#actions').submit();
	});
	$('.monitis-options .restore').click(function(){
		var $option = $(this).closest('.option');
		$('#actions [name=action]').val('add');
		$('#actions [name=option_id]').val($option.attr('data-id'));
		$('#actions [name=type]').val($option.attr('data-type'));
		$('#actions [name=is_active]').val('');
		$('#actions [name=settings]').val($option.attr('data-settings').replace(/\~/g, '"'));
		$('#actions').submit();
	});
	$('.monitis-options [name=type]').change(function(){
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
	$('.monitis-options [name=type]').change();
	$('.monitis-options [name=timeout]').blur(function(){
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

<div class="monitis-options">
	<div class="template add-option" data-title="Select configurable option">
		<div class="note" style="display: none;"></div>
		<div class="layout">
			<div class="layout-33">
				<div class="title">
					Group
				</div>
				<div class="groups elements">
					<ul>
					<?foreach($tree as $groupId => $group):?>
						<li>
							<span class="tree-group" data-id="<?=$groupId?>"><?=$group['name']?></span>
							<ul>
							<?foreach($group['configs'] as $configId => $config):?>
								<li>
								<span class="tree-config" data-id="<?=$configId?>"><?=$config['name']?></span>
									<ul>
									<?foreach($config['options'] as $optionId => $option):?>					
										<li>
										<span class="tree-option <?if($option['hasMonitor']):?>disabled<?endif?>" data-id="<?=$optionId?>"><?=$option['name']?></span>
										</li>
									<?endforeach?>
									</ul>
								</li>
							<?endforeach?>
							</ul>
						</li>
					<?endforeach?>
					</ul>
				</div>
			</div>
			<div class="layout-33">
				<div class="title">
					Option
				</div>
				<div class="options elements">

				</div>
			</div>
			<div class="layout-33">
				<div class="title">
					Value
				</div>
				<div class="values elements selectable">

				</div>
			</div>
		</div>
	</div>
	<div id="window-locations"></div>
	<div id="monitis_notification_dialog_div"></div>

	<input name="add-option" type="button" value="Add configurable option" />
	<?if(isset($success) && $success):?>
	<div class="note success"><?=$success?></div>
	<?endif?>
	
	<?if(isset($error) && $error):?>
	<div class="note error"><?=$error?></div>
	<?endif?>

	<div class="monitors">
		<table class="datatable" cellspacing="1" cellpadding="3">
			<thead>
				<tr>
					<th style="width: 250px;">Configurable option</th>
					<th style="width: 120px;">Monitor type</th>
					<th style="width: 380px;">Monitor settings</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?foreach($groups as $groupId => $group):?>
				<tr>
					<td colspan="5"><big><b>Group: <?=$group['name']?></b></big></td>
				</tr>
				<?foreach($groups[$groupId]['configs'] as $configId => $config):?>
				<?foreach($groups[$groupId]['configs'][$configId]['options'] as $optionId => $option):?>
				<tr class="option" data-id="<?=$optionId?>" <?if($optionId == $optionIdToRemove):?>data-type="<?=$option['type']?>" data-settings="<?=$option['settings']?>"<?endif?>>
					<td><b><?=$config['name'] .':</b> '. $option['name']?></td>
					<td>
						<?if($optionId != $optionIdToRemove):?>
						<select name="type">
							<option value="http" <?if($option['type'] == 'http'):?>selected="selected"<?endif?>>HTTP</option>
							<option value="https" <?if($option['type'] == 'https'):?>selected="selected"<?endif?>>HTTPS</option>
							<option value="ping" <?if($option['type'] == 'ping'):?>selected="selected"<?endif?>>PING</option>
						</select>
						<?endif?>
					</td>
					<td>
						<?if($optionId != $optionIdToRemove):?>
						<table width="380px" cellspacing="1" cellpadding="3" border="0">
                                        		<tr>
                                            			<td class="fieldlabel" style="width: 100px;">Interval:</td>
                                            			<td>
									<select name="interval">
                                            					<option value="1" <?if($option['settings']['interval'] == 1):?>selected="selected"<?endif?>>1</option>
										<option value="3" <?if($option['settings']['interval'] == 3):?>selected="selected"<?endif?>>3</option>
										<option value="5" <?if($option['settings']['interval'] == 5):?>selected="selected"<?endif?>>5</option>
										<option value="10" <?if($option['settings']['interval'] == 10):?>selected="selected"<?endif?>>10</option>
										<option value="15" <?if($option['settings']['interval'] == 15):?>selected="selected"<?endif?>>15</option>
										<option value="20" <?if($option['settings']['interval'] == 20):?>selected="selected"<?endif?>>20</option>
										<option value="30" <?if($option['settings']['interval'] == 30):?>selected="selected"<?endif?>>30</option>
										<option value="40" <?if($option['settings']['interval'] == 40):?>selected="selected"<?endif?>>40</option>
										<option value="60" <?if($option['settings']['interval'] == 60):?>selected="selected"<?endif?>>60</option>
									</select>&nbsp;min.
                                            			</td>
                                        		</tr>
                                        		<tr>
                                            			<td class="fieldlabel">Timeout:</td>
                                            			<td><input type="text" size="15" name="timeout" value="<?=$option['settings']['timeout']?>"> (<span class="from">1000</span> — <span class="to">50 000</span> ms.)</td>
                                        		</tr>
                                        		<tr>
                                            			<td class="fieldlabel">Max locations:</td>
                                            			<td><input type="text" size="15" name="locations_max" value="<?=$option['settings']['locationsMax']?>"></td>
                                        		</tr>
                                        		<tr class="monitisMultiselect">
                                            			<td class="fieldlabel">Check locations:</td>
                                            			<td>
                                                			<label><span class="count monitisMultiselectText"><?=$option['settings']['locationsCount']?></span> locations</label>
                                                			<input type="button" class="locations btn" value="Select" data-location-ids="<?=$option['settings']['locationIds']?>" >
                                            			</td>
                                        		</tr>
							
							<tr style="display: none;">
								<td class="fieldlabel">Alert:</td>
								<td>
									<input type="button" class="alert btn" value="<?=($option['settings']['alertGroupName']?$option['settings']['alertGroupName']:'no alert')?>" data-group-id="<?=$option['settings']['alertGroupId']?>" data-group-name="<?=$option['settings']['alertGroupName']?>"  data-rules="<?=$option['settings']['alertRules']?>">
								</td>
							</tr>
                                    		</table>
						<?endif?>
					</td>
					<td class="actions">
						<?if($optionId != $optionIdToRemove):?>
						<?if($option['is_active']):?>
						<div><button class="deactivate btn btn-danger">Deactivate</button></div>
						<!--
						<div><button class="create btn">Create monitors</button></div>
						-->
						<?else:?>
						<div><button class="activate btn btn-success">Activate</button></div>
						<?endif?>
						<div><button class="save btn">Update</button></div>
						<!--
						<div><button class="remove btn btn-danger">Remove</button></div>
						-->
						<?else:?>
						<div><button class="restore btn">Restore</div></div>
						<?endif?>
					</td>
				</tr>
				<?endforeach?>
				<?endforeach?>
				<?endforeach?>
			</tbody>
		</table>
		<form id="actions" action="<?=MONITIS_APP_URL?>&monitis_page=options" method="post">
			<input type="hidden" name="action" />
			<input type="hidden" name="option_id" />
			<input type="hidden" name="type" />
			<input type="hidden" name="settings" />
			<input type="hidden" name="is_active" />
		</form>
	</div>
</div>
