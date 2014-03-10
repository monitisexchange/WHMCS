<?php

//$locations = MonitisConf::$locations;
//$groupList = MonitisApi::getContactGroupList();
class monitisProductClass {
	public $websiteValue = array(
		'type' => 'product',
		'relid' => 0,
		'fieldname' => MONITIS_FIELD_WEBSITE,
		'fieldtype' => 'text',
		'description' => '',
		'required' => 'on',
		'showorder' => 'on',
		'showinvoice' => 'on'
	);
	public $monitorTypesValue = array(
		'type' => 'product',
		'relid' => 0,
		'fieldname' => MONITIS_FIELD_MONITOR,
		'fieldtype' => 'dropdown',
		'description' => '',
		'fieldoptions' => MONITIS_EXTERNAL_MONITOR_TYPES,
		'required' => 'on',
		'showorder' => 'on',
		'showinvoice' => 'on'
	);

	public function __construct() {
	}

	public function updateField($field_id, $values) {
		$where = array('id' => $field_id);
		update_query('tblcustomfields', $values, $where);
	}

	public function updateProductSettings($pid, $settings) {
		$value = array('settings' => $settings);
		$where = array('product_id' => $pid);
		return update_query('mod_monitis_product', $value, $where);
	}

	public function activateProduct($pid, $setting) {
		$values = array('product_id' => $pid, 'settings' => $setting, 'status' => 'active');
		insert_query('mod_monitis_product', $values);
	}

	public function updateProduct($productId, $setting) {
		$value = array('settings' => $setting);
		$where = array('product_id' => $productId);
		update_query('mod_monitis_product', $value, $where);
	}

	//////////////////////////////////////////////////
	//public $monitisProducts = null;
	protected function monitisProducts() {
		return monitisSqlHelper::query('SELECT * FROM mod_monitis_product');
	}

	public function getFieldById($fielId) {
		$vals = monitisSqlHelper::query('SELECT fieldoptions FROM tblcustomfields  WHERE id=' . $fielId);
		if ($vals)
			return $vals[0];
		else
			return null;
	}

	public function getCustomfields(& $flds) {
		$customfields = array();
		for ($i = 0; $i < count($flds); $i++) {
			if ($flds[$i]['name'] == MONITIS_FIELD_WEBSITE) {
				$customfields['website'] = $flds[$i];
			}
			if ($flds[$i]['name'] == MONITIS_FIELD_MONITOR) {
				$customfields['monitortype'] = $flds[$i];
			}
		}
		return $customfields;
	}

	public function allProducts() {
		$command = "getproducts";
		$adminuser = MonitisHelper::getAdminName();
		$values = array();
		$results = localAPI($command, $values, $adminuser);

		if ($results && $results['result'] == "success") {
			$products = $results['products']['product'];
			$otherProducts = array();
			if ($products) {

				$activeProducts = $this->monitisProducts();

				for ($i = 0; $i < count($products); $i++) {
					if (strtolower($products[$i]['type']) == 'other') {
						$product = $products[$i];

						$fields = $this->getCustomfields($product['customfields']['customfield']);

						$isMonitisProduct = false;
						if ($fields) {
							$isMonitisProduct = true;
							$website_id = $fields['website']['id'];
							$monType_id = $fields['monitortype']['id'];

							$monTypes = $this->getFieldById($monType_id);
							$types = $monTypes['fieldoptions'];

							$monitisProduct = MonitisHelper::in_array($activeProducts, 'product_id', $product['pid']);
							$settings = null;
							if ($monitisProduct && $monitisProduct['settings']) {
								$settings = $monitisProduct['settings'];
							}
							$product['monitisProduct'] = array(
								'product_id' => $product['pid'],
								'website_id' => $fields['website']['id'],
								'monType_id' => $fields['monitortype']['id'],
								'types' => $types,
								'settings' => $settings
							);
						}
						$otherProducts[] = $product;
					}
				}
			}
			return $otherProducts;
		}
		return null;
	}

	public function getProducts() {
		return $this->allProducts();
	}

	public function activateProducts($productIds) {
		$result = array('status' => 'error', 'products' => array());
		$products = $this->allProducts();
		if ($products) {

			$result['status'] = 'ok';
			for ($i = 0; $i < count($productIds); $i++) {
				$prdct = MonitisHelper::in_array($products, 'pid', $productIds[$i]);
				$productId = intval($productIds[$i]);
				if ($prdct) {
					if (isset($prdct['monitisProduct'])) {
						// monitis product is active                                           
						if ($prdct['monitisProduct']['settings']) {
							$result['products'][] = array('status' => 'warning', 'msg' => 'Product "' . $prdct['name'] . '" already has activated');
						} else {
							// activate 
							$setting = MonitisConf::$settings['ping'];
							$setting['timeoutPing'] = 1000;
							$setting['timeout'] = 10;
							$this->activateProduct($productId, json_encode($setting));
							$result['products'][] = array('status' => 'ok', 'msg' => 'Product "' . $prdct['name'] . '" activated');
						}
					} else {
						// set up as monitoring product
						$this->websiteValue['relid'] = $productId;
						insert_query('tblcustomfields', $this->websiteValue);
						$this->monitorTypesValue['relid'] = $productId;
						insert_query('tblcustomfields', $this->monitorTypesValue);
						$setting = MonitisConf::$settings['ping'];
						$setting['timeoutPing'] = 1000;
						$setting['timeout'] = 10;
						$this->activateProduct($productId, json_encode($setting));
						$result['products'][] = array('status' => 'ok', 'msg' => 'Product "' . $prdct['name'] . '" was set up as monitoring product successful');
					}
				} else {
					$result['products'][] = array('status' => 'error', 'msg' => 'Error product ' . $productId);
				}
			}
		}
		return $result;
	}

