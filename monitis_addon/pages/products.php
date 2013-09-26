<?
include 'incs/servicetop.php';

$allTypes = explode(",", MONITIS_EXTERNAL_MONITOR_TYPES);

class monitisProductClass extends whmcs_db {

    public function __construct() {}
	
	public function updateField( $field_id, $values ) {
		$where = array('id' => $field_id );
		update_query('tblcustomfields',$values,$where);
	}
	
	public function updateProductSettings( $pid,  $settings ) {
		$value = array( 'settings' => $settings );
		$where = array('product_id' => $pid );
		return update_query('mod_monitis_product', $value, $where);
	}
	
	public function activateProduct( $pid, $setting ) {
		//$values = array('product_id'=>$pid, 'is_active'=>1, 'status'=>'active');
		$values = array('product_id'=>$pid, 'settings' => $setting, 'status'=>'active');
		insert_query('mod_monitis_product', $values);
	}

	public function deactivateProduct( $pid ) {
		$sql = 'DELETE FROM mod_monitis_product WHERE product_id='.$pid;
		return $this->query_del( $sql );
		//$value = array( 'is_active' => 0 );
		//update_query('mod_monitis_product', $value, $where);
	}

	//////////////////////////////////////////////////
	
	public $monitisProducts = null;
	protected function monitisProducts() {
		$sql = 'SELECT * FROM mod_monitis_product';
		return $this->query( $sql );
	}
	
	public function getFieldById( $fielId) {
		$sql = 'SELECT fieldoptions FROM tblcustomfields  WHERE id='.$fielId;
		//return $this->query( $sql );
		$vals = $this->query( $sql );
		if( $vals ){ return $vals[0];
		} else { return null; }
	}
	
	public function linkProduct( $pid ) {
		if( $this->monitisProducts ) {
			for($i=0; $i<count($this->monitisProducts); $i++) {
				if( $this->monitisProducts[$i]['product_id'] == $pid )
					return $this->monitisProducts[$i];
			}
		}
		return null;
	}
	public function getCustomfields( & $flds) {
		$customfields = array();
		for( $i=0; $i<count($flds); $i++) {
			if( $flds[$i]['name'] == MONITIS_FIELD_WEBSITE ) { 
				$customfields['website'] = $flds[$i];
			}
			if( $flds[$i]['name'] == MONITIS_FIELD_MONITOR) {
				$customfields['monitortype'] = $flds[$i];
			}
		}
		return $customfields;
	}
	public function all_Products() {
		$command = "getproducts";
		$adminuser = 'admin'; //MonitisConf::getAdminName();
		$values = array();
		$results = localAPI($command,$values,$adminuser);

		if( $results && $results['result'] == "success") {
			$products = $results['products']['product'];
			return $products;
		}
		return null;
	}
	
	public function getproducts() {
		$this->monitisProducts = $this->monitisProducts();
		return $this->all_Products();
	}
}

$oMProduct = new monitisProductClass();
$action = monitisPost('action');
if ($action ) {
	$productId = monitisPostInt('productId');
	$monitorTypes = monitisPost('monitor_type');
	if (!empty($monitorTypes)) {
		
		$locs = explode(',', $_POST["locationIDs"]);
		$loc = array_map("intval", $locs);
        $timeout = monitisPostInt("timeout");
        //if ($timeout > 5000) {
        //    $timeout = 5000;
        //}

		$setting = json_encode(MonitisConf::$settings);
		$setting = json_decode( $setting, true );
		$set = $setting['http'];
		
		$set['timeout'] = $timeout;
		$set['timeoutPing'] = isset($_POST["timeoutPing"]) ? $_POST["timeoutPing"] : 1000; 
		
		$set['interval'] = $_POST["interval"]; 
		$set['locationIds'] = $loc;
		$set['locationsMax'] = (!$_POST["locationsMax"]) ? 0 : $_POST["locationsMax"];
		
		$set['alertGroupId'] = $_POST["alertGroupId"];
		$rule = $_POST['alertRules'];
		$rule = str_replace("~", '"', $rule);
		$set["alertRules"] = json_decode( $rule, true);


		$new_setting = json_encode($set);

        $website_id = monitisPostInt('website_id');
        $monType_id = monitisPostInt('monType_id');
        $monitor_types = implode(",", $monitorTypes);

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
            'fieldoptions' => $monitor_types,
            'required' => 'on',
            'showorder' => 'on',
            'showinvoice' => 'on'
        );

        switch ($action) {
            case 'activate':

                $oMProduct->updateField($website_id, $website_values);
                $oMProduct->updateField($monType_id, $monitor_values);
                $oMProduct->activateProduct($productId, $new_setting);
                break;
            case 'deactivate':
                $oMProduct->deactivateProduct($productId);
                break;
            case 'update':
				$oMProduct->updateField($website_id, $website_values);
				$oMProduct->updateField($monType_id, $monitor_values);
				//$oMProduct->updateProductSettings( $productId, $new_setting );
				$value = array( 'settings' => $new_setting );
				$where = array('product_id' => $productId );
				update_query('mod_monitis_product', $value, $where);
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
                $oMProduct->activateProduct($productId, $new_setting);
                break;
        }
    } else {
        MonitisApp::addError('Monitor type is required');
    }
}

