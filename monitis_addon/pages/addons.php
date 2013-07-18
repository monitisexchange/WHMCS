<?php

require_once ('../modules/addons/monitis_addon/lib/product.class.php');


class addonClass extends WHMCS_product_db {
	public function __construct () {}
	
	private function isMonitorType( $addon_id, & $maddons ) {
		for( $i=0; $i<count($maddons); $i++) {
			if( $maddons[$i]['addon_id'] == $addon_id )
				return $maddons[$i];
		}
		return null;
	}
	/*public function activateAddon( $values ) {
		//$values = array('addon_id'=>$addonId);
		insert_query('mod_monitis_addon', $values);
	}*/
	
	public function addonsList() {
		$all = $this->allAddons();
		$maddons = $this->whmcsAddons();
		for( $i=0; $i<count($all); $i++) {
			$info = $this->isMonitorType( $all[$i]['id'], $maddons );
			$all[$i]['monitor'] = $info;
		}
		return $all;
	}
	public function deactivateAddon( $addon_id ) {
		$this->deleteAddon($addon_id);
	}
}


$oAddon = new addonClass();



//_dump( $result );


$action = monitisPost('action');
$addonId = monitisPostInt('addonId');

if ( $action && $addonId > 0 ) {

		$monitor_type = monitisPost('monitor_type');
		switch( $action ) {
			case 'activate':
			$values = array(
				'addon_id'=>$addonId,
				'type' => $monitor_type,
				'status' => 'active'
			);
			insert_query('mod_monitis_addon', $values);
			//$oMProduct->activateAddon( $values );
			break;
			case 'deactivate':
				//echo "**** action = $action ****** addonId=$addonId **** <br>";
				$oAddon->deactivateAddon( $addonId );
			break;

		}
}

$addons = $oAddon->addonsList();

//_dump( $addons );

?>
<?php MonitisApp::printNotifications(); 
// Automatically create following monitors when<br/>creating new servers on WHMCS
?>
<style>

.datatable .customfields .type {
	text-align:center;
	width:150px
}
</style>
<center>


<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th width="20"><!-- input type="checkbox" class="monitis_checkall" / --></th>
		<th><a href="javascript:void(0)" onclick="submit();">Addon Name</a></th>
		<th>Addon type</th>
	</tr>
<?

if( $addons && count($addons) > 0 ) {	
	//$totalresults = $products[0]['total'];
	
	for($i=0; $i<count($addons); $i++) {
		$addonId = $addons[$i]['id'];
		//$groupId = $products[$i]['gid'];
?>
	<tr>
		<td><!-- input type="checkbox" class="monitis_checkall" value="<?=$productId?>" name="productId[]" / --></td>
		<td><?php echo $addons[$i]['name']?></td>
		<td class="customfields" valign="center" align="left">
			<form method="post" action="">
			<table>
			<tr>
		<? 	$allTypes = explode(",", MONITIS_MONITOR_TYPES);
			if( !$addons[$i]['monitor'] ) { ?>
				<td class="type">
				<select name="monitor_type">
		<?
				for($a=0; $a<count($allTypes); $a++){
					//echo '<li><input type="checkbox" value="'.$allTypes[$a].'" name="monitor_type[]" checked /> <span>'.strtoupper($allTypes[$a]).' monitor</span></li>';
					echo '<option value="'.$allTypes[$a].'">'.strtoupper($allTypes[$a]).'</option>';
				}		
		?>		
				</select>
				</td>
				<td>
					<input type="hidden" name="action" value="activate" />
					<input type="submit" value="Activate" class="btn-success"  />
				</td>
		<?} else {?>
				<td class="type">
				<select name="monitor_type">
		<?
				$monitor = $addons[$i]['monitor'];
				for($a=0; $a<count($allTypes); $a++){
					$selected = ( $allTypes[$a]== $monitor['type']) ? 'selected' : '';
					echo '<option value="'.$allTypes[$a].'" '.$selected.'>'.strtoupper($allTypes[$a]).'</option>';
				}
		?>
				</select>
				</td>
				<td class="center">
					<input type="hidden" name="action" value="deactivate" />
					<input type="submit" value="Deactivate" onclick="this.form.action.value='deactivate'" class="btn-danger"  />
				</td>
		<?	}?>
			</tr></table>
			<input type="hidden" name="addonId" value="<?=$addonId?>" />
			</form>
		</td>
</tr>
<?	}
}?>
	</table>
	<input type="hidden" name="saveProductConfig" value="1" />

</center>