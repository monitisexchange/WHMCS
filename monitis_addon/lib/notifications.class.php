<?

class notificationsClass {

	private $mgroups = null;

	public function setupContactGroups() {  // ???
		$mgroups = MonitisApi::getContactGroupList();

		if (!$mgroups) {
			$resp = $this->createDefaultGroup();
			if ($resp['status'] == 'ok') {
				$mgroups = MonitisApi::getContactGroupList();
			}
			else
				return $resp;
		}
		$this->mgroups = $mgroups;
		// ===========================================================
		return array('status' => 'ok', 'data' => $mgroups);
	}

//**************************GROUPS && ADMINS***********************//

	public function createDefaultGroup1() {
		$defaultgroups = json_decode(MONITIS_ADMIN_CONTACT_GROUPS, true);
		$existedGroups = MonitisApi::getContactGroupList(); // existed monitis groups

		foreach ($defaultgroups as $mType => $groupName) {

			$group = MonitisHelper::in_array($existedGroups, 'id', MonitisConf::$settings['groups'][$mType]['groupId']);
			$existNotifByType = MonitisApiHelper::getNotificationRuleByType($mType);

			$alerts = json_decode(MONITIS_NOTIFICATION_RULE, true);
			if ($mType == 'internal') {
				$alerts['minFailedLocationCount'] = null;
			}

			$alertRulesDefault = ($existNotifByType) ? $existNotifByType : $alerts;

			if ($group) {
				$notifByTypeGroup = MonitisApiHelper::getNotificationRuleByTypeGroup($mType, $group['id']);
				if ($notifByTypeGroup) {
					$alertRulesDefault = $notifByTypeGroup;
				}
				MonitisConf::$settings['groups'][$mType]['groupId'] = $group['id'];
				MonitisConf::$settings['groups'][$mType]['groupName'] = $group['name'];
				MonitisConf::$settings['groups'][$mType]['alert'] = $alertRulesDefault;
			} else {
				$newGroupName = $groupName;
				$resp = MonitisApi::addContactGroup(1, $newGroupName);
				if ($resp['status'] == 'ok') {
					MonitisConf::$settings['groups'][$mType]['groupId'] = $resp['data'];
					MonitisConf::$settings['groups'][$mType]['groupName'] = $newGroupName;
					MonitisConf::$settings['groups'][$mType]['alert'] = $alertRulesDefault;
				} else {
					// error
					return array('status' => 'error', 'msg' => 'Add contact group error ' . $resp['error']);
				}
			}

			// $r = $this->addContacts(ucfirst($mType), MonitisConf::$settings['groups'][$mType]['groupId']);
		}

		MonitisConf::update_settings(json_encode(MonitisConf::$settings));
		return array('status' => 'ok', 'msg' => 'External, internal groups sets success');
	}

	public function createDefaultGroup() {
		$defaultgroups = json_decode(MONITIS_ADMIN_CONTACT_GROUPS, true);
		$existedGroups = MonitisApi::getContactGroupList(); // existed monitis groups

		foreach ($defaultgroups as $mType => $groupName) {

			$group = MonitisHelper::in_array($existedGroups, 'id', MonitisConf::$settings['groups'][$mType]['groupId']);

			$alerts = json_decode(MONITIS_NOTIFICATION_RULE, true);
			if ($mType == 'internal') {
				$alerts['minFailedLocationCount'] = null;
			}
			$groupId = ($group['id']) ? $group['id'] : 0;
			$notifByTypeGroup = MonitisApiHelper::getNotificationRuleByType1($mType, $groupId);
			$alertRulesDefault = ($notifByTypeGroup) ? $notifByTypeGroup : $alerts;
			if ($group) {
				MonitisConf::$settings['groups'][$mType]['groupId'] = $group['id'];
				MonitisConf::$settings['groups'][$mType]['groupName'] = $group['name'];
				MonitisConf::$settings['groups'][$mType]['alert'] = $alertRulesDefault;
			} else {
				$newGroupName = $groupName;
				$resp = MonitisApi::addContactGroup(1, $newGroupName);
				if ($resp['status'] == 'ok') {
					MonitisConf::$settings['groups'][$mType]['groupId'] = $resp['data'];
					MonitisConf::$settings['groups'][$mType]['groupName'] = $newGroupName;
					MonitisConf::$settings['groups'][$mType]['alert'] = $alertRulesDefault;
				} else {
					// error
					return array('status' => 'error', 'msg' => 'Add contact group error ' . $resp['error']);
				}
			}

			// $r = $this->addContacts(ucfirst($mType), MonitisConf::$settings['groups'][$mType]['groupId']);
		}

		MonitisConf::update_settings(json_encode(MonitisConf::$settings));
		return array('status' => 'ok', 'msg' => 'External, internal groups sets success');
	}

