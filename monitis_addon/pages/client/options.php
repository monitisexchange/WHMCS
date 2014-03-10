<?php
/*
  $query = 'DROP TABLE `mod_monitis_options`';
  mysql_query($query);

  $query = 'CREATE TABLE `mod_monitis_options`(
  `option_id` INT NOT NULL,
  `type` VARCHAR(50),
  `settings` TEXT,
  `is_active` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`option_id`)
  )';
  mysql_query($query);
 */


if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'save':
			$subId = (int) $_POST['sub_id'];
			$type = mysql_real_escape_string($_POST['type']);
			$settings = mysql_real_escape_string($_POST['settings']);
			$isNew = (int) $_POST['is_new'] ? true : false;
			if ($isNew) {
				$query = '
					INSERT INTO `mod_monitis_options` (option_id, settings, type, is_active)
					VALUES ("' . $subId . '", "' . $settings . '", "' . $type . '", 1)
				';
			} else {
				$query = '
					UPDATE `mod_monitis_options`
					SET option_id = "' . $subId . '", settings = "' . $settings . '", type = "' . $type . '"
					WHERE option_id = ' . $subId . '
				';
			}

			if (mysql_query($query)) {
				/*
				  if($_POST['is_create_monitors'] === '1'){
				  header('location: ' . MONITIS_APP_URL . '&monitis_page=optionsResult&id='.$optionId);
				  }
				 */
				if ($isNew) {
					$success = 'Configurable Option has been linked.';
				} else {
					$success = 'Configurable Option settings have been saved.';
				}
			} else {
				$error = 'An error occurred. Try again later.';
			}
			break;
		case 'activate':
			$isActive = true;
		case 'deactivate':
			if (!isset($isActive))
				$isActive = false;
			$subIds = $_POST['sub_ids'];

			foreach ($subIds as $subId) {
				$subId = (int) $subId;
				$query = '
					UPDATE `mod_monitis_options`
					SET is_active = "' . $isActive . '"
					WHERE option_id = ' . $subId . '
				';
				mysql_query($query);
			}
		   /* if ($isActive && $subIds) {
				$success = 'Option have been activated.';
			} elseif ($subIds != null) {
				$success = 'Option have been deactivated.';
			} */
			break;
	}
}

$locations = MonitisConf::$locations;
$contactGroups = MonitisApi::getContactGroupList();

$query = '
	SELECT groups.id AS group_id, groups.name AS group_name, options.id AS option_id, options.optionname AS option_name,
		subs.id AS sub_id, subs.optionname AS sub_name, moptions.option_id as oid,
		moptions.type, moptions.settings, moptions.is_active
	FROM `mod_monitis_options` AS moptions
		LEFT JOIN `tblproductconfigoptionssub` AS subs
			ON subs.id = moptions.option_id
		LEFT JOIN `tblproductconfigoptions` AS options
			ON options.id = subs.configid
		LEFT JOIN `tblproductconfiggroups` AS groups
			ON groups.id = options.gid
';
$result = mysql_query($query);

$groups = array();
$subsCount = 0;
while ($row = mysql_fetch_assoc($result)) {
	++$subsCount;

	if (!is_array($groups[$row['group_id']])) {
		$groups[$row['group_id']] = array(
			'name' => $row['group_name'],
			'options' => array()
		);
	}

	if (!is_array($groups[$row['group_id']]['options'][$row['option_id']])) {
		$groups[$row['group_id']]['options'][$row['option_id']] = array(
			'name' => $row['option_name'],
			'options' => array()
		);
	}

	$settings = html_entity_decode($row['settings']);
	$groups[$row['group_id']]['options'][$row['option_id']]['subs'][$row['sub_id']] = array(
		'name' => $row['sub_name'],
		'type' => $row['type'],
		'is_active' => $row['is_active'],
		'settings' => json_decode($settings, true),
		'settingsEncoded' => str_replace('"', '~', $settings)
	);
}

$query = '
	SELECT groups.id AS group_id, groups.name AS group_name, options.id AS option_id, options.optionname AS option_name, sub.id AS sub_id, sub.optionname AS sub_name
	FROM `tblproductconfigoptionssub` AS sub
		LEFT JOIN `tblproductconfigoptions` AS options
			ON options.id = sub.configid
		LEFT JOIN `tblproductconfiggroups` AS groups
			ON groups.id = options.gid		