$products = $oMProduct->getproducts();
//_dump($products);
?>
<?php MonitisApp::printNotifications(); ?>

<script type="text/javascript">
$(document).ready(function(){
	$('.monitis-products [name=timeout], .monitis-products [name=timeoutPing]').blur(function(){
		var val = parseInt($(this).val());
		if(isNaN(val)) val = 0;
		var from = parseInt($(this).parent().find('.from').text());
		var to = parseInt($(this).parent().find('.to').text());
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
<table class="monitis-products datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <tr>
        <th width="20px">&nbsp;</th>
        <th class="title" style="width:150px;">Product Name</th>
        <th class="title" style="width:150px;">Monitor type</th>
        <th class="title" style="width:380px;">Monitor settings</th>
        <th>&nbsp;</th>
    </tr>
    <?
    if ($products && count($products) > 0) {
		
        for ($i = 0; $i < count($products); $i++) {
            $product = $products[$i];
			$productId = $product['pid'];
            ?>
            <tr>
                <td>&nbsp;</td>
                <td width="150px" style="border-right:solid 1px #ebebeb;"><?php echo $products[$i]['name'] ?></td>
                <td class="customfields" valign="center" align="left" colspan="4" style="padding:0px;">
                    <form method="post" action="">
                        <table style="width:100%;min-width:1000px"cellspacing="1" cellpadding="6" border=0>
                            <tr>
                                <?
                                $types = null;
								//  product fields
								$fields = $oMProduct->getCustomfields( $product['customfields']['customfield'] );
								$isActive = false;
								$isMonitisProduct = false;
								if( $fields ) {
                                //if ($products[$i]['monitorType']) {
									$isMonitisProduct = true;
									$website_id = $fields['website']['id'];
									$monType_id = $fields['monitortype']['id'];

									$monTypes = $oMProduct->getFieldById( $monType_id);
									$types = explode(",", $monTypes['fieldoptions']);
                                }
								$monitisProduct = $oMProduct->linkProduct( $productId );
								$settings = null;
                                //if ($products[$i]['settings']) {
								if( $monitisProduct && $monitisProduct['settings']) {
                                    $settings = $monitisProduct['settings'];
                                } 
                                ?>
                                <td width="150px">
									<ul>
                                <?
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
                                    </ul>
								</td>   
                                <td width="380px">
								<?
										// $settings, $monitor_type, $prefix
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
									
									$timeOutPing = isset( $setts['timeoutPing'] ) ? $setts['timeoutPing'] : 1000;
									
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
										<td class="fieldlabel">Interval:</td>
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
									<tr>
										<td class="fieldlabel">Timeout:</td>
										<td><input type="text" size="15" name="timeout" value="<?=$timeOut?>"/> (<span class="from">1000</span> — <span class="to">50000</span> ms.)</td>
									</tr>
										<td class="fieldlabel">Ping timeout:</td>
										<td><input type="text" size="15" name="timeoutPing" value="<?=$timeOutPing?>"/> (<span class="from">1</span> — <span class="to">5000</span> ms.)</td>
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
                                 <? if( $isMonitisProduct ) { ?>
                                        <? if( $monitisProduct ) { ?>
                                            <input type="hidden" name="action" value="update" />
                                            <div><input type="submit" value="Deactivate" onclick="this.form.action.value='deactivate'" class="btn btn-danger" /></div>
                                            <div><input type="submit" value="Update" onclick="this.form.action.value='update'" class="btn" /></div>
										<? } else { ?>
                                            <div><input type="hidden" name="action" value="activate" /></div>
                                            <div><input type="submit" value="Activate" onclick="this.form.action.value='activate'" class="btn btn-success" /></div>

										<? } ?>
                                        <input type="hidden" name="website_id" value="<?=$website_id?>" />
                                        <input type="hidden" name="monType_id" value="<?=$monType_id?>" />
                                 <? } else { ?>

                                        <div><input type="hidden" name="action" value="setMonitorType" /></div>
                                        <div><input type="submit" value="Set monitor product" class="btn" /></div>
                                 <? } ?>
                                </td>
                       
                            </tr>
                        </table>
                        <input type="hidden" name="productId" value="<?=$productId?>" />
                    </form>
                </td>
              
            </tr>
        <?
    }
}
?>
</table>
