<?

class notification_db extends whmcs_db {

	protected function allExternalMonitors( $monitor_type, $opts=null ) {
		$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM mod_monitis_ext_monitors WHERE monitor_type="'.$monitor_type.'"';
		if( $opts ) {
			$sql .= ' ORDER BY '.$opts['sort'].' '.$opts['sortorder'].' LIMIT '.$opts['start'].','.$opts['limit'];
		}
		 $vals = $this->query( $sql );
		 if( $vals) {
			$vals[0]['total'] = $this->count();
			return $vals;
		 } else return null;
	}
	
	protected function adminList(){
		$sql = 'SELECT * FROM tbladmins'; 
		return $this->query( $sql );
	}
	
	protected function updateAlertGroupIds( $ids, $alertgroup_id, $table) {
		$sql = 'UPDATE '.$table.' SET alertgroup_id='.$alertgroup_id.' WHERE monitor_id in ('.$ids.')';
		return mysql_query( $sql ) or die ("Error in query UPDATE : " .  mysql_error() . "<br>" . $sql . "<br>");
	}
}

class notificationsClass extends notification_db {

	private $mgroups = null;

	public function setupContactGroups() {
		
		$mgroups =  MonitisApi::getContactGroupList();
		if( !$mgroups) {
			$resp = $this->createDefaultGroup();
			if( $resp['status'] == 'ok' ) {
				$mgroups =  MonitisApi::getContactGroupList();
			} else return $resp;
		}
		
//_logActivity("setupContactGroups  **** <b>********************************</b><p>".json_encode($mgroups[$i])."</p>");

		$this->mgroups = $mgroups;
		// ===========================================================
		return array('status'=>'ok', 'data'=>$mgroups );
	}
	
	public function createDefaultGroup(){ 
		$defaultgroup=MonitisConf::$defaultgroup;
		$resp=MonitisApi::addContactGroup(1, $defaultgroup);
		if(!$resp['error']){
			$adminInfo=$this->adminList();	//$this->whmcsAdminList();
			if($adminInfo){
				$list = '';
				for($i=0; $i<count($adminInfo); $i++){ 
					$adminObj[$i]=array(
						'firstname'=>$adminInfo[$i]['firstName'],
						'lastname'=>$adminInfo[$i]['lastName'],
						'account'=>$adminInfo[$i]['email'],
						'contactType'=>1, 
						'timezone'=> MonitisConf::$settings["timezone"],
						'group'=>$defaultgroup
					);
					MonitisApi::addContactToGroup($adminObj[$i]);
					$list .= $adminInfo[$i]['email'].', ';
				}
				return array('status'=>'ok', 'msg'=> 'Create default admin list '.$list );
			} else 
				return array('status'=>'error', 'msg'=> 'WHMCS admin list error' );
		} else {
			return array('status'=>'error', 'msg'=>$resp['error'] );
		}
	}
	public function defaultGroup( $name ) {
		$mgroups = $this->mgroups;
		for($i=0; $i<count($mgroups); $i++){
			if( $mgroups[$i]['name'] == $name )
				return $mgroups[$i];
		}
		return $mgroups[0];
	}
	

	public function autoApplyAlertsToAll($monitor_type) {
		$type = $monitor_type;
		
		$ext = $this->allExternalMonitors( $monitor_type );
		if( $monitor_type == 'ping') {
			$type = 'external';
		}
		
		if( $ext ) {
			$aIds = array();
			for($i=0; $i<count($ext); $i++) {
				$aIds[] = $ext[$i]['monitor_id'];
			}

			if( count($aIds) > 0) {
				$ids = implode(',', $aIds);
				$alertGroupId = MonitisConf::$settings[$monitor_type]['alertGroupId'];
				$rest = MonitisApiHelper::addNotificationRule( $ids, $type, $alertGroupId, MonitisConf::$settings[$monitor_type]['alertRules'] );
				if( $rest && $rest['status'] == 'ok') {
					$table = 'mod_monitis_int_monitors';
					if( $monitor_type == 'ping') {
						$table = 'mod_monitis_ext_monitors';
					}
					$resp = $this->updateAlertGroupIds( $ids, $alertGroupId, $table);
				}
			}

		}
	}        
        
        /////////////////////**************For test******************************88*****///////////////////////////////////////////
       