	public function addContacts($mtype, $groupId) {
		//$contactList = $this->query('SELECT * FROM tbladmins LIMIT 3');
		$contactList = monitisSqlHelper::query('SELECT * FROM tbladmins LIMIT 3');


		$existNotif = MonitisApiHelper::getNotificationRuleByType($mtype);
		$alertRulesDefault = ($existNotif) ? $existNotif : json_decode(MONITIS_NOTIFICATION_RULE, true);
		for ($i = 0; $i < count($contactList); $i++) {

			$monitisEmailList = MonitisApiHelper::getContactsEmails();

			if (!(in_array($contactList[$i]['email'], array_values($monitisEmailList)))) {
				$adminObj = array(
					'firstname' => $contactList[$i]['firstname'],
					'lastname' => $contactList[$i]['lastname'],
					'account' => $contactList[$i]['email'],
					'contactType' => 1,
					'timezone' => MonitisConf::$settings['timezone'],
					'group' => $mtype,
					'confirmContact' => 'true'
				);
				$resp = MonitisApi::addContactToGroup($adminObj);

				if ($resp['status'] == 'ok') {
					$contactInfo = $resp['data'];
					$notifIdsOnContact = $this->getNotificationRuleIds($contactInfo['contactId'], $mtype);

					if ($notifIdsOnContact) {
						// $alertRulesDefault = MonitisApiHelper::getNotificationRule($contactInfo['contactId'], $mtype);
						$this->editNotificationRule($contactInfo['contactId'], ucfirst($mtype), $alertRulesDefault, $notifIdsOnContact);
					} else {
						MonitisApiHelper::addNotificationRule($contactInfo['contactId'], $mtype, $groupId, $alertRulesDefault);
					}
				}
			} else {
				$contactId = array_search($contactList[$i]['email'], $monitisEmailList);
				$gidlist = $this->getGroupIdsByContcatId($contactId);
				$groupIds = (count($gidlist) != 0) ? implode(",", $gidlist) . ',' . $groupId : $groupId;
				MonitisApi::editContact($contactId, $groupIds);

				$checkNotif = MonitisApiHelper::getNotificationRule($contactId, $mtype);
				if (!$checkNotif) {
					MonitisApiHelper::addNotificationRule($contactId, $mtype, $groupId, $alertRulesDefault);
				} else {
					$notificationRuleIds = $this->getNotificationRuleIds($contactId, $mtype);
					// $alertRulesDefault = $checkNotif;
					$this->editNotificationRule($contactId, ucfirst($mtype), $alertRulesDefault, $notificationRuleIds);
				}
			}
		}
	}

	public function editNotificationRule($contact_id, $contactGroup, $alertRules, $notificationRuleIds) {
		$result = array();
		if ($notificationRuleIds) {

			$info = array(
				'contactId' => $contact_id,
				'contactGroup' => $contactGroup,
				'notificationRuleIds' => $notificationRuleIds
			);
			$infoSet = MonitisApiHelper::rulesFromJson($info, $alertRules);

			$resp = MonitisApi::editNotificationRule($infoSet);

			if ($resp['status'] == 'ok') {
				$result['status'] = 'ok';
				$result['msg'] = 'A notification has been successfully set';
			} else {
				$result['status'] = 'error';
				$result['msg'] = $resp['error'];
			}
		} else {
			$result['status'] = 'error';
			$result['msg'] = 'Notification is not set';
		}
		return $result;
	}