	public function deactivateProducts($ids) {
		return monitisSqlHelper::altQuery('DELETE FROM mod_monitis_product WHERE product_id in (' . $ids . ')');
	}
}

$oMProduct = new monitisProductClass();
$action = monitisPost('action_type');
if ($action && $action == 'edit_product') {
    $productId = monitisPostInt('productId');

	//_dump($_POST);

	$monitorTypes = monitisPost('monitor_type');
	if (!empty($monitorTypes)) {

		$locs = explode(',', $_POST["locationIds"]);
		$loc = array_map("intval", $locs);

		$set = MonitisConf::$settings['ping'];
		$set['timeout'] = $_POST["timeout"];
		$set['timeoutPing'] = isset($_POST["timeoutPing"]) ? $_POST["timeoutPing"] : 1000;
		$set['interval'] = $_POST["interval"];
		$set['locationIds'] = $loc;
		$set['locationsMax'] = (!$_POST["locationsMax"]) ? 3 : $_POST["locationsMax"];

		$new_setting = json_encode($set);

		$website_id = monitisPostInt('website_id');
		$monType_id = monitisPostInt('monType_id');
		//$monitor_types = implode(",", $monitorTypes);

		$website_values = $oMProduct->websiteValue;
		$website_values["relid"] = $productId;

		$monitor_values = $oMProduct->monitorTypesValue;
		$monitor_values["relid"] = $productId;
		//$monitor_values["fieldoptions"] = $monitor_types;
		$monitor_values["fieldoptions"] = $monitorTypes;

		if ($website_id > 0) {
			$oMProduct->updateField($website_id, $website_values);
		} else {
			insert_query('tblcustomfields', $website_values);
		}

		if ($monType_id > 0) {
			$oMProduct->updateField($monType_id, $monitor_values);
		} else {
			insert_query('tblcustomfields', $monitor_values);
		}
		if ($_POST['edit_type'] == 'create') {
			$oMProduct->activateProduct($productId, $new_setting);
			MonitisApp::addMessage('Product "' . $_POST["productName"] . '" activated successfully');
		} else {
			$oMProduct->updateProduct($productId, $new_setting);
			MonitisApp::addMessage('Product "' . $_POST["productName"] . '" updated successfully');
		}
	} else {
		MonitisApp::addError('Monitor type is required');
	}
} elseif ($action) {
	$productIds = monitisPost('productIds');
	if ($productIds) {
		switch ($action) {
			case 'activate':
				$resp = $oMProduct->activateProducts($productIds);
				if ($resp['status'] == 'ok') {
					$prdcts = $resp['products'];
					for ($i = 0; $i < count($prdcts); $i++) {
						if ($prdcts[$i]['status'] == 'error') {
							MonitisApp::addError($prdcts[$i]['msg']);
						} elseif ($prdcts[$i]['status'] == 'warning') {
							//MonitisApp::addWarning($prdcts[$i]['msg']);
						} else {
							//MonitisApp::addMessage($prdcts[$i]['msg']);
						}
					}
				}
				break;
			case 'deactivate':
				$ids = implode(',', $productIds);
				$oMProduct->deactivateProducts($ids);
				break;
		}
	}
}
$products = $oMProduct->getProducts();

