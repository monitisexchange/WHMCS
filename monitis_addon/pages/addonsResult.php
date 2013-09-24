<?php
//require_once ('../modules/addons/monitis_addon/lib/product.class.php');
//require_once ('../modules/addons/monitis_addon/lib/services.class.php');

//require_once ('../modules/addons/monitis_addon/lib/clientservices.class.php');

$addonId = monitisGetInt('addonid');

/*
$adminuser = MonitisConf::getAdminName();
$oSrv = new servicesClass();
$products = $oSrv->addonOrdersList( $addonId, $adminuser );
*/
$oSrv = new clientServicesClass();
$products = $oSrv->addonProductsList($addonId);
//_dump($products);
?>
<style>
.datatable td {
	padding-left: 20px;
}
.datatable .msg{
	font-weight:bold;
	color:#000000;
}
</style>

<div class="dialogTitle"><?php if($oSrv->productname!='') echo "Addon name: <b>".$oSrv->productname."</b>"; ?></div>
<div align="left">
	<hr/>
</div>
<div style="text-align: right;">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=addons">&#8592; Back to addons list</a>
</div>
<br />
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th>Order ID</th>
		<th>Order #</th>
		<th>Client</th>
		<th>Monitor type</th>
		<th>Domain</th>
		<th>Dedicated IP</th>
		<th>Status</th>
	</tr>
	
<?
if( $products && count($products) > 0) {

	for($i=0; $i<count($products); $i++) {
		$resp = $oSrv->createMonitor( $products[$i] );
	
		$color = '#000000';
		if( $resp["status"] == 'ok' )
			$color = '#468847';
		elseif( $resp["status"] == 'error' )
			$color = '#cc0000';
		else
			$color = '#C09853';
?>
	<tr>
		<td style="text-align: center;padding-left:0px;"><?=$resp["product"]["orderid"]?></td>
		<td><?=$resp["product"]["ordernum"]?></td>
		<td><?=$resp["product"]["client"]?></td>
		<td><?=$resp["product"]["monitor_type"]?></td>
		<td><?=$resp["product"]["domain"]?></td>
		<td><?=$resp["product"]["dedicatedip"]?></td>
		<td class="msg" style="color:<?=$color?>"><?=$resp["msg"]?></td>
	</tr>
<?
	}
}
?>
</table>

