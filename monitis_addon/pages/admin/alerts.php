<?php
$notifications = new notificationsClass();
if(isset($_POST['action'])){
	switch($_POST['action']){
		case 'save_contacts':
			if(
				!isset($_POST['group_id']) || !isset($_POST['group_name']) ||
				!isset($_POST['contacts_to_add']) || !isset($_POST['contacts_to_remove'])
			) {
				break;
			}
			$groupId = (int)$_POST['group_id'];
			$groupName = (int)$_POST['group_name'];
			$contactsToAdd = $_POST['contacts_to_add'] ? explode(',', $_POST['contacts_to_add']) : null;
			$contactsToRemove = $_POST['contacts_to_remove'] ? explode(',', $_POST['contacts_to_remove']) : null;
			
			if($groupId == MonitisConf::$settings['groups']['external']['groupId']) {
				$monitorType = 'external';
			}
			elseif($groupId == MonitisConf::$settings['groups']['internal']['groupId']) {
				$monitorType = 'internal';
			}
			$alertRules = MonitisApiHelper::getNotificationRuleByTypeGroup($monitorType, $groupId);
			if(!$alertRules) {
				$alertRules = MonitisConf::$settings['groups'][$monitorType]['alert'];
			}
			if(is_array($contactsToRemove)) {
				foreach($contactsToRemove as $contact) {
					$contactInfo = $notifications->existContact($contact);
					$ids = $notifications->getGroupIdsByContcatId($contactInfo['contactId']);
					if(in_array($groupId, $ids)) {
						$indexToRemove = array_search($groupId, $ids);
						array_splice($ids, $indexToRemove, 1);
					}
					$response = MonitisApi::editContact($contactInfo['contactId'], implode(',', $ids));
					if ($response['status'] == 'ok') {
						MonitisApiHelper::deleteNotificationRule($contactInfo['contactId'], $monitorType);
					}
				}
			}
			if(is_array($contactsToAdd)) {
				foreach($contactsToAdd as $contact) {              
					$contactInfo = $notifications->existContact($contact);
					if($contactInfo) {
						$ids = $notifications->getGroupIdsByContcatId($contactInfo['contactId']);
						if(!in_array($groupId, $ids)) {
							array_push($ids, $groupId);
						}
						MonitisApi::editContact($contactInfo['contactId'], implode(",", $ids));
						
						$notificationRuleIdsOnContact = $notifications->getNotificationRuleIds($contactInfo['contactId'], $monitorType);
						$notificationRuleIds = $notifications->getNotificationRuleIdsByType($monitorType);
					  
						
						if($notificationRuleIdsOnContact) {
							$notifications->editNotificationRule($contactInfo['contactId'], $groupName, $alertRules, $notificationRuleIdsOnContact);
						}
						else {
							if($notificationRuleIds) {
								$notifications->editNotificationRule($contactInfo['contactId'], $groupName, $alertRules, $notificationRuleIds);
							}
							else {
								MonitisApiHelper::addNotificationRule($contactInfo['contactId'], $monitorType, $groupId, $alertRules);
							}
						}
					}
					else {
						$timezone = MonitisConf::$settings["timezone"];
						$contactWhmcs = $notifications->getWhmcsAdmin($contact);                       
						$contactNew = array(
							'firstName' => $contactWhmcs['firstname'],
							'lastName' => $contactWhmcs['lastname'],
							'account' => $contactWhmcs['email'],
							'contactGroupIds' => $groupId,
							'contactType' => 1,
							'timezone' => $timezone,
							'confirmContact' => 'true'
						); 
					
						$response = MonitisApi::addContactToGroup($contactNew);
						if($response['status'] == 'ok') {
							$contactInfo = $response['data'];
							$notificationRuleIdsOnContact = $notifications->getNotificationRuleIds($contactInfo['contactId'], $monitorType);
							if ($notificationRuleIdsOnContact) {
								$notifications->editNotificationRule($contactInfo['contactId'], $groupName, $alertRules, $notificationRuleIdsOnContact);
							} else {
								MonitisApiHelper::addNotificationRule($contactInfo['contactId'], $monitorType, $groupId, $alertRules);
							}
						}else{
							MonitisApp::addWarning($response['error']);
						}
					}
				}
			}
		break;
		case 'save_notifications':
			if(!isset($_POST['group_id']) || !isset($_POST['group_alerts'])) {
				break;
			}
			$groupId = (int)$_POST['group_id'];
			$groupName = $_POST['group_name'];
			$groupType = $_POST['group_type'];         
	  
			$groupAlerts = str_replace("~", '"', $_POST['group_alerts']);
			$groupAlerts = json_decode($groupAlerts, true);
		  
			$monitisContactGroup = MonitisApi::getContactsByGroupID($groupId);
			
			if(is_array($monitisContactGroup)){
				foreach($monitisContactGroup[0]['contacts'] as $contact){ 
				    
					$notificationRuleIdsOnContact = $notifications->getNotificationRuleIds($contact['contactId'], $groupType); 
					if($notificationRuleIdsOnContact!=''){
					$notifications->editNotificationRule($contact['contactId'], $groupName, $groupAlerts, $notificationRuleIdsOnContact);
					}else{
					    MonitisApiHelper::addNotificationRule($contact['contactId'], $groupType, $groupId, $groupAlerts);
					}
				}
			}
		break;
	}
}
MonitisApp::printNotifications();


