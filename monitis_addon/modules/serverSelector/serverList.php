<?php
$res = mysql_query('SELECT id, name, ipaddress, hostname, test_ids
						FROM tblservers
						LEFT JOIN mod_monitis_servers ON tblservers.id=mod_monitis_servers.server_id
						WHERE 1');

$servers = array();
while($server = mysql_fetch_assoc($res)) {
	$server['test_ids'] = empty($server['test_ids']) ?
		array() : explode(',', $server->test_ids);
	$servers[$server['id']] = $server;
}


echo "Module ---- serverSelector/serverList.php (change servers list)<br>";
_dump($servers);
?>
<monitis_data>
<table id="m_ServerSelector_Table_<?php echo $this->instanceID; ?>" class="monitisDatatable"
	width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th></th>
		<th>ID</th>
		<th>Name</th>
		<th>IP Address</th>
		<th>Hostname</th>
		</tr>
	<?php foreach ($servers as $server) : ?>
	<tr>
		<td><input type="checkbox" name="<?php echo $this->inputName; ?>" value="<?php echo $server['id']; ?>"/></td>
		<td><?php echo $server['id']; ?></td>
		<td><?php echo $server['name']; ?></td>
		<td><?php echo $server['ipaddress']; ?></td>
		<td><?php echo $server['hostname']; ?></td>
	</tr>
	<?php endforeach; ?>
</table>
</monitis_data>