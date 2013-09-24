<?php

$oWHMCS = new notificationsClass();
$action = monitisPost('actiontype');

if ($action) {

    switch ($action) {      
           
       case 'addGroup':
              $new_group = isset($_POST["new_group"]) ? $_POST["new_group"] : '';
            
            if($new_group){
                 $resp=MonitisApi::addContactGroup(1, $new_group); 
                
               if($resp["error"]){
                    MonitisApp::addWarning("The group with this name already exists");              
               }                
            }else{
                 MonitisApp::addWarning("Enter group name");
            }             
            break; 
       case 'Delete':
             $group_name = isset($_POST["group_name"]) ? $_POST["group_name"] : '';
             if($group_name){
                MonitisApi::deleteContactGroup($group_name);
             }
             break; 
      }
      
}

MonitisApp::printNotifications();
$groupIds=$oWHMCS->getGruopIdList();
$contactsByGroup = MonitisApi::getContactsByGroupID(implode(",", $groupIds));
$whmscEmails=$oWHMCS->whmcsAdminEmailList();

?>

 <form method="post" action="" >
            <div style="float:left; height: 40px; ">
                 <span class="fieldlabel" >Group name :</span>
                 <span><input type="text" name="new_group" /></span>                 
                 <span>
                 <input type="hidden" name="actiontype" value="addGroup" >
                 <input type="submit" name="add" value="Add Group"   />
                 </span>
            </div>
  </form>
<form method="post" action="" >
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
            <tr>
                <th width="20%" >Contact Groups </th>
                <th width="20%">Group Contacts</th>     
                <th>&nbsp;</th>
            </tr>
     <? for($i=0; $i<count($contactsByGroup); $i++){  ?>    
            <tr>             
                <td><input type="text" class="group_name" name="group_name" value="<?=$contactsByGroup[$i]['contactGroupName'] ?>" readonly  /> </td>          
                <td > 
                   <?
                  
                   foreach($contactsByGroup[$i]['contacts'] as $contact ){ 
                            if(in_array($contact['account'], $whmscEmails)){?>                         
                             <div><?=$contact['name'] ?></div>       
                           <? } }?>                      
                      <? 
                      $notWhmcsAdmins=$oWHMCS->notWhmcsAdmins($contactsByGroup[$i]['contactGroupId'] );
                      for($j=0; $j<count($notWhmcsAdmins); $j++){ ?>
                                 <div style="color:#669999"><?=$notWhmcsAdmins[$j] ?></div>  
                      <? } ?>
                </td>    
                <td align="center" >        
                <span><a href="<?php echo MONITIS_APP_URL ?>&monitis_page=contactGroup&group_id=<?=$contactsByGroup[$i]['contactGroupId']?>"> <input type="button" value="Edit" /></a></span>
                <input type="hidden" name="actiontype" value="Delete" />
                <span><input type="submit" value="Delete"  class="btn-danger"/></span>
                
                </td>
            </tr>     
     <? } ?>
     
</table>
</form>
<style>
    .group_name{
        border:none;
        background:transparent;
    }
</style>