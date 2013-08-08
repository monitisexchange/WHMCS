<?php
require_once ('../modules/addons/monitis_addon/lib/product.class.php');
require_once ('../modules/addons/monitis_addon/lib/services.class.php');

$addonId = monitisGetInt('addonid');

$adminuser = MonitisConf::getAdminName();
$oSrv = new servicesClass();
$products = $oSrv->addonOrdersList( $addonId, $adminuser );

/*
if( $products && count($products) > 0) {

	for($i=0; $i<count($products); $i++) {
		$resp = $oSrv->createMonitor( $products[$i] );
		$prdct = $resp["product"]["fullInfo"];
		
		
		$str = '<table class="automate" border=0>
		<tr><th>Order:</th><td>'. $resp["product"]["orderid"].' - '.$resp["product"]["ordernum"].'</td></tr>
		<tr><th>Client:</th><td>'.$prdct["client"].'</td></tr>
		<tr><th>Monitor type:</th><td>'.$resp["monitor_type"].'</td></tr>
		<tr><th>Domain:</th><td>'. $prdct["domain"].'</td></tr>
		<tr><th>Dedicated ip:</th><td>'. $prdct["dedicatedip"].'</td></tr>
		<tr><th>Status:</th><td>'.$resp["msg"].'</td></tr>
		</table>';
		if( $resp["status"] == 'ok' )
			MonitisApp::addMessage( $str );
		elseif( $resp["status"] == 'error' )
			MonitisApp::addError( $str );
		else
			MonitisApp::addWarning( $str );

	}
} else {
	//MonitisApp::addWarning( 'You have not product with this addon for monitoring' );
}
//MonitisApp::printNotifications();
*/
?>
<style>
.datatable td {
	padding-left: 20px;
	/*margin-top:20px;
	text-align: center;*/
}
.datatable .msg{
	font-weight:bold;
	color:#000000;
	/*line-height:14px;
	padding: 3px 7px;
	font-size:11px;
	*/
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
		<th>Dedicated ip</th>
		<th>Status</th>
	</tr>
	
<?
if( $products && count($products) > 0) {

	for($i=0; $i<count($products); $i++) {
		$resp = $oSrv->createMonitor( $products[$i] );
		$prdct = $resp["product"]["fullInfo"];
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
		<td><?=$prdct["client"]?></td>
		<td><?=$resp["monitor_type"]?></td>
		<td><?=$prdct["domain"]?></td>
		<td><?=$prdct["dedicatedip"]?></td>
		<td class="msg" style="color:<?=$color?>"><?=$resp["msg"]?></td>
	</tr>
<?
	}
}
?>
</table>

