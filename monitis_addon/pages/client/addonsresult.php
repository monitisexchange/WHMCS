<?php
$addonId = monitisGetInt('addonid');

//$oSrv = new clientServicesClass();
//$products = $oSrv->addonProductsList($addonId);

$result = MonitisHookClass::createAddonsMonitorById($addonId);
$addonName = '';
if($result && count($result)>0)
	$addonName = $result[0]['name'];

//_dump($products);
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
	
<?
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
		
//_dump($product);
		
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
		<?if(!empty($product["orderid"])) {?>
			<a href="<?=$orderUrl?>" target="_blank"><?=$product["orderid"]?></a>
		<?} else { ?>
			<span style="color:#888888"><?=$product["serviceorderid"]?></span>
		 <? } ?>
		</td>
		<td><?=$product["ordernum"]?></td>
		<td><a href="<?=$serviceUrl?>" target="_blank"><?=$product["serviceid"]?>/<?=$product["addonserviceid"]?></a></td>
		<td><?=$product["username"]?></td>
		<td><?=$product["monitor_type"]?></td>
		<td><?=$product["domain"]?></td>
		<td><?=$product["dedicatedip"]?></td>
		<td class="msg" style="color:<?=$color?>">
		<? if($isError) {?>
			<a href="<?=$addonUrl?>&monitis_page=tabreport" style="color:<?=$color?>" target="_blank"><?=$response['msg']?></a>
		<?} else {?>
			<?=$response['msg']?>
		<?}?>
		</td>
	</tr>
<?
	}
}else{ ?>
    <tr>
        <td colspan="8">No active addons available</td>
    </tr>
<?}
?>
    </tbody>
</table>
