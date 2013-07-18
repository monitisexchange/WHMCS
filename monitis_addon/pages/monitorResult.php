<div style="text-align: right;">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=monitorList">&#8592; Back to monitor list</a>
</div>
<?php
$monitorID = monitisGetInt('id');
$monitor_type = monitisGetInt('type');

if( $monitor_type == 'ping') {
	$extMonitors = MonitisApi::getExternalMonitors();
	$monitor = NULL;
	foreach ($extMonitors['testList'] as $m) {
		if ($m['id'] == $monitorID)
			$monitor = $m;
	}

	if (!is_null($monitor)) {
		$chart = new MonitisChartExternal($monitorID);
		$chart->setHeight(400);
		
		if (!$monitor['isSuspended']) {
			$chart->renderJS();
			$chart->renderHtml();
		} else {
			echo '<div style="height: 110px; padding-top: 90px;"><span class="label" style="font-size: 14px;">MONITOR IS SUSPENDED</span></div>';
		}
	}
} else {

}


?>