<div style="text-align: right;"><a  href="<?php echo MONITIS_APP_URL ?>&monitis_page=notification">&#8592; Back to group list</a></div>

<?
$oWHMCS = new notificationsClass();
$groupID = monitisGet('group_id');
$groupInfo = MonitisApiHelper::getGroupById($groupID);

if ($_POST['save']) {
    $post = array();
    foreach ($_POST as $key => $val) {
    if (!empty($key)) {
        $post[$key] = $val;
      }
    }
    
    
   if($post["group_name"] && $post["group_name"]!=$groupInfo["name"] ){
        MonitisApi::editContactGroup($groupInfo["name"], $post["group_name"]);  
    }    
   
  ////////********Remove Contact From Group *******///////////////////////////
     if($post["editGroup"] ){      
         $contactIds = explode(",", $post["editGroup"]);            
            for($i=0; $i<count($contactIds); $i++){ 
              $idlist=$oWHMCS->getGroupIdsByContcatId($contactIds[$i]);             
            
             if(in_array($groupID, $idlist)){
                  unset($idlist[array_search($groupID, $idlist)]);
             }
          
         $groupIds=(count($idlist)!=0)? implode("," , $idlist):''; 
         
         MonitisApi::editContact($contactIds[$i], $groupIds);
         }       
     }
    
  ///////***********Add contact**********//////////////////   
     if($post["addToGroup"]){         
            $emailList=explode(",", $post["addToGroup"]);      
        for($i=0; $i<count($emailList); $i++){
                $contact[$i]=$oWHMCS->existContact($emailList[$i]);           
              if(!$contact[$i]){
                  $timezone=MonitisConf::$settings["timezone"];
                  $adminInfo[$i]=$oWHMCS->getWhmcsAdmin($emailList[$i]);
                             _dump($adminInfo[$i]);
                            $adminObj[$i]=array( 'firstName'=>$adminInfo[$i]['firstname'],  
                                                 'lastName'=>$adminInfo[$i]['lastname'], 
                                                 'account'=>$adminInfo[$i]['email'], 
                                                 'group'=>$groupInfo["name"],  
                                                 'groupIds'=>$groupID,
                                                 'contactType'=>1, 
                                                 'timezone'=>$timezone); 

                             MonitisApi::addContactToGroup($adminObj[$i]); 


              }
            else{
                 $idlist=$oWHMCS->getGroupIdsByContcatId($contact[$i]['contactId']);    
                 $groupIds=implode(",", $idlist).','.$groupID;          
                 MonitisApi::editContact($contact[$i]['contactId'], $groupIds);
                }   
         }

     }  

}
?>

<?php
$groupContacts =  MonitisApi::getContactsByGroupID($groupID);
$whmscEmails=$oWHMCS->whmcsAdminEmailList();
$whmcsAdminList = $oWHMCS->filterWhmcsAdminList($groupID);
$groupInfo = MonitisApiHelper::getGroupById($groupID);
$notWhmcsAdmins=$oWHMCS->notWhmcsAdmins($groupID);
$listSize =(count($groupContacts)>count($whmscEmails))? count($groupContacts)+1 : count($whmscEmails)+1;
?>

<form method="post" action="" >
<table border="0" class="form" align="center">
<tr>
    <td class="fieldlabel"  valign="top" >Rename contact group :</td>
    <td valign="top" class="fieldarea"   colspan="3">  
    <input type="name" name="group_name" value="<?=$groupInfo["name"] ?>" />        
    </td>    
</tr>
<tr>
    <td valign="top" class="fieldlabel">Admin List :</td>
    <td class="fieldarea">
        <div style="float:left">
            <div class="group_name" ><?=$groupInfo["name"]?></div>
            <select  id="one" size="<?=$listSize?>" class="select" >    
                    <?
                    for($i=0; $i<count($groupContacts); $i++){
                        foreach($groupContacts[$i]['contacts'] as $contact ){
                             if(in_array($contact['account'], $whmscEmails)){ ?>             
                    
                   <option  value="<?=$contact["contactId"] ?>"><?=$contact["name"] ?></option> 
                
                    <? } } } ?>                  
               
                <? for($i=0; $i<count($notWhmcsAdmins); $i++){ ?>
                   <option style="color:#669999" disabled="disabled" ><?=$notWhmcsAdmins[$i] ?></option> 
                <? } ?>
            </select>           
            <input type="hidden" name="editGroup" id='editGroup'  />
        </div>
        <div class="button">            
            <div><input type="button" id="remove" name="remove" value="»" ></div>             
            <div><input type="button" id="add" name="add" value="«" ></div>
        </div>
        <div style="float:left">
            <div class="group_name" >WHMCS contacts</div> 
            <select  id="two"  size="<?=$listSize?>" class="select" >    
                 <? foreach($whmcsAdminList as $whmcsContact){ ?>                     
                    <option value="<?=$whmcsContact['email']?>"><?=$whmcsContact['firstname'].' '.$whmcsContact['lastname']?></option>                  
                <? } ?>
            </select>
             <input type="hidden" name="addToGroup" id='addToGroup'  />
        </div>
    </td>
</tr>
<tr>
   <td colspan="4" align="center"> <input type="submit" name="save" value="Save Settings"></td>   
</tr>
</table>
</form>
<script>    
$(document).ready(function() {   
  selectedElements();
  appendElements();

});

function appendElements(){
     $('#remove').click(function(e) {
        var selectedOpts = $('#one option:selected');         
        $('#two').append($(selectedOpts).clone());
        $(selectedOpts).remove();
    
    });

    $('#add').click(function(e) {
        var selectedOpts = $('#two option:selected');    
        $('#one').append($(selectedOpts).clone());
        $(selectedOpts).remove();
       
    });  
}

 function selectedElements(){      
    var values1 = [];
    var values2 = [];
    $("#one").change(function() { 
     
     $("#one option").each( function(event) {         
        values1.push( $("#one option:selected").val());
        $("#editGroup").val(values1.toString()); 
          event.preventDefault ? event.preventDefault() : event.returnValue = false;
     });   
  });
  $("#two").change(function() {     
     $("#two option").each( function(event) {        
        values2.push( $("#two option:selected").val());
        $("#addToGroup").val(values2.toString()); 
          event.preventDefault ? event.preventDefault() : event.returnValue = false;
     });   
   });
}

</script>

<style>
    table.form td{
      padding: 2px 0px;  
    }
    table.form td.fieldlabel{
        min-width:250px;
    }
     #two, #one{
        min-width:300px;
        padding:2px 7px;
        margin:5px 7px
    }
    .group_name{
       text-align:center;       
    }
    .button{
      float:left;
      margin-top:17px;
    }  


</style>