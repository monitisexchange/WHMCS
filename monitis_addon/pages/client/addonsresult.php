<?php
$addonId = monitisGetInt('addonid');

$result = MonitisHookClass::createAddonsMonitorById($addonId);
$addonName = '';
if($result && count($result)>0)
	$addonName = $result[0]['name'];
?>
<style>
.datatable .msg{
	font-weight:bold;
	color:#000000;
}
</style>

<div class="dialogTitle"><?php if($addonName!='') echo "Addon: <b>".$addonName."</b>"; ?></div>
<div style="text-align: right;" class="monitis_link_result">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabclient&sub=addons">&#8592; Back to addons list</a>
</div>
<br />
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
    <thead>
	<tr>
		<th>Order ID</th>
		<th>Order #</th>
		<th>Service</th>
		<th>Client</th>
		<th>Monitor Type</th>
		<th>Domain</th>
		<th>Dedicated IP</th>
		<th>Status</th>
	</tr>
    </thead>
	
<?php
if( $result && count($result) > 0) {

	$addonUrl = MonitisHelper::adminAddonUrl();
	for($i=0; $i<count($result); $i++) {
	
		$msg = '';
		$color = '#000000';
		$item = $result[$i];
		$product = $item['data'][0]['product'];
		$response = $item['data'][0]['response'];
		
		$orderUrl = $item["order_url"];
		$serviceUrl = $item["service_url"];
		$isError = false;
		if($response["status"] == 'ok') {
			$color = '#468847';
		} elseif($response["status"] == 'error') {
			$color = '#cc0000';
			$isError = true;
		} else $color = '#C09853';
?>
    <tbody>
	<tr>
		<td>
		<?php if(!empty($product["orderid"])) {?>
			<a href="<?php echo $orderUrl?>" target="_blank"><?php echo $product["orderid"]?></a>
		<?php } else { ?>
			<span style="color:#888888"><?php echo $product["serviceorderid"]?></span>
		 <?php } ?>
		</td>
		<td><?php echo $product["ordernum"]?></td>
		<td><a href="<?php echo $serviceUrl?>" target="_blank"><?php echo$product["serviceid"]?>/<?php echo $product["addonserviceid"]?></a></td>
		<td><?php echo $product["username"]?></td>
		<td><?php echo $product["monitor_type"]?></td>
		<td><?php echo $product["domain"]?></td>
		<td><?php echo $product["dedicatedip"]?></td>
		<td class="msg" style="color:<?php echo $color?>">
		<?php if($isError) {?>
			<a href="<?php echo $addonUrl?>&monitis_page=tabreport" style="color:<?php echo $color?>" target="_blank"><?php echo $response['msg']?></a>
		<?php } else {?>
			<?php echo $response['msg']?>
		<?php } ?>
		</td>
	</tr>
<?php
	}
}else{ ?>
    <tr>
        <td colspan="8">No active addons available</td>
    </tr>
<?php } ?>
    </tbody>
</table>