	public function getNotificationRuleIdsByType($monitor_type) {
		$notificationRuleIds = array();

		$params = array('monitorType' => $monitor_type);
		$notifSet = MonitisApi::getNotificationRules($params);

		for ($i = 0; $i < count($notifSet); $i++) {

			if ($notifSet[$i]['contactId'] == "All") {
				$notificationRuleIds[] = $notifSet[$i]['id'];
			}
		}

		$notificationRuleIds = implode(",", $notificationRuleIds);
		return $notificationRuleIds;
	}

	public function getNotificationRuleIds($contact_id, $monitor_type) {
		$notificationRuleIds = array();

		$params = array('contactId' => $contact_id, 'monitorType' => $monitor_type);
		$notifSet = MonitisApi::getNotificationRules($params);

		for ($i = 0; $i < count($notifSet); $i++) {

			if ($notifSet[$i]['contactId'] != "All") {
				$notificationRuleIds[] = $notifSet[$i]['id'];
			}
		}

		$notificationRuleIds = implode(",", $notificationRuleIds);
		return $notificationRuleIds;
	}

	public function getGroupInfoById($group_id) {
		return $this->groupById($group_id);
	}

	public function whmcsAdminList() {
		//return $this->adminList();
		return monitisSqlHelper::query('SELECT * FROM tbladmins');
	}

	public function getWhmcsAdmin($email) {
		$whmcsAdminList = $this->whmcsAdminList();
		if (count($whmcsAdminList) > 0) {
			for ($i = 0; $i < count($whmcsAdminList); $i++) {

				if ($whmcsAdminList[$i]['email'] == $email) {
					return $whmcsAdminList[$i];
				}
			}
		}
		return null;
	}

	public function filterWhmcsAdminList($groupId) {
		$whmcsAdminList = $this->whmcsAdminList();
		$contactInfo = MonitisApiHelper::getContactsEmailByGroup($groupId);
		$filterContacts = array();
		if (count($whmcsAdminList) > 0) {
			for ($i = 0; $i < count($whmcsAdminList); $i++) {

				if (count($contactInfo) > 0 && !(in_array($whmcsAdminList[$i]['email'], $contactInfo))) {
					$filterContacts[] = $whmcsAdminList[$i];
				} elseif (count($contactInfo) == 0) {
					$filterContacts[] = $whmcsAdminList[$i];
				}
			}
			return $filterContacts;
		}
		return null;
	}

	public function whmcsAdminEmailList() {
		$array = array();
		$whmcsAdminList = $this->whmcsAdminList();
		if (count($whmcsAdminList) > 0) {
			for ($i = 0; $i < count($whmcsAdminList); $i++) {
				$array[] = $whmcsAdminList[$i]['email'];
			}
			return $array;
		}
		return null;
	}

	public function existContact($email) {
		$contactList = MonitisApi::getContacts();
		for ($i = 0; $i < count($contactList); $i++) {
			if ($contactList[$i]["contactAccount"] == $email) {
				return $contactList[$i];
			}
		}
		return null;
	}

	public function getGruopIdList() {
		$allGroups = MonitisApi::getContactGroupList();
		$idList = array();
		if ($allGroups) {
			for ($i = 0; $i < count($allGroups); $i++) {
				$idList[] = $allGroups[$i]["id"];
			}
			return $idList;
		}
		return null;
	}

	public function getGroupIdsByContcatId($contactId) {
		$idList = $this->getGruopIdList();
		$allGroups = MonitisApi::getContactsByGroupID(implode(",", $idList));
		$array = array();
		for ($i = 0; $i < count($allGroups); $i++) {
			foreach ($allGroups[$i]['contacts'] as $contact) {
				if ($contact['contactId'] == $contactId) {
					$array[] = $allGroups[$i]['contactGroupId'];
				}
			}
		}
		return $array;
	}

}

?>