        	public function autoApplyAlertsToAll_1($monitor_type) {
		$type = $monitor_type;
		
		$ext = $this->allExternalMonitors( $monitor_type );
		if( $monitor_type == 'ping') {
			$type = 'external';
		}
		
               // _dump($ext);
		if( $ext ) {
			
			for($i=0; $i<count($ext); $i++) {
				$aIds[] = $ext[$i]['monitor_id'];
			}
                       
                        // _dump($aIds);
                         
			if( count($aIds) > 0) {
				$ids = implode(',', $aIds);
				$alertGroupId = MonitisConf::$settings[$monitor_type]['alertGroupId'];
                             //   _dump($alertGroupId);
                                 $r =  MonitisApi::deleteNotificationRule(array( 'contactGroupId'=>$alertGroupId, 'monitorId'=>$aIds, 'monitorType'=>$type));
                                _dump( $r );
				$rest = MonitisApiHelper::addNotificationRule( $ids, $type, $alertGroupId, MonitisConf::$settings[$monitor_type]['alertRules'] );
                               // _dump($rest);
				if( $rest && $rest['status'] == 'ok') {
					$table = 'mod_monitis_int_monitors';
					if( $monitor_type == 'ping') {
						$table = 'mod_monitis_ext_monitors';
					}
					$resp = $this->updateAlertGroupIds( $ids, $alertGroupId, $table);
                                      //  _dump($resp);
				}
			}

		}
	} 
 /////////////////////***************End for test*******************///////////////////////////////////////////////////////////
         public function getGroupInfoById($group_id) {
		return $this->groupById($group_id);
	}
	
    public function whmcsAdminList(){  
		return $this->adminList();             
	} 
        
     public function getWhmcsAdmin($email){
        $whmcsAdminList=$this->whmcsAdminList();
        
        for($i=0; $i< count($whmcsAdminList); $i++){
            if($whmcsAdminList[$i]['email']==$email){
               return $whmcsAdminList[$i];
            }
        }
      
    }    
    
    
   public function filterWhmcsAdminList($groupId){  
       $whmcsAdminList=$this->whmcsAdminList();
       $contactInfo = MonitisApiHelper::getContactsEmailByGroup($groupId);    
       $filterContacts=array(); 
      
       for($i=0; $i<count($whmcsAdminList); $i++){
            if(!(in_array($whmcsAdminList[$i]['email'],  $contactInfo))){
               $filterContacts[]=$whmcsAdminList[$i]; 
            }
       }       
       return   $filterContacts;       
   }     
  

 public function whmcsAdminEmailList(){
     $array=array();
     $whmcsAdminList=$this->whmcsAdminList();
     for($i=0; $i<count($whmcsAdminList); $i++){
         $array[]=$whmcsAdminList[$i]['email'];
     }
     return $array;
 }
 
public function existContact($email){
     $contactList = MonitisApi::getContacts();
     
     for($i=0; $i<count($contactList); $i++){
         if($contactList[$i]["contactAccount"]== $email){
             return $contactList[$i];            
          }          
     }
     return null;
   }
  
   public function getGruopIdList(){
         $allGroups=  MonitisApi::getContactGroupList(); 
         $idList=array();
          for($i=0; $i<count($allGroups); $i++){
             $idList[]=$allGroups[$i]["id"];
         }
         return $idList;
  }
  
 public function getGroupIdsByContcatId($contactId){
     $idList=$this->getGruopIdList();
     $allGroups=  MonitisApi::getContactsByGroupID(implode(",", $idList));  
     $array=array();     
     for($i=0; $i<count($allGroups); $i++){         
         foreach($allGroups[$i]['contacts']  as $contact){
         if($contact['contactId']==$contactId){
             $array[]=$allGroups[$i]['contactGroupId'];
         }
        }
     }
    return $array;
 }
 
public function notWhmcsAdmins($groupID){
    $whmscEmails =$this->whmcsAdminEmailList();
    $array=array();
    $groupContacts =  MonitisApi::getContactsByGroupID($groupID);
       for($i=0; $i<count($groupContacts); $i++){
                        foreach($groupContacts[$i]['contacts'] as $contact ){
                             if(!(in_array($contact['account'], $whmscEmails))){                                
                                 $array[]=$contact["name"];                                 
                               }
                             }
                          }
                         return $array;
  }
	
	
}
?>