<?php
// whmcs.class.php

class whmcs_db {
	public function __construct () { }
	
	protected function query( $sql ){
		$result = mysql_query( $sql ) or die ("Error in query: " .  mysql_error() . "<br>" . $sql . "<br>");
		if( !is_resource( $result ) )
			return array();
		$num_rows = mysql_num_rows($result);
		$vObj = array();
		if($num_rows > 0) {
			$i = 0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$vObj[$i] = array();
				foreach ($row as $key => $value) { $vObj[$i][$key] = $value; }
				$i++;
			}
		} else 
			$vObj = null;
		return $vObj;
	}
	protected function query_del( $sql ){
		$result = mysql_query( $sql ) or die ("Error in DELETE query: " .  mysql_error() . "<br>" . $sql . "<br>");
	}
	protected function count(){
		$result = mysql_query( 'SELECT FOUND_ROWS() as __count' ) or die ("Error in DELETE query: " .  mysql_error() . "<br>" . $sql . "<br>");
		$count = 0;
		while($row = mysql_fetch_object($result)) {
			$count = $row->__count;
		}
		return $count;
	}
	protected function query_count( $sql ){
		 $vals = $this->query( $sql );
		 if( $vals) {
			$vals[0]['total'] = $this->count();
			return $vals;
		 } else return null;
	}
}

class WHMCS_class extends whmcs_db {
	private $client_id = null;
	
	public function __construct ( $client_id=0 ) {
		$this->client_id = $client_id;
	}
	
	public function clientInfo() {
		$sql = 'SELECT * FROM mod_monitis_client WHERE client_id=' . $this->client_id;
		$val = $this->query( $sql );
		if($val)	return $val[0];
		else return null;
	}
	
	public function serverInfo( $server_id ) {
		$sql = 'SELECT id, name, ipaddress, hostname FROM tblservers WHERE id='.$server_id;
		return $this->query( $sql );
	}
	
	public function all_servers( $opts ){
		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, name, ipaddress, hostname
			FROM tblservers
			LEFT JOIN mod_monitis_ext_monitors ON (tblservers.id = mod_monitis_ext_monitors.server_id AND mod_monitis_ext_monitors.client_id = '.$this->client_id.')
			ORDER BY '.$opts['sort'].' '.$opts['sortorder'].' LIMIT '.$opts['start'].','.$opts['limit'];
		 
		 $vals = $this->query( $sql );
		 if( $vals) {
			$vals[0]['total'] = $this->count();
			return $vals;
		 } else return null;
	}
	
	// 
	public function externalMonitors( $opts ) {
		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, name, ipaddress, hostname, monitor_id, monitor_type
				FROM mod_monitis_ext_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_ext_monitors.server_id 
				WHERE client_id='.$this->client_id.' ORDER BY '.$opts['sort'].' '.$opts['sortorder'].' LIMIT '.$opts['start'].','.$opts['limit'];
		 $vals = $this->query( $sql );
		 if( $vals) {
			$vals[0]['total'] = $this->count();
			return $vals;
		 } else return null;
	}
	
	public function ext_monitors() {
		$sql = 'SELECT id, name, ipaddress, hostname, monitor_id, monitor_type
				FROM mod_monitis_ext_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_ext_monitors.server_id 
				WHERE client_id='.$this->client_id;
		return $this->query( $sql );
	}
	
	public function int_monitors() {
		$sql = 'SELECT id, name, ipaddress, hostname, monitor_id, monitor_type
				FROM mod_monitis_int_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_int_monitors.server_id 
				WHERE client_id='.$this->client_id;
		return $this->query( $sql );
	}
	
	public function extServerMonitors( $server_id ) {
		$sql = 'SELECT id, name, ipaddress, hostname, mod_monitis_ext_monitors.*
				FROM mod_monitis_ext_monitors 
				LEFT JOIN tblservers ON tblservers.id=mod_monitis_ext_monitors.server_id  
				WHERE client_id='.$this->client_id.' AND tblservers.id='.$server_id;
		return $this->query( $sql );
	}