';
$result = mysql_query($query);

$optionGroups = array();
while ($row = mysql_fetch_assoc($result)) {
	if (!is_array($optionGroups[$row['group_id']])) {
		$optionGroups[$row['group_id']] = array(
			'name' => $row['group_name'],
			'options' => array()
		);
	}
	if (!is_array($optionGroups[$row['group_id']]['options'][$row['option_id']])) {
		$optionGroups[$row['group_id']]['options'][$row['option_id']] = array(
			'name' => $row['option_name'],
			'options' => array()
		);
	}

	//Check is this option in already added options
	if (
			isset($groups[$row['group_id']]) &&
			isset($groups[$row['group_id']]['options'][$row['option_id']]) &&
			isset($groups[$row['group_id']]['options'][$row['option_id']]['subs'][$row['sub_id']])
	) {
		//Check is this option just removed
		if (isset($optionIdToRemove) && $row['sub_id'] == $optionIdToRemove) {
			$hasMonitor = false;
		} else {
			$hasMonitor = true;
		}
	} else {
		$hasMonitor = false;
	}

	$optionGroups[$row['group_id']]['options'][$row['option_id']]['subs'][$row['sub_id']] = array(
		'name' => $row['sub_name'],
		'hasMonitor' => $hasMonitor
	);
}


if (isset($optionIdToRemove)) {
	$query = '
		DELETE FROM `mod_monitis_options`
		WHERE option_id = ' . $optionIdToRemove . '
	';
	if (mysql_query($query)) {
		$success = 'Option have been removed.';
	} else {

		$error = 'An error occurred. Try again later.';
	}
}

MonitisApp::addMessage($success);
MonitisApp::addError($error);
?>

<style type="text/css">
.monitis-options{
	text-align: left;
}
.monitis-options .datatable .actions div{
	padding: 0 0 10px 30px;
}
.monitis-options .datatable tbody tr th{
	background: #BBBBBB;
	color: black;
}
.datatable{
	width: 100%;
}
</style>

<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisproductdialog.js?<?php echo rand(1, 1000) ?>"></script>
<script type="text/javascript">
$(document).ready(function() {
	var locations = <?php echo json_encode($locations) ?>;
	var optionGrups = <?php echo json_encode($optionGroups) ?>;

	$('.monitis-options-edit').click(function() {
		$option = $(this).closest('tr');
		monitisProductDialog({
			'type': 'addon',
			'name': $option.find('td').eq(1).text(),
			'settings': $option.attr('data-settings'),
			'locations': locations,
			'optionGrups': optionGrups
		}, function(settings) {
			$('.monitis-options-form-save').find('[name=sub_id]').val($option.attr('data-id'));
			$('.monitis-options-form-save').find('[name=type]').val(settings.types);
			$('.monitis-options-form-save').find('[name=settings]').val(JSON.stringify(settings));
			$('.monitis-options-form-save').submit();
		});
	});

	$('.monitis-options-link input').click(function() {
		monitisProductDialog({
			'type': 'option',
			'name': 'New Configurable Option',
			'settings': {
				'types': 'http',
				'interval': 1,
				'timeout': 1000,
				'locationIds': [1, 9, 10],
				'locationsMax': 5
			},
			'locations': locations,
			'optionGrups': optionGrups
		}, function(settings) {
			$('.monitis-options-form-save').find('[name=sub_id]').val(settings.subId);
			$('.monitis-options-form-save').find('[name=type]').val(settings.types);
			$('.monitis-options-form-save').find('[name=is_new]').val(1);
			$('.monitis-options-form-save').find('[name=settings]').val(JSON.stringify(settings));
			$('.monitis-options-form-save').submit();
		});
	});
	$('.monitis-options .monitis_checkall').click(function() {
		if ($('.monitis-options td .monitis_checkall:checked').length) {
			if (navigator.userAgent.indexOf('Firefox') != -1) {
				//Firefox                               
				$('.monitis-options .activate, .monitis-options .deactivate').attr('disabled', true);
			} else {
				$('.monitis-options .activate, .monitis-options .deactivate').prop('disabled', false);
			}
		}
		else {
			if (navigator.userAgent.indexOf('Firefox') != -1) {
				//Firefox
				$('.monitis-options .activate, .monitis-options .deactivate').attr('disabled', false);
			} else {
				$('.monitis-options .activate, .monitis-options .deactivate').prop("disabled", true);
			}
		}
	});
});
</script>

