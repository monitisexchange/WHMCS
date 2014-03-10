<?php
$locations = MonitisConf::$locations;

class monitisAddonClass {

	public function __construct() {
		
	}

	public function addonsList() {
		$sql = 'SELECT id, name, mod_monitis_addon.*
		FROM tbladdons
		LEFT JOIN mod_monitis_addon on ( tbladdons.id = mod_monitis_addon.addon_id )';
		return monitisSqlHelper::query($sql);
	}

	public function deactivateAddon($ids) {
		return monitisSqlHelper::altQuery('DELETE FROM mod_monitis_addon WHERE addon_id in (' . $ids . ')');
	}

	public function updateAddonSettings($addonId, $settings, $type) {

		$addon = monitisSqlHelper::objQuery('SELECT * FROM mod_monitis_addon WHERE addon_id=' . $addonId);
		if ($addon && count($addon) > 0) {
			$value = array('settings' => $settings, 'type' => $type);
			$where = array('addon_id' => $addonId);
			update_query('mod_monitis_addon', $value, $where);
			return 'update';
		} else {
			$value = array('addon_id' => $addonId, 'type' => $type, 'settings' => $settings, 'status' => 'active');
			insert_query('mod_monitis_addon', $value);
			return 'create';
		}
	}

}

$allTypes = explode(",", MONITIS_EXTERNAL_MONITOR_TYPES);

$oAddon = new monitisAddonClass();
$action = monitisPost('action_type');
if ($action && $action == 'edit_product') {
	$monitor_type = monitisPost('monitor_type');
	$addonId = monitisPostInt('productId');

	if ($_POST["locationIds"] && $_POST["locationsMax"] > 0) {

		$locs = explode(',', $_POST["locationIds"]);
		$loc = array_map("intval", $locs);

		$set = MonitisConf::$settings[$monitor_type];
		$set['timeout'] = $_POST["timeout"];
		$set['interval'] = $_POST["interval"];
		$set['locationIds'] = $loc;
		$set['locationsMax'] = (!$_POST["locationsMax"]) ? 0 : $_POST["locationsMax"];

		$new_setting = json_encode($set);

		$resp = $oAddon->updateAddonSettings($addonId, $new_setting, $monitor_type);
		/*
		if ($resp['create']) {
			MonitisApp::addMessage('Addon "' . $_POST["productName"] . '" activated successfully');
		} else {
			MonitisApp::addMessage('Addon "' . $_POST["productName"] . '" updated successfully');
		}
		*/
		MonitisApp::addMessage('Addon "' . $_POST["productName"] . '" updated successfully');
	} else {
		MonitisApp::addError('All fields required');
	}
} elseif ($action) {

	$productIds = monitisPost('productIds');
	switch ($action) {
		case 'activate':
			if ($productIds) {
				for ($i = 0; $i < count($productIds); $i++) {
					$monitor_type = 'http';
					$setting = MonitisConf::$settings[$monitor_type];
					$oAddon->updateAddonSettings($productIds[$i], json_encode($setting), $monitor_type);
				}
			}
			break;
		case 'deactivate':
			if ($productIds) {
				$ids = implode(',', $productIds);
				$oAddon->deactivateAddon($ids);
			}
			break;
		case 'automate':
			$productId = monitisPost('productId');
			header('location: ' . MONITIS_APP_URL . '&monitis_page=client/addonsresult&addonid=' . $productId);
			break;
	}
}

$products = $oAddon->addonsList();

