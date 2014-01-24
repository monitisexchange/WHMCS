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
					//$optionid, $configid, $serviceid
					$resp = MonitisHookClass::applyCreateConfigOptionMonitor($_POST['option_id'], $_POST['productid'], $_POST['serviceid']);
				}
				//$response = MonitisSeviceHelper::createMonitor( $product );
			break;
		}
	} elseif($action == 'clean') {
		monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_HOOK_REPORT_TABLE);
	}
}


$list = monitisSqlHelper::query('SELECT * FROM '.MONITIS_HOOK_REPORT_TABLE.' ORDER BY `date` DESC');
//_dump($list);
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
		<!-- th>temp</th -->
		<th>Response</th>
		<th>&nbsp;</th>
	</tr>
<?
$producttype = array(
	'addon'=>'Addon',
	'option'=>'Configure options',
	'product'=>'Custom fields'
);
if( $list && count($list) > 0) {

	for($k=0; $k<count($list); $k++) {
		$row = $list[$k];
		// $vars = $row['vars'];
		
		$resp = json_decode($row['json'], true);
		
		$data = $resp['data'];
		for($i=0; $i<count($data); $i++){
			
			$product = $data[$i]['product'];

			/*
			$is_linked = 'no';
			$is_existed = 'no';
			if($product['monitor'] && count($product['monitor']) > 0) {
				$is_linked = 'yes';
				if($product['monitor']['api'] && count($product['monitor']['api']) > 0)
					$is_existed = 'yes';
			}			
			*/
			
			$pType = $producttype[$product["producttype"]];
			$status = $data[$i]['response']['status'];
			$response = $data[$i]['response']['msg'];
			$stl = '';
			if($status == 'error') {
				//$stl = 'style="color:red"';
				$stl = 'class="textred"';
?>
	<tr>
		<td><?=$row["date"]?></td>
		<td><a href="<?=$resp['serviceurl']?>" target="_blank"><?=$resp["title"]?></a></td>
		<td><?=$resp["username"]?></td>
		<td><?=$pType?></td>
		<td><?=$product['web_site']?></td>
		<td><?=$product['monitor_type']?></td>
<?	/*	<td>
			<div>hook type: <?=$resp["hook_type"]?> / hook: <?=$resp["hook"]?></div>
			<div>existed: <?=$is_existed?> / linked: <?=$is_linked?></div>
		</td>
	*/
?>
		<td><span <?=$stl?>><?=$response?></span></td>
		<td>
			<form method="post" action="">
			<input type="submit" value="Apply" onclick="this.form.act.value='apply'" class="btn" />
			<input type="submit" value="Delete" onclick="this.form.act.value='delete'" class="btn" />
			<input type="hidden" name="act" value="" />
			<input type="hidden" name="hook" value="<?=$resp["hook"]?>" />
			<input type="hidden" name="hook_type" value="<?=$resp["hook_type"]?>" />
			<input type="hidden" name="id" value="<?=$row["id"]?>" />
			<?if($resp["hook_type"] == 'order') {?>
				<input type="hidden" name="orderid" value="<?=$product["orderid"]?>" />
			<?} elseif($resp["hook_type"] == 'edit' || $resp["hook_type"] == 'module') {?>
				<input type="hidden" name="serviceid" value="<?=$product["serviceid"]?>" />
				<input type="hidden" name="userid" value="<?=$product["userid"]?>" />
			<?} elseif($resp["hook_type"] == 'multiple') { ?>
				<input type="hidden" name="multi_type" value="<?=$row["multi_type"]?>" />
				<input type="hidden" name="producttype" value="<?=$product["producttype"]?>" />
				<input type="hidden" name="option_id" value="<?=$product["option_id"]?>" />
				<input type="hidden" name="productid" value="<?=$product["productid"]?>" />
				<input type="hidden" name="serviceid" value="<?=$product["serviceid"]?>" />
				<input type="hidden" name="userid" value="<?=$product["userid"]?>" />
				<input type="hidden" name="addonserviceid" value="<?=@$product["addonserviceid"]?>" />
			<?} else {?>
				<input type="hidden" name="addonserviceid" value="<?=$product["addonserviceid"]?>" />
				<input type="hidden" name="serviceid" value="<?=$product["serviceid"]?>" />
				<input type="hidden" name="addonid" value="<?=$product["addonid"]?>" />
				<input type="hidden" name="userid" value="<?=$product["userid"]?>" />
			<?}?>
			</form>
		</td>
	</tr>
<?		
			}
		}
	}
}
?>
</table>

<?
// &monitis_page=tabreport&sub=hook


?>
