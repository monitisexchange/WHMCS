<?php

$action = isset($_POST['act']) ? strtolower($_POST['act']) : '';
if($action) {

	if(isset($_POST['id'])) {
		monitisSqlHelper::query('DELETE FROM '.MONITIS_HOOK_REPORT_TABLE.' WHERE id='.$_POST['id']);
	}
	
	if($action == 'apply') {
		$hook_type = $_POST['hook_type'];
		$hook = $_POST['hook'];
		switch($hook_type) {
			case 'order';
				$vars = array(
					'orderid' => $_POST['orderid']
				);
				monitisOrderHookHandler( $vars, $hook);
			break;
			case 'module';
				$vars = array(
					'serviceid' => $_POST['serviceid'],
					'userid' => $_POST['userid']
				);
				monitisModuleHookHandlerAlt( $vars, $hook);
			break;
			case 'edit';
				$vars = array(
					'serviceid' => $_POST['serviceid'],
					'userid' => $_POST['userid']
				);
				monitisEditHookHandler($vars, $hook);
			break;
			case 'addon';
				$vars = array(
					'id' => $_POST['addonserviceid'],
					'addonserviceid' => $_POST['addonserviceid'],
					'serviceid' => $_POST['serviceid'],
					'userid' => $_POST['userid'],
					'addonid' => $_POST['addonid']
				);
				monitisAddonHookHandler($vars, $hook);
			break;
			case 'multiple';
				$type = $_POST["producttype"];
				if($type == 'addon') {
					$resp = MonitisHookClass::applyCreateAddonMonitor ($_POST['addonserviceid'], $_POST['serviceid'], $_POST['userid']);
				} else {
					$resp = MonitisHookClass::applyCreateConfigOptionMonitor($_POST['option_id'], $_POST['productid'], $_POST['serviceid']);
				}
			break;
		}
	} elseif($action == 'clean') {
		monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_HOOK_REPORT_TABLE);
	}
}
$list = monitisSqlHelper::query('SELECT * FROM '.MONITIS_HOOK_REPORT_TABLE.' ORDER BY `date` DESC');
?>
<div style="text-align: left;padding: 0px 0px 5px;">
	<form method="post" action="">
		<input type="submit" value="Clean" name="act" class="btn" />
	</form>
</div>
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th>Date</th>
		<th>Title</th>
		<th>Client</th>
		<th>Product type</th>
		<th>URL / IP</th>
		<th>Monitor type</th>
		<th>Response</th>
		<th>&nbsp;</th>
	</tr>
<?php
$producttype = array(
	'addon'=>'Addon',
	'option'=>'Configure options',
	'product'=>'Custom fields'
);
if( $list && count($list) > 0) {

	for($k=0; $k<count($list); $k++) {
		$row = $list[$k];
	
		$resp = json_decode($row['json'], true);
		
		$data = $resp['data'];
		for($i=0; $i<count($data); $i++){
			
			$product = $data[$i]['product'];
			
			$pType = $producttype[$product["producttype"]];
			$status = $data[$i]['response']['status'];
			$response = $data[$i]['response']['msg'];
			$stl = '';
			if($status == 'error') {
				$stl = 'class="textred"';
?>
	<tr>
		<td><?php echo $row["date"]?></td>
		<td><a href="<?php echo $resp['serviceurl']?>" target="_blank"><?php echo $resp["title"]?></a></td>
		<td><?php echo $resp["username"]?></td>
		<td><?php echo $pType?></td>
		<td><?php echo $product['web_site']?></td>
		<td><?php echo $product['monitor_type']?></td>
		<td><span <?php echo $stl?>><?php echo $response?></span></td>
		<td>
			<form method="post" action="">
			<input type="submit" value="Apply" onclick="this.form.act.value='apply'" class="btn" />
			<input type="submit" value="Delete" onclick="this.form.act.value='delete'" class="btn" />
			<input type="hidden" name="act" value="" />
			<input type="hidden" name="hook" value="<?php echo $resp["hook"]?>" />
			<input type="hidden" name="hook_type" value="<?php echo $resp["hook_type"]?>" />
			<input type="hidden" name="id" value="<?php echo $row["id"]?>" />
			<?php if($resp["hook_type"] == 'order') {?>
				<input type="hidden" name="orderid" value="<?php echo $product["orderid"]?>" />
			<?php } elseif($resp["hook_type"] == 'edit' || $resp["hook_type"] == 'module') {?>
				<input type="hidden" name="serviceid" value="<?php echo $product["serviceid"]?>" />
				<input type="hidden" name="userid" value="<?php echo $product["userid"]?>" />
			<?php } elseif($resp["hook_type"] == 'multiple') { ?>
				<input type="hidden" name="multi_type" value="<?php echo $row["multi_type"]?>" />
				<input type="hidden" name="producttype" value="<?php echo $product["producttype"]?>" />
				<input type="hidden" name="option_id" value="<?php echo $product["option_id"]?>" />
				<input type="hidden" name="productid" value="<?php echo $product["productid"]?>" />
				<input type="hidden" name="serviceid" value="<?php echo $product["serviceid"]?>" />
				<input type="hidden" name="userid" value="<?php echo $product["userid"]?>" />
				<input type="hidden" name="addonserviceid" value="<?php echo @$product["addonserviceid"]?>" />
			<?php } else {?>
				<input type="hidden" name="addonserviceid" value="<?php echo $product["addonserviceid"]?>" />
				<input type="hidden" name="serviceid" value="<?php echo $product["serviceid"]?>" />
				<input type="hidden" name="addonid" value="<?php echo $product["addonid"]?>" />
				<input type="hidden" name="userid" value="<?php echo $product["userid"]?>" />
			<?php }?>
			</form>
		</td>
	</tr>
<?php
			}
		}
	}
}
?>
</table>

<?
// &monitis_page=tabreport&sub=hook


?>
