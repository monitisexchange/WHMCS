<?php
class monitisWhmcsServer {

	// for monitors.php
    static function serverInfo($server_id) {
		return monitisSqlHelper::objQuery('SELECT id, name, ipaddress, hostname FROM tblservers WHERE disabled=0 AND id=' . $server_id);
    }
	
    static function extMonitorsByServerId($server_id) {
		// mod_monitis_ext_monitors
		return monitisSqlHelper::query('SELECT * FROM '.MONITIS_EXTERNAL_TABLE.' WHERE server_id='.$server_id);
    }

	static function intMonitorsByServerId($server_id) {
		// mod_monitis_int_monitors
		return monitisSqlHelper::query('SELECT * FROM '.MONITIS_INTERNAL_TABLE.' WHERE server_id='.$server_id);
	}
	
	//////////////////////////// server tab
	static function serverByIds($serverIds) {
		return monitisSqlHelper::query('SELECT id, name, ipaddress, hostname FROM tblservers WHERE disabled=0 AND id in ('.$serverIds.')');
	}

	static function extMonitorsByServerIds($serverIds) {
        return monitisSqlHelper::query('SELECT * FROM mod_monitis_ext_monitors WHERE server_id in ('.$serverIds.')');
    }

	static function intMonitorsByServerIds($serverIds) {
		return monitisSqlHelper::query('SELECT * FROM mod_monitis_int_monitors WHERE server_id in ('.$serverIds.')');
	}

	static function allServers($opts) {
		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, name, ipaddress, hostname
			FROM tblservers WHERE disabled=0 
			ORDER BY '.$opts['sort'].' '.$opts['sortorder'].' LIMIT '.$opts['start'].','.$opts['limit'];
		return monitisSqlHelper::pageQuery($sql);
	}

    static function intMonitorsByType($agentId, $monitorType) {
        $sql = 'SELECT * FROM mod_monitis_int_monitors WHERE agent_id='.$agentId.' AND monitor_type="'.$monitorType.'"';
        return monitisSqlHelper::query($sql);
    }

	static function ext_monitors() {
		$sql = 'SELECT id, name, ipaddress, hostname, monitor_id, monitor_type
				FROM mod_monitis_ext_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_ext_monitors.server_id
				WHERE tblservers.disabled=0
				';
		//	WHERE client_id='.$this->client_id;
		return monitisSqlHelper::query($sql);
	}

	static function int_monitors() {
		$sql = 'SELECT id, name, ipaddress, hostname, monitor_id, monitor_type
				FROM mod_monitis_int_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_int_monitors.server_id 
				WHERE tblservers.disabled=0
				';
		//WHERE client_id='.$this->client_id;
		return monitisSqlHelper::query($sql);
	}

	static function extServerMonitors($server_id) {
		$sql = 'SELECT id, name, ipaddress, hostname, mod_monitis_ext_monitors.*
				FROM mod_monitis_ext_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_ext_monitors.server_id  
				WHERE tblservers.disabled=0 AND tblservers.id=' . $server_id;
		return monitisSqlHelper::query($sql);
	}

	static function unlinkExternalMonitorById($monitor_id) {
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_ext_monitors WHERE monitor_id=' . $monitor_id);
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_product_monitor WHERE monitor_id=' . $monitor_id);
	}

	static function unlinkMonitorsByServersId($server_id) {
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_int_monitors WHERE server_id=' . $server_id);
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_ext_monitors  WHERE server_id=' . $server_id);
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_product_monitor WHERE server_id=' . $server_id);
	}

	static function unlinkProductMonitorById($monitor_id) {
		$sql = 'DELETE FROM mod_monitis_product_monitor WHERE monitor_id=' . $monitor_id;
		return $this->query_del($sql);
	}
}

?>