	/////////////
	private function _intServerMonitorsAll( & $vals ) {
		$intMons = array();
		for( $i=0; $i<count($vals); $i++ ) {
			if( $vals[$i]['monitor_type'] == 'cpu' ) {
				$intMons['cpu'] = $vals[$i];
			} elseif( $vals[$i]['monitor_type'] == 'memory' ) {
				$intMons['memory'] = $vals[$i];
			} elseif( $vals[$i]['monitor_type'] == 'drive' ) {
				if( !isset( $intMons['drive'] ) )
					$intMons['drive'] = array();
				$intMons['drive'][] = $vals[$i];
			}
		}
		return $intMons;
	}
	public function intServerMonitorsAll( $server_id ) {
		$sql = 'SELECT id, name, ipaddress, hostname, mod_monitis_int_monitors.*
				FROM mod_monitis_int_monitors 
				LEFT JOIN tblservers ON ( tblservers.id=mod_monitis_int_monitors.server_id  )
				WHERE client_id='.$this->client_id.' AND server_id='.$server_id;
//echo "$sql<br>";
		$vals = $this->query( $sql );
		
		if( $vals && count($vals) > 0) {
			return $this->_intServerMonitorsAll( $vals );
		} else
			return null;
	}
	
	public function intMonitorsByAgentId( $agentId ) {
		$sql = 'SELECT * FROM mod_monitis_int_monitors WHERE agent_id='.$agentId;
		return $this->query( $sql );
	}
	
	public function intMonitorsByType( $agentId, $monitorType ) {
		$sql = 'SELECT * FROM mod_monitis_int_monitors WHERE agent_id='.$agentId.' AND monitor_type="'.$monitorType.'"';
		return $this->query( $sql );
	}

	public function intServerMonitors( $server_id, $type ) {

		$sql = 'SELECT id, name, ipaddress, hostname, mod_monitis_int_monitors.*
				FROM mod_monitis_int_monitors 
				LEFT JOIN tblservers ON ( tblservers.id='.$server_id.' AND tblservers.id = mod_monitis_int_monitors.server_id ) 
				WHERE client_id='.$this->client_id.' AND monitor_type="'.$type.'"';
		return $this->query( $sql );
	}
	
	public function allServers(){
		$sql = 'SELECT id, name, ipaddress, hostname 
			FROM tblservers
			LEFT JOIN mod_monitis_ext_monitors ON (tblservers.id = mod_monitis_ext_monitors.server_id AND mod_monitis_ext_monitors.client_id = '.$this->client_id.')';
		return $this->query( $sql );
	}
	
	public function servers_list( $serverIds) {
		$sql = 'SELECT id, name, ipaddress, hostname FROM tblservers WHERE id in ('.$serverIds.')';
		return $this->query( $sql );
	}
	public function servers_list_ext( $serverIds) {
		$sql = 'SELECT * FROM mod_monitis_ext_monitors WHERE server_id in ('.$serverIds.')';
		return $this->query( $sql );
	}
	public function servers_list_int( $serverIds) {
		$sql = 'SELECT * FROM mod_monitis_int_monitors WHERE server_id in ('.$serverIds.')';
		return $this->query( $sql );
	}
	
	// remove ext and int monitors from whmcs db
	public function removeExtMonitorsByServersId($server_id) {
		$sql = 'DELETE FROM mod_monitis_ext_monitors  WHERE server_id='.$server_id;
		$this->query_del( $sql );
	}
	public function removeIntMonitorsByServersId($server_id) {
		$sql = 'DELETE FROM mod_monitis_int_monitors WHERE server_id='.$server_id;
		$this->query_del( $sql );
	}
	// remove ext and int monitors from whmcs db
	public function removeMonitorsByServersId($server_id) {
		$this->removeIntMonitorsByServersId($server_id);
		$this->removeExtMonitorsByServersId($server_id);
	}

      ///for notification test///

      public function adminList(){  
            $roleIds= $this->adminRoleIds();             
            $sql = 'SELECT username, id, email FROM tbladmins WHERE roleid in ('.$roleIds.')';
	    return $this->query( $sql );
        }
  
       

     public function adminRoleIds(){
           $sql = 'SELECT * FROM tbladdonmodules';
	   $resultSet = $this->query( $sql );    
	   $idSet=''; $arr=$resultSet[1];

	    foreach ($arr as $key => $val) {
                if($key =='value'){
                 $idSet=$arr[$key];
                }
               }                                 
        
             return  $idSet;    
        }
        
    public function adminList_test($admin_id){  
           // $roleIds= $this->adminRoleIds();           
            $sql = 'SELECT * FROM tbladmins WHERE id='.$admin_id;
            $result=$this->query($sql);             
	    return json_encode($result);

        } 
     
   
	
}

////////////////////////////////


?>