MonitisApp::printNotifications();
?>
<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr>
		<td width="50%" align="left">
			<b><?php echo count($products) ?></b> Addons Found, Page <b>1</b> of <b>1</b>
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
<form method="post" action="">
	<table class="datatable" border=0 cellspacing="1" cellpadding="3">
		<thead>
			<tr>
				<th width="20px"><input type="checkbox" class="monitis-checkbox-all" /></th>
				<th>Addon</th>
				<th>Monitor Type</th>
				<th>Check Interval (min.)</th>
				<th>Timeout</th>
				<th>Max Locations</th>
				<th>Status</th>
				<th width="50">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ($products && count($products) > 0) {
				for ($i = 0; $i < count($products); $i++) {
					$productId = $products[$i]['id'];
					$settings = null;
					$isActive = false;
					$timeout = $interval = $locationsMax = $monitor_type = '-';
					$timeoutTxt = '';
					if (isset($products[$i]["type"]) && !empty($products[$i]["type"])) {
						$monitor_type = $products[$i]["type"];
						$settings = json_decode($products[$i]["settings"], true);
						$timeout = $settings['timeout'];
						$timeoutTxt = 'sec.';
						if ($monitor_type == 'ping') {
							$timeoutTxt = 'ms.';
						}
						$interval = $settings['interval'];
						$locationsMax = $settings['locationsMax'];
						$isActive = true;
						$settingProduct = array(
							'interval' => $interval,
							'timeout' => $timeout,
							'name' => $products[$i]['name'],
							'types' => $monitor_type,
							'locationIds' => $settings['locationIds'],
							'locationsMax' => $settings['locationsMax']
						);
						$settingProduct = json_encode($settingProduct);
						$settingProduct = str_replace('"', "~", $settingProduct);
					} else {
						$setts = MonitisConf::$settings['http'];
						$settingProduct = array(
							'interval' => $setts['interval'],
							'timeout' => $setts['timeout'],
							'name' => $products[$i]['name'],
							'types' => 'http',
							'locationIds' => $setts['locationIds'],
							'locationsMax' => $setts['locationsMax']
						);
						$settingProduct = json_encode($settingProduct);
						$settingProduct = str_replace('"', "~", $settingProduct);
					}
					?>
					<tr>
						<td><input type="checkbox" class="monitis-checkbox" value="<?php echo $productId ?>" name="productIds[]" /></td>

						<td><?php echo $products[$i]['name'] ?></td>
						<td><?php echo $monitor_type ?></td>

						<td><?php echo $interval ?></td>
						<td><?php echo $timeout ?> <?php echo $timeoutTxt ?></td>
						<td><?php echo $locationsMax ?></td>
						<td>
							<?php if ($isActive) { ?>
								<span class="textgreen">Active</span>
							<?php } else { ?>
								<span class="textred">Inactive</span>
							<?php } ?>
						</td>				
						<td class="action">
							<?php if ($isActive) { ?>
								<input type="image" src="images/edit.gif" class="monitis_product_edit" data-id="<?php echo $productId ?>" data-settings='<?php echo $settingProduct ?>' title="Edit addon settings" />
								<input type="image" src="images/icons/autosettings.png" class="monitis_create_monitor monitis_link_button" onclick="this.form.action_type.value = 'automate';
								this.form.productId.value = '<?php echo $productId ?>';" title="Create monitors" />
								   <?php } ?>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="8">No active addons available.</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<div class="monitis-page-bottom">
		<label>With Selected: </label> 
		<input type="submit" value="Activate" onclick="this.form.action_type.value = 'activate'" class="btn btn-success monitis-checkbox-subject monitis_link_button activate" disabled="disabled" />
		<input type="submit" value="Deactivate" onclick="this.form.action_type.value = 'deactivate'" class="btn btn-danger monitis-checkbox-subject monitis_link_button deactivate" disabled="disabled" />
	</div>

	<input type="hidden" name="action_type" value="1" />
	<input type="hidden" name="productId" value="1" />
</form>

<form method="post" action="" id="productEditForm">
	<input type="hidden" name="productName" value="" />
	<input type="hidden" name="productId" value="" />
	<input type="hidden" name="monitor_type" value="<?php echo $monitor_type ?>" />
	<input type="hidden" name="locationIds" value="" />
	<input type="hidden" name="timeout" value="" />
	<input type="hidden" name="interval" value="" />
	<input type="hidden" name="locationsMax" value="" />
	<input type="hidden" name="action_type" value="edit_product" />
</form>

<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisproductdialog.js?<?php echo rand(1, 1000) ?>"></script>
<script type="text/javascript">

$(document).ready(function() {
	$('.monitis_product_edit').click(function(event) {
		event.preventDefault();
		var product = $(this);
		var productId = product.attr("data-id");

		var options = {
			type: 'addon',
			settings: product.attr("data-settings"),
			locations: <?php echo json_encode(MonitisConf::$locations) ?>
		}
		new monitisProductDialog(options, function(response) {

			if (response.locationIds) {
				var form = $('#productEditForm');
				$(form).find('input[name="productName"]').val(response.name);
				$(form).find('input[name="productId"]').val(productId);

				$(form).find('input[name="monitor_type"]').val(response.types);
				$(form).find('input[name="locationsMax"]').val(response.locationsMax);
				$(form).find('input[name="locationIds"]').val(response.locationIds.join());

				$(form).find('input[name="interval"]').val(response.interval);
				$(form).find('input[name="timeout"]').val(response.timeout);
				//$(form).find('input[name="timeoutPing"]').val(response.timeoutPing);
				$(form).submit();
			} else {
//console.log(response);
			}
		});

	});
});
</script>