<div class="monitis-options">
<?php MonitisApp::printNotifications(); ?>
	<div class="monitis-options-link">
		<input class="btn" type="button" value="Link a Configurable Option" />
	</div>
	<div class="monitors">
		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr>
				<td width="50%" align="left">
					<b><?php echo $subsCount ?></b> Options Found, Page <b>1</b> of <b>1</b>
				</td>
				<td width="50%" align="right">
					Jump to Page:&nbsp;&nbsp; 
					<select name="page" onchange="return false;">
						<option value="1" selected>1</option>
					</select>
					<input type="submit" value="Go" onclick="return false;" />		
				</td>
			</tr>
		</table>
		<form action="" method="post">
			<table class="datatable" cellspacing="1" cellpadding="3">
				<thead>
					<tr>
						<th width="20"><input type="checkbox" class="monitis-checkbox-all" /></th>
						<th>Configurable Option</th>
						<th>Monitor Type</th>
						<th>Check Interval (min.)</th>
						<th>Timeout</th>
						<th>Max Locations</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if (count($groups)): ?>
				<?php foreach ($groups as $group): ?>
					<tr>
						<th colspan="8" class="monitis-options-group">Group: <?php echo $group['name'] ?></th>
					</tr>
					<?php foreach ($group['options'] as $option): ?>
					<?php foreach ($option['subs'] as $subId => $sub): ?>
					<tr class="option" data-id="<?php echo $subId ?>" data-settings="<?php echo $sub['settingsEncoded'] ?>">
						<td><input type="checkbox" class="monitis-checkbox" name="sub_ids[]" value="<?php echo $subId ?>"  /></td>
						<td><b><?php echo $option['name'] . ':</b> ' . $sub['name'] ?></td>
						<td><?php echo $sub['type'] ?></td>
						<td><?php echo $sub['settings']['interval'] ?></td>
						<td><?php echo $sub['settings']['timeout'] ?> <?php echo $sub['settings']['types'] == 'ping' ? 'ms.' : 'sec.' ?></td>
						<td><?php echo $sub['settings']['locationsMax'] ?></td>
						<td>
						<?php if ($sub['is_active']): ?>
							<span class="textgreen">Active</span>
						<?php else: ?>
							<span class="textred">Inactive</span>
						<?php endif ?>
						</td>
						<td width="50">
						<?php if ($sub['is_active']): ?>
							<a href="#" class="monitis-options-edit" title="Edit Configurable Option settings"><img src="images/edit.gif" alt="Edit Configurable Option settings" /></a>
							<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=client/optionsresult&id=<?php echo $subId ?>" class="monitis-options-create monitis_link_button" title="Create monitors"><img src="images/icons/autosettings.png" alt="Create monitors" /></a>
						<?php endif ?>
						</td>
					</tr>
					<?php endforeach ?>
					<?php endforeach ?>
					<?php endforeach ?>
					<?php else: ?>
					<tr>
						<td colspan="8">No active products available.</td>
					</tr>
					<?php endif ?>
				</tbody>
			</table>
			<div class="monitis-page-bottom">
				<label>With Selected: </label>
				<input type="hidden" name="action" value="" />
				<input type="submit" value="Activate" onclick="this.form.action.value = 'activate'" class="btn btn-success monitis-checkbox-subject monitis_link_button activate" disabled="disabled" />
				<input type="submit" value="Deactivate" onclick="this.form.action.value = 'deactivate'"  class="btn btn-danger monitis-checkbox-subject monitis_link_button deactivate" disabled="disabled" />
			</div>
		</form>
		<form class="monitis-options-form-save" action="" method="post">
			<input type="hidden" name="action" value="save" />
			<input type="hidden" name="type" />
			<input type="hidden" name="sub_id" />
			<input type="hidden" name="is_new" value="0" />
			<input type="hidden" name="settings" />
		</form>
	</div>
</div>
