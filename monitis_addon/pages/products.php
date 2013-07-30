<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');

$oMProduct = new productClass();


$action = monitisPost('action');
$productId = monitisPostInt('productId');
$monitor_type = monitisPost('monitor_type');
if ( $action && $productId > 0 ) {
	if( !empty($monitor_type) ) {	
		
		$website_id = monitisPostInt('website_id');
		$monType_id = monitisPostInt('monType_id');
		$monitor_type = implode(",", $monitor_type);
		
		$website_values = array(
			'type' => 'product',
			'relid' => $productId,
			'fieldname' => MONITIS_FIELD_WEBSITE,
			'fieldtype' => 'text',
			'description'=> '',
			'required'=> 'on',
			'showorder' => 'on',
			'showinvoice' => 'on'
		);
		$monitor_values = array(
			'type' => 'product',
			'relid' => $productId,
			'fieldname' => MONITIS_FIELD_MONITOR,
			'fieldtype' => 'dropdown',
			'description'=> '',
			'fieldoptions'=> $monitor_type,
			'required'=> 'on',
			'showorder' => 'on',
			'showinvoice' => 'on'
		);
		
		switch( $action ) {
			case 'activate':
				$oMProduct->updateField( $website_id, $website_values );
				$oMProduct->updateField( $monType_id, $monitor_values );
				$oMProduct->activateProduct( $productId );
			break;
			case 'deactivate':
				$oMProduct->deactivateProduct( $productId );
			break;
			case 'update':
				$oMProduct->updateField( $website_id, $website_values );
				$oMProduct->updateField( $monType_id, $monitor_values );				
			break;
			case 'setMonitorType':
				if( $website_id == 0 ) {
					$website_id = insert_query('tblcustomfields', $website_values);
				} else {
					$oMProduct->updateField( $website_id, $website_values );
				}
				if( $monType_id == 0 ) {
					$monType_id = insert_query('tblcustomfields', $monitor_values);
				} else {
					$oMProduct->updateField( $monType_id, $monitor_values );
				}
				$oMProduct->activateProduct( $productId );
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

.datatable .customfields  td{
	padding:3px 10px;
	width:30%;
}
.datatable .customfields ul{
	list-style-type: none;
	width:250px;
	float:left;
	margin: 0px;
	padding: 2px 5px;
}
.datatable .customfields li{
	padding: 3px 0px;
	margin: auto 0px;
}

</style>
<center>


<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th width="20"><!-- input type="checkbox" class="monitis_checkall" / --></th>
		<th style="text-align:left;padding-left:20px;"><a href="javascript:void(0)" onclick="submit();">Product Name</a></th>
		<th style="text-align:left;padding-left:20px;">Monitor type</th>

	</tr>
<?

if( $products && count($products) > 0 ) {	
	$totalresults = $products[0]['total'];
	
	for($i=0; $i<count($products); $i++) {
		$productId = $products[$i]['id'];
		$groupId = $products[$i]['gid'];
?>
	<tr>
		<td><!-- input type="checkbox" class="monitis_checkall" value="<?=$productId?>" name="productId[]" / --></td>
		<td><?php echo $products[$i]['name']?></td>
		<td class="customfields" valign="center" align="left">
			<form method="post" action="">
			<table>
			<tr>
		<? 	$allTypes = explode(",", MONITIS_MONITOR_TYPES);
			if( !$products[$i]['monitorType'] ) { ?>
				<td><ul>
		<?
				for($a=0; $a<count($allTypes); $a++){
					echo '<li><input type="checkbox" value="'.$allTypes[$a].'" name="monitor_type[]" checked /> <span>'.strtoupper($allTypes[$a]).' monitor</span></li>';
				}		
		?>		
				</ul>
				</td>
				<td class="actions">
					<input type="hidden" name="action" value="setMonitorType" />
					<input type="submit" value="Activate" class="btn-success"  />
				</td>
				<td>&nbsp;</td>
		<?} else {?>
				<td>
				<ul>
		<?
				$customfields = $products[$i]['customfields'];
				$website = $monType = null;
				$website_id = $monType_id = 0;
				
				for($j=0; $j<count($customfields); $j++){
					if( $customfields[$j]['fieldname'] == MONITIS_FIELD_WEBSITE ) {
						$website = $customfields[$j];
						$website_id = $customfields[$j]['id'];
					}
					if( $customfields[$j]['fieldname'] == MONITIS_FIELD_MONITOR ) {
						$monType = $customfields[$j];
						$monType_id = $customfields[$j]['id'];
					}
				}
				$types = explode(",", $monType['fieldoptions']);

				for($a=0; $a<count($allTypes); $a++){
					$checked = (in_array($allTypes[$a], $types)) ? 'checked' : '';
					echo '<li><input type="checkbox" value="'.$allTypes[$a].'" name="monitor_type[]" '.$checked.' /> <span>'.strtoupper($allTypes[$a]).' monitor</span></li>';
				}
		?>
				</ul>
				</td>
				<td class="actions">
				<? if(  $products[$i]['isWhmcsItem']) {?>
			
					<input type="hidden" name="action" value="update" />
					<input type="submit" value="Deactivate" onclick="this.form.action.value='deactivate'" class="btn-danger"  />
					<input type="submit" value="Update" onclick="this.form.action.value='update'" />
				<? } else { ?>
					<input type="hidden" name="action" value="activate" />
					<input type="submit" value="Activate" onclick="this.form.action.value='activate'" class="btn-success"  />
					
				<?}?>
				<input type="hidden" name="website_id" value="<?=$website_id?>" />
				<input type="hidden" name="monType_id" value="<?=$monType_id?>" />
				<!-- input type="submit" value="Delete" onclick="this.form.action.value='delete'" / -->
				</td>
				<td>&nbsp;</td>
		<?	}?>
			</tr></table>
			<input type="hidden" name="productId" value="<?=$productId?>" />
			</form>
		</td>
</tr>
<?	}
}?>
	</table>
	<input type="hidden" name="saveProductConfig" value="1" />

</center>