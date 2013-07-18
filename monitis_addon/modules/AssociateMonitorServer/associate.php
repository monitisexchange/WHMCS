<?php
$serverID = monitisGet('server_id');
$toAssociateIDs = monitisPost('m_AssociateMonitorServer_Selecteds', array());


$res = mysql_query(sprintf('SELECT id, name, ipaddress, hostname, test_ids
						FROM tblservers
						LEFT JOIN mod_monitis_servers ON tblservers.id=mod_monitis_servers.server_id
						WHERE id=%d', $serverID));
$server = mysql_fetch_object($res);
$testIDs = is_null($server->test_ids) ? array() : explode(',', $server->test_ids);

$testIDs = array_merge($testIDs, $toAssociateIDs);

if (is_null($server->test_ids)) {
	$values = array("server_id" => $server->id, "test_ids" => implode(',', $testIDs));
	insert_query('mod_monitis_servers',$values);
} else {
	$update = array("test_ids" => implode(',', $testIDs));
	$where = array("server_id" => $server->id);
	update_query('mod_monitis_servers', $update, $where);
}

MonitisApp::addMessage('Monitors ( id: ' . implode(', ', $toAssociateIDs) . ' ) successfully associated with this server.');

$this->render('default');