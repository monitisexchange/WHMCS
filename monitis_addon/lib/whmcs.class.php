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
	
	public function __construct ( $client_id=1 ) {
		$this->client_id = MONITIS_CLIENT_ID;
	}
	
	public function clientInfo() {
		//$sql = 'SELECT * FROM mod_monitis_client WHERE client_id=' . $this->client_id;
		$sql = 'SELECT * FROM mod_monitis_client';
		$val = $this->query( $sql );
		if($val)	return $val[0];
		else return null;
	}
	
	public function serverInfo( $server_id ) {
		$sql = 'SELECT id, name, ipaddress, hostname FROM tblservers WHERE id='.$server_id;
		return $this->query( $sql );
	}
	
	public function extMonitorsByServerId( $server_id ) {
		$sql = 'SELECT * FROM mod_monitis_ext_monitors WHERE server_id='.$server_id;
		return $this->query( $sql );
	}
	
	public function all_servers( $opts ){
/*
		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, name, ipaddress, hostname
			FROM tblservers
			LEFT JOIN mod_monitis_ext_monitors ON (tblservers.id = mod_monitis_ext_monitors.server_id AND mod_monitis_ext_monitors.client_id = '.$this->client_id.')
			ORDER BY '.$opts['sort'].' '.$opts['sortorder'].' LIMIT '.$opts['start'].','.$opts['limit'];
*/
		$sql = 'SELECT SQL_CALC_FOUND_ROWS id, name, ipaddress, hostname
			FROM tblservers
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
				WHERE tblservers.id='.$server_id;
		return $this->query( $sql );
	}

	public function intAssosMonitors( $server_id ) {
		$sql = 'SELECT * FROM mod_monitis_int_monitors WHERE server_id='.$server_id;
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
	
	public function servers_ext( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey, name, hostname 
		FROM mod_monitis_ext_monitors
		LEFT JOIN tblservers ON ( tblservers.id = mod_monitis_ext_monitors.server_id )
		WHERE server_id in ('.$serverIds.') AND available='.$available;
		return $this->query( $sql );
	}
	public function servers_int( $serverIds, $available=1) {
		$sql = 'SELECT server_id, monitor_id, monitor_type, publickey, name, hostname 
		FROM mod_monitis_int_monitors
		LEFT JOIN tblservers ON ( tblservers.id = mod_monitis_int_monitors.server_id )
		WHERE server_id in ('.$serverIds.') AND available='.$available;
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
	public function removeClientMonitorsByServersId($server_id) {
		$sql = 'DELETE FROM mod_monitis_product_monitor WHERE server_id='.$server_id;
		$this->query_del( $sql );
	}

	// remove ext and int monitors from whmcs db by server_id
	public function removeMonitorsByServersId($server_id) {
		$this->removeIntMonitorsByServersId($server_id);
		$this->removeExtMonitorsByServersId($server_id);
		$this->removeClientMonitorsByServersId($server_id);
	}
	
	// remove ext and int monitors from whmcs db by monitor_id
	public function removeExternalMonitorsById($monitor_id) {
		$sql = 'DELETE FROM mod_monitis_ext_monitors WHERE monitor_id='.$monitor_id;
		$this->query_del( $sql );
		$this->removeProductMonitorsById($monitor_id);
	}
	public function removeInternalMonitorsById($monitor_id) {
		$sql = 'DELETE FROM mod_monitis_int_monitors WHERE monitor_id='.$monitor_id;
		$this->query_del( $sql );
		$this->removeProductMonitorsById($monitor_id);
	}
	public function removeProductMonitorsById($monitor_id) {
		$sql = 'DELETE FROM mod_monitis_product_monitor WHERE monitor_id='.$monitor_id;
		return $this->query_del( $sql );
	}
	//////////////////////////////////////////////////////
	public function getAdmin(){

		$sql = 'SELECT tbladmins.id as adminid, username, email, tbladmins.roleid as roleid
		FROM tbladmins 
		LEFT JOIN tbladminroles ON ( tbladminroles.name = "Full Administrator" AND tbladminroles.id = tbladmins.roleid )';
		//return $this->query( $sql );
		$vals = $this->query( $sql );
		if( $vals ) return $vals[0];
		else return null;
	}
	// tbladdonmodules
	public function getAdminName( $addonmame, $fieldname){
		$sql = 'SELECT * FROM tbladdonmodules WHERE module="'.$addonmame.'" AND setting="'.$fieldname.'"';
		$vals = $this->query( $sql );
		if( $vals ) return $vals[0];
		else return null;
	}

      public function adminList(){  
            $roleIds= $this->adminRoleIds();             
            $sql = 'SELECT username, id, email FROM tbladmins WHERE roleid in ('.$roleIds.')';

	    $result=$this->query($sql); 
           if($result){                    
			return $result;
           }

          else return null;
        }
  
       

   
        public function adminRoleIds(){
           $sql = 'SELECT * FROM tbladdonmodules';
	   $resultSet = $this->query( $sql );
           $idSet='';
            foreach($resultSet as $set){
                foreach($set as $t){
                    $idSet=$t['access'];
                }
            }
            return $idSet;                          
     
        }
        
        
    public function adminList_test($admin_id){  
           // $roleIds= $this->adminRoleIds();           
            $sql = 'SELECT * FROM tbladmins WHERE id='.$admin_id;
            $result=$this->query($sql);             
           if($result){                    
	    return json_encode($result);

        } 
     
          else return null;
   
        }
	
}




?>