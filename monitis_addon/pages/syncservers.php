<?php
	
	if(!MonitisConf::$settings['ping']['autocreate'] && !MonitisConf::$settings['cpu']['autocreate'] && !MonitisConf::$settings['memory']['autocreate'])
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=tabadmin');

	$servers = array();
	$res = mysql_query('SELECT id, name, ipaddress, hostname FROM tblservers WHERE disabled=0');
	while($s = mysql_fetch_array($res)) {
		$servers[$s['id']] = $s;
	}
	
	if (count($servers) < 1) {
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=tabadmin');
		//MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=servers');
	}
	
	if (isset($_POST['sync'])) {
		if($_POST['sync']) {
			foreach ($servers as $server)
				MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $server);
		}
		//MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=servers');
		MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=tabadmin');
	}
	
	$sets = MonitisConf::$settings;
	$newCreateMonitorsText = array();
	if($sets['ping']['autocreate'] > 0)
		$newCreateMonitorsText[] = 'ping';
	if($sets['cpu']['autocreate'] > 0) 
		$newCreateMonitorsText[] = 'cpu';
	if($sets['memory']['autocreate'] > 0) 
		$newCreateMonitorsText[] = 'memory';
		
	$newCreateMonitorsText = array_map(function($v){ return ucfirst($v); }, $newCreateMonitorsText);
	$newCreateMonitorsText = implode(', ', $newCreateMonitorsText);
?>
<!--h3>You have <b><?php echo count($servers); ?></b> servers, do you want to create <b><?php echo $newCreateMonitorsText; ?></b> monitors for each server?</h3-->
<h3>Do you want to create monitors for existing servers?</h3>
<form method="post" action="">
	<input type="hidden" name="sync" value="0" />
	<input type="submit" onclick="javascript: $(this).parent('form').find('input[name=sync]').val(1);" value="Yes" class="monitis_link_button" />
	<input type="submit" onclick="javascript: $(this).parent('form').find('input[name=sync]').val(0);" value="No" class="monitis_link_button" />
</form>
<?php MonitisApp::printNotifications(); ?>