MonitisApp::printNotifications();
?>
<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisproductdialog.js?<?php echo rand(1, 1000) ?>"></script>
<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr>
		<td width="50%" align="left">
			<b><?php echo count($products) ?></b> Products Found, Page <b>1</b> of <b>1</b>
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
	<table class="monitis-products datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
		<thead>
			<tr>
				<th width="20"><input type="checkbox" class="monitis-checkbox-all" ></th>
				<th>Product</th>
				<th>Monitor Types</th>
				<th>Check Interval (min.)</th>
				<th>Timeout (sec.)</th>
				<th>Ping Timeout (ms.)</th>
				<th>Max Locations</th>
				<th>Status</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ($products && count($products) > 0) {

				for ($i = 0; $i < count($products); $i++) {
					$product = $products[$i];
					$productId = $product['pid'];
					$settings = null;

					$isMonitisProduct = false;
					$monitisProduct = false;
					$timeOut = $timeOutPing = $interval = $locationsMax = $monitor_type = '-';
					$types = '-';
					$website_id = $monType_id = 0;
					$settingProduct = '';
					if (isset($product['monitisProduct'])) {
						$isMonitisProduct = true;
						$website_id = $product['monitisProduct']['website_id'];
						$monType_id = $product['monitisProduct']['monType_id'];

						$types = $product['monitisProduct']['types'];
						$settings = $product['monitisProduct']['settings'];
						$timeOut = 10;
						$timeOutPing = 1000;
						if ($settings) {
							$monitisProduct = true;
							$settings = json_decode($settings, true);

							$interval = $settings['interval'];
							$timeOut = $settings['timeout'];
							$timeOutPing = isset($settings['timeoutPing']) ? $settings['timeoutPing'] : 1000;
							$locationsMax = $settings['locationsMax'];

							$settingProduct = array(
								'interval' => $interval,
								'timeout' => $timeOut,
								'name' => $products[$i]['name'],
								'types' => $types,
								'locationIds' => $settings['locationIds'],
								'timeoutPing' => $timeOutPing,
								'locationsMax' => $settings['locationsMax']
							);
							$settingProduct = json_encode($settingProduct);
							$settingProduct = str_replace('"', "~", $settingProduct);
						}
					}
					if (!$settings) {
						$timeOut = 10;
						$timeOutPing = 1000;
						$setts = MonitisConf::$settings['http'];
						$settingProduct = array(
							'interval' => $setts['interval'],
							'timeout' => $timeOut,
							'name' => $products[$i]['name'],
							'types' => MONITIS_EXTERNAL_MONITOR_TYPES,
							'locationIds' => $setts['locationIds'],
							'timeoutPing' => $timeOutPing,
							'locationsMax' => $setts['locationsMax']
						);
						if ($isMonitisProduct) {
							$settingProduct['types'] = $types;
						}
						$settingProduct = json_encode($settingProduct);
						$settingProduct = str_replace('"', "~", $settingProduct);
					}
					?>
					<tr>
						<td><input type="checkbox" class="monitis-checkbox" value="<?php echo $productId ?>" name="productIds[]" /></td>
						<td><?php echo $products[$i]['name'] ?></td>
						<td><?php echo $types ?></td>
						<td><?php echo $interval ?></td>
						<td><?php echo $timeOut ?></td>
						<td><?php echo $timeOutPing ?></td>
						<td><?php echo $locationsMax ?></td>
						<td>
							<?php
							$action = 'update';
							if ($isMonitisProduct) {
								?>
								<?php if ($monitisProduct) { ?>
									<span class="textgreen">Active</span>

								<?php } else { ?>
									<span class="textred">Inactive</span>

								<?php  } ?>
								<?php
							} else {
								$action = 'create';
								?>
								<span class="textred">Inactive</span>
							<?php } ?>
						</td>
						<td class="action">
							<?php if ($monitisProduct) { ?>
								<input type="image" src="images/edit.gif" class="monitis_product_edit" data-id="<?php echo $productId ?>" data-settings='<?php echo $settingProduct ?>' 
									   website="<?php echo $website_id ?>" monType_id="<?php echo $monType_id ?>" edit_type="<?php echo $action ?>" title="Edit product settings" />
								   <?php } ?>
						</td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td colspan="9">No active products available.</td>
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
</form>
<form method="post" action="" id="productEditForm">
	<input type="hidden" name="website_id" value="" />
	<input type="hidden" name="monType_id" value="" />
	<input type="hidden" name="productId" value="" />
	<input type="hidden" name="productName" value="" />
	<input type="hidden" name="monitor_type" value="" />
	<input type="hidden" name="locationIds" value="" />
	<input type="hidden" name="timeout" value="" />
	<input type="hidden" name="timeoutPing" value="" />
	<input type="hidden" name="interval" value="" />
	<input type="hidden" name="locationsMax" value="" />
	<input type="hidden" name="edit_type" value="" />
	<input type="hidden" name="action_type" value="edit_product" />
</form>

<script type="text/javascript">
$(document).ready(function() {

	$('.monitis_product_edit').click(function(event) {
		event.preventDefault();
		var product = $(this);
		var productId = product.attr("data-id");
		var monType_id = product.attr("monType_id");
		var website = product.attr("website");
		var edit_type = product.attr("edit_type");

		var options = {
			type: 'product',
			settings: product.attr("data-settings"),
			locations: <?php echo json_encode(MonitisConf::$locations) ?>
		}
		new monitisProductDialog(options, function(response) {
			var form = $('#productEditForm');
			$(form).find('input[name="productName"]').val(response.name);
			$(form).find('input[name="edit_type"]').val(edit_type);
			$(form).find('input[name="productId"]').val(productId);

			$(form).find('input[name="monType_id"]').val(monType_id);
			$(form).find('input[name="website_id"]').val(website);

			$(form).find('input[name="monitor_type"]').val(response.types);
			$(form).find('input[name="locationsMax"]').val(response.locationsMax);
			$(form).find('input[name="locationIds"]').val(response.locationIds.join());

			$(form).find('input[name="interval"]').val(response.interval);
			$(form).find('input[name="timeout"]').val(response.timeout);
			$(form).find('input[name="timeoutPing"]').val(response.timeoutPing);
			$(form).submit();
		});

	});
});
</script>