if(!MonitisApi::getContactsByGroupID('All')){    
   $notifications->createDefaultGroup();
}
$monitisContactGroups = MonitisApi::getContactsByGroupID('All');

$whmcsAdmins = monitisSqlHelper::query('SELECT CONCAT(firstname, " ", lastname) as name, email FROM tbladmins');
$contactGroups = array();

for($i = 0; $i < count($monitisContactGroups); $i++){
	$contactGroups[$i] = array();
	$contactGroups[$i]['id'] = $monitisContactGroups[$i]['contactGroupId'];
	$contactGroups[$i]['name'] = $monitisContactGroups[$i]['contactGroupName'];

	$alertRules = '';
	if(MonitisConf::$settings['groups']['external']['groupId'] == $monitisContactGroups[$i]['contactGroupId'] ) {
		$monitorType = 'external';
		$externalAlerts = MonitisApiHelper::getNotificationRuleByTypeGroup($monitorType, $monitisContactGroups[$i]['contactGroupId']);
		if ($externalAlerts) {
			$alertRules = json_encode($externalAlerts);
			$alertRules = str_replace('"', "~", $alertRules);
		}
		else {
			$alertRules = json_encode(MonitisConf::$settings['groups'][$monitorType]['alert']);
			$alertRules = str_replace('"', "~", $alertRules);
		}
	}
	elseif(MonitisConf::$settings['groups']['internal']['groupId'] == $monitisContactGroups[$i]['contactGroupId']) {
		$monitorType = 'internal';
		$internalAlerts = MonitisApiHelper::getNotificationRuleByTypeGroup($monitorType, $monitisContactGroups[$i]['contactGroupId']);
		if ($internalAlerts) {
			$alertRules = json_encode($internalAlerts);
			$alertRules = str_replace('"', "~", $alertRules);
		}
		else {
			$alertRules = json_encode(MonitisConf::$settings['groups'][$monitorType]['alert']);
			$alertRules = str_replace('"', "~", $alertRules);
		}
	}
	$contactGroups[$i]['type'] = $monitorType;
	$contactGroups[$i]['alerts'] = $alertRules;

	$contactGroups[$i]['whmcsContacts'] = array();
	$contactGroups[$i]['notWhmcsContacts'] = array();
	for($j = 0; $j < count($monitisContactGroups[$i]['contacts']); $j++){
		$contact = array(
			'email' => $monitisContactGroups[$i]['contacts'][$j]['account'],
			'name' => $monitisContactGroups[$i]['contacts'][$j]['name']
		);
		
		$isWhmcsContact = false;
		foreach($whmcsAdmins as $whmcsAdmin){
			if($monitisContactGroups[$i]['contacts'][$j]['account'] == $whmcsAdmin['email']){
				$isWhmcsContact = true;
			}
		}
		if($isWhmcsContact){
			$contactGroups[$i]['whmcsContacts'][] = $contact;
		}
		else{
			$contactGroups[$i]['notWhmcsContacts'][] = $contact;
		}
	}
}
//_dump($contactGroups);
?>


<style>
#monitis_contacts_dialog_div .contacts-group, .contacts-whmcs, .contacts-buttons{
	float: left;
	height: 200px;
}
#monitis_contacts_dialog_div .contacts-buttons{
	 height: 100px;
	 margin-top:40px;
}
#monitis_contacts_dialog_div .contacts-buttons div {
	 margin-top:20px;
}
#monitis_contacts_dialog_div .group-name{
    font-weight: bold;
}
#monitis_contacts_dialog_div .contacts-group select, .contacts-whmcs select{
    width: 200px;
    padding: 2px 7px;
    margin: 5px 7px
}
#monitis_contacts_dialog_div .group-label{
	padding: 15px 0px 15px 10px;
}
#monitis_contacts_dialog_div .group-name{
padding: 0px 0px 5px 10px;
}
.monitis-notifications table.form td{
	padding: 2px 0px;  
}

.monitis-notifications table.form td.fieldlabel{
	min-width: 160px;
}
.monitis-notifications table.datatable th{
	text-align: left;
	padding-left: 20px;
}

.monitis-notifications .contacts{
	margin:10px 0px; 
	padding:0px;
	float:left;
	width: 650px;
}
.monitis-notifications .buttons{
	float: right;
}
.monitis-notifications .contacts li{
	display: inline;
}
.monitis-notifications .contacts li:after{
	display: inline;
	content: ',';
}
.monitis-notifications .contacts li:last-child:after{
	display: none;
}
.monitis-notifications .disabled{
	color: #CCCCCC;
}



</style>

<script type="text/javascript">
$(document).ready(function(){
	$('.edit-contacts').click(function(){
		var $element = $(this).closest('tr');
		var groupname = 'Contacts';
		$('#monitis_contacts_dialog_div').dialog({
			'width': 520,         
		'modal': true,
			'title' : groupname,
			'open': function(){
				var html =
					'<div class="group-label">Select contacts to alert</div>'+
					'<div class="contacts-whmcs">'+
						'<div class="group-name">WHMCS admins list:</div>'+
						'<select size="10"></select>'+
					'</div>'+
					'<div class="contacts-buttons">'+
						'<div><input type="button" class="btn add" name="add" value="»" /></div>'+
						'<div><input type="button" class="btn remove" name="remove" value="«" /></div>'+
					'</div>'+
					'<div class="contacts-group">'+
						'<div class="group-name">Selected:</div>'+
						'<select size="10"></select>'+
					'</div>';
				$(this).html(html);
				$(this).attr('data-id', $element.attr('data-id'));
				//$(this).attr('data-name', $element.attr('data-name'));
				$group = $(this).find('.contacts-group select');
				$whmcs = $(this).find('.contacts-whmcs select');
				//$(this).find('.contacts-group .group-name').html($element.attr('data-name'));
				var whmcsAdmins = {};
				$('.monitis-notifications .whmcs-admins li').each(function(){
					whmcsAdmins[$(this).attr('data-email')] = $(this).html();
				});
				$element.find('.contacts li').each(function(){
					var name = $(this).html();
					var email = $(this).attr('data-email');
					var $option = $('<option value="' + email + '">' + name + '</option>')
					if($(this).hasClass('disabled')){
						$option.attr('disabled', true);
					}
					$group.append($option);
					
					delete whmcsAdmins[email];
				});
				for(key in whmcsAdmins){
					var email = key;
					var name = whmcsAdmins[key];
					var $option = $('<option value="' + email + '">' + name + '</option>')
					$whmcs.append($option);
				}
				$(this).find('.remove').click(function(){
					var $dialog = $(this).closest('#monitis_contacts_dialog_div');
					var $group = $dialog.find('.contacts-group select');
					var $whmcs = $dialog.find('.contacts-whmcs select');
					$whmcs.append($group.find('option:selected'));
				});
				$(this).find('.add').click(function(){
					var $dialog = $(this).closest('#monitis_contacts_dialog_div');
					var $group = $dialog.find('.contacts-group select');
					var $whmcs = $dialog.find('.contacts-whmcs select');
					$group.append($whmcs.find('option:selected'));
				});
			},
			'buttons': [
				{
					'text': 'Save',
					'class': 'btn',
					'click': function(){
try {
	setTimeout( 'monitisModal.open({content: null});', 1);
} catch(ex) {}
						var $form = $('<form></form>');
						$form.attr('action', '');
						$form.attr('method', 'POST');
						$form.hide();
						$form.append('<input type="text" name="action" value="save_contacts" />');
						$form.append('<input type="text" name="group_id" value="'+ $(this).attr('data-id') +'" />');
						$form.append('<input type="text" name="group_name" value="'+ $(this).attr('data-name') +'" />');
						var contactsToAdd = [];
						$(this).find('.contacts-group select option').each(function(){
							contactsToAdd.push($(this).attr('value'));
						});
						$form.append('<input type="text" name="contacts_to_add" value="'+ contactsToAdd.join(',') +'" />');
						var contactsToRemove = [];
						$(this).find('.contacts-whmcs select option').each(function(){
							contactsToRemove.push($(this).attr('value'));
						});
						$form.append('<input type="text" name="contacts_to_remove" value="'+ contactsToRemove.join(',') +'" />');
						$(this).append($form);

						$form.submit();
						$(this).dialog('close');
					}
				},{
					'text': 'Cancel',
					'class': 'btn',
					'click': function(){
						$(this).dialog('close');
					}
				}
			]
		});
	});
	$('.edit-notifications').click(function(){
		var $element = $(this).closest('tr');
		var type = $element.attr('data-type');
		var alerts = $element.attr('data-alerts');
		if(alerts != '') {
			var group = {
				id: null,
				name: null,
				list: null
			}
			var popup = new monitisNotificationRuleClass(alerts, type, group, function(alerts, group) {
			
try {
	setTimeout( 'monitisModal.open({content: null});', 1);
} catch(ex) {}
				var $form = $('<form></form>');
				$form.attr('action', '');
				$form.attr('method', 'POST');
				$form.hide();
				$form.append('<input type="text" name="action" value="save_notifications" />');
				$form.append('<input type="text" name="group_id" value="'+ $element.attr('data-id') +'" />');
				$form.append('<input type="text" name="group_name" value="'+ $element.attr('data-name') +'" />');
				$form.append('<input type="text" name="group_type" value="'+ $element.attr('data-type') +'" />');
				$form.append('<input type="text" name="group_alerts" value="'+ alerts +'" />');
				$('body').append($form);

				$form.submit();
			});
		}
	});
});
</script>
<div id="monitis_notification_dialog_div"></div>
<div id="monitis_contacts_dialog_div"  ></div>

<div class="monitis-notifications">
	<ul class="whmcs-admins" style="display: none;">
		<? foreach($whmcsAdmins as $whmcsAdmin): ?>
		<li data-email="<?=$whmcsAdmin['email'] ?>"><?=$whmcsAdmin['name'] ?></li>
		<? endforeach ?>
	</ul>
	<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
		<thead>
		<tr>
			<th width="30%">Alert Rules</th>
			<th width="70%">Contacts</th>            
		</tr>
		</thead>
		<tbody>
		<? if(is_array($contactGroups) && count($contactGroups)): ?>
		<? foreach($contactGroups as $group): ?>
		<tr data-id="<?= $group['id'] ?>" data-name="<?= $group['name'] ?>" data-type="<?= $group['type'] ?>" data-alerts="<?= $group['alerts'] ?>">
			<td><?= $group['name'] ?></td>
			<td>
			   
				<ul class="contacts">
					<? foreach($group['whmcsContacts'] as $contact): ?>
					<li data-email="<?= $contact['email'] ?>"><?= $contact['name'] ?></li>
					<? endforeach ?>
					<? foreach($group['notWhmcsContacts'] as $contact): ?>
					<li data-email="<?= $contact['email'] ?>" class="disabled"><?= $contact['name'] ?></li>
					<? endforeach ?>
				</ul>
				  
				<div class="buttons">
				<?if(!empty($group['alerts'])) {?>
					<button class="btn edit-contacts">Contacts</button>
					<button class="btn edit-notifications" <? if(!count($group['whmcsContacts'])): ?>disabled="disabled"<? endif ?>>Alert rule</button>
				<?}?>
				</div>
				 
			</td>           
		</tr>
		<? endforeach ?>
		<? else: ?>
		<tr>
			<td colspan="2">No alerts available.</td>
		</tr>
		<? endif ?>
		</tbody>
	</table>
</div>