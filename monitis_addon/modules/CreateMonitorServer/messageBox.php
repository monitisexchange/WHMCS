<monitis_data>
<?php

$serverID = monitisGet('server_id');


$monitorID = intval(monitisPostInt('module_CreateMonitorServer_monitorID'));
$monitorType = monitisPost('module_CreateMonitorServer_monitorType');
//echo "*******************************************$monitorID $monitorType <br>";

$action_type = 'delete';
$monitorName = '';
switch($monitorType) {
	case 'ping':
		$monitor = MonitisApi::getExternalMonitorInfo($monitorID);
		$monitorName = $monitor['name'];
//_dump($monitor);
	break;
	case 'cpu':
		$monitor = MonitisApi::getCPUMonitor($monitorID);
		$monitorName = $monitor['name'];
//_dump($monitor);
	break;
	case 'memory':
		$monitor = MonitisApi::getMemoryInfo($monitorID);
		$monitorName = $monitor['name'];
//_dump($monitor);
	break;
	case 'drive':
		$monitor = MonitisApi::getDriveInfo($monitorID);
		$monitorName = $monitor['name'];
//_dump($monitor);
	break;
}


//echo "serverID = $serverID; monitorID = $monitorID; monitorType = $monitorType<br>";
//echo "Module ---- modules/CreateMonitorServer/createFormMemory.php <br>";
?>

<div class="dialogTitle"><?php if($monitorID > 0) echo "<b>Monitor:</b> $monitorName"; ?></div>

<form action="" method="post" id="editMonitorForm">
	<table class="form" width="100%" border="0" >
		<tr>
			<th class="fieldlabel" align="center" style="padding:30px">Are you sure you want to remove the monitor?</th>
		</tr>
		<tr>
			<td class="fieldlabel" align="center" style="padding:10px">
				<input type="button" value="Delete" onclick="javascript: m_CreateMonitorServer.submitForm('editMonitorForm');">
				<input type="button" value="Close" onclick="javascript: $('#m_CreateMonitorServer_Content').dialog('close');">
			</td>
		</tr>
	</table>

	<input type="hidden" name="action_type" value="<?=$action_type?>" />
	<input type="hidden" name="monitor_id" value="<?=$monitorID?>" />
	<input type="hidden" name="monitorType" value="<?=$monitorType?>" />
	<input type="hidden" name="type" value="<?=$action_type?>" />
	<input type="hidden" name="module_CreateMonitorServer_monitorID" value="<?=$monitorID?>" />
	<input type="hidden" name="module_CreateMonitorServer_action" value="createSubmited" />
</form>
</monitis_data>