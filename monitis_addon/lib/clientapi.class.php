<?php

class monitisClientApiAccess {

	static $endpoint = MONITISAPIURL;
	
	
	static function jsonDecode($result) {

		if(empty($result)) {
			//return array('status'=>'error', 'code'=>101);
			return null;
		} else {
			$result = utf8_encode($result);
			$resp = json_decode($result, true);
			if(empty($resp) && gettype($resp) != 'array') {
				//MonitisConf::$apiServerError = $err;
				//return array('status'=>'error', 'code'=>101);
				return null;
			}
			return $resp;
		}
	}
	
	
	static function requestGet($action, $params) {
		// TODO: error handling when JSON is not returned
		$authToken = MonitisConf::$authToken;
		if( $authToken) {
			$params['version'] = MONITIS_API_VERSION;
			$params['action'] = $action;
			$params['authToken'] = $authToken;
			$query = http_build_query($params);
			
			$url = self::$endpoint . '?' . $query;
			
			$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($ch);
monitisLog("client requestGet **** action = <b>$action</b><p>$url</p><p>$result</p>");
			//$resp = json_decode($result, true); // mml
			$resp = self::jsonDecode($result);
			
			if(@$resp['error'] && @$resp['errorCode'] && $resp['errorCode'] == 4) {
				if(MonitisConf::update_token())
					return self::requestGet($action, $params);
				else
					return array('status'=>'error', 'msg'=>'Monitis server not response');
			} 
			return $resp;
		} else {
			if(MonitisConf::update_token())
				return self::requestGet($action, $params);
			else
				return array('status'=>'error', 'msg'=>'Monitis server not response');
		}
	}

	/*
	 * requestPost
	 */
	static function requestPost($action, $params, $user) {

		//$authToken = $user['auth_token'];
		$authToken = MonitisConf::$authToken;
		if( $authToken) {
			$params['validation'] = 'token';
			$params['version'] = MONITIS_API_VERSION;
			$params['action'] = $action;
			$params['timestamp'] = date("Y-m-d H:i:s", time());
			$params['apikey'] = $user['api_key'];
			$params['authToken'] = $authToken;
			$query = http_build_query($params);

			$ch = curl_init(self::$endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1 );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
			$result = curl_exec($ch);
monitisLog("client requestPost **** action = <b>$action</b><p>$query</p><p>$result</p>");
			//$resp = json_decode($result, true);
			$resp = self::jsonDecode($result);

			if(@$resp['error'] && @$resp['errorCode'] && $resp['errorCode'] == 4) {
					if(MonitisConf::update_token())
						return self::requestPost($action, $params);
					else
						return array('status'=>'error', 'msg'=>'Monitis server not response');
			} //else
			return $resp;
		} else {
			if(MonitisConf::update_token())
				return self::requestPost($action, $params);
			else
				return array('status'=>'error', 'msg'=>'Monitis server not response');
		}
	}
}


class monitisClientApi extends monitisClientApiAccess {

	static function clientDetail($userid) {
		return monitisSqlHelper::objQuery('SELECT id as userid, firstname as firstName, lastname as lastName, email, LOWER(status) as status
			FROM tblclients 
			WHERE id='.$userid);
	}
	
	static function activeClient($userid) {
		return monitisSqlHelper::objQuery('SELECT id as userid, firstname as firstName, lastname as lastName, email, LOWER(status)
			FROM tblclients 
			WHERE id='.$userid.' AND status="Active"');
	}

	static function whmcsLinkUser($userid) {
		return monitisSqlHelper::objQuery('SELECT * FROM '.MONITIS_USER_TABLE.' WHERE user_id='.$userid);
	}

	static function linksMonitors($userid) {
		return monitisSqlHelper::query('SELECT * FROM mod_monitis_product_monitor WHERE user_id='.$userid);
	}

	static function getUserByEmail($user) {
		$allUsers = MonitisApi::clients();
		for($i=0; $i<count($allUsers); $i++) {
			if( strtolower($allUsers[$i]['account']) == strtolower($user['email'])) {
				return $allUsers[$i];
			}
		}
		return null;
	}
	
	static function linkUserByApikey($apikey, $userid) {
	
		$result = array('status'=>'error', 'data' =>null);
		$resp = self::requestGet('secretkey', array('apikey'=>$apikey));

		if( !@$resp['error'] && $resp['secretkey']) {
			$secretkey = $resp['secretkey'];

			$value = array(
				'user_id' => $userid,
				'api_key' => $apikey,
				'secret_key' => $secretkey
			);
			insert_query(MONITIS_USER_TABLE, $value);
			
			$result['status'] = 'ok';
			$result['msg'] = 'success';
			$result['data'] = $value;
		} else {
			$result['action'] = 'getSecretkey';
			$result['msg'] = $resp['error'];
		}
		return $result;
	}
	
	static function generateAccount($userid) {
		// MonitisHelper::parentDomain();
		return array('email' => strtolower('whmcs'.$userid.'_'.MonitisConf::$apiKey.'@'.MonitisConf::$parentDomain),
			'password' => MonitisConf::$apiKey.'_'.$userid
		);
	}
		
	static function editClient($params, $userid) {
	
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			return self::requestPost('editUser', $params, $user); 
		} else {
			return $user;
		}
	}
	
	static function addMonitisUser($user) {

		$result = array('status'=>'error');
		$userid = $user['userid'];
		$monitisAccount = self::generateAccount($userid);

		$user['email'] = $monitisAccount['email'];
		$user['password'] = $monitisAccount['password'];
		$user['role'] = 'Client_Unlimited';
		
		$userInfo = array(
			'user_id' => $userid,
			'api_key' => MonitisConf::$apiKey,		//  parent apiKey
			'secret_key' => MonitisConf::$secretKey,
			'parentAPIKey' => MonitisConf::$apiKey
		);

		// addClient
		$resp = self::requestPost('addClient', $user, $userInfo);
		if(@$resp['status'] == 'ok') {
			$apikey = $resp['data'];
			$result = self::linkUserByApikey($apikey, $userid);
		} elseif( isset($resp['errorCode']) && $resp['errorCode'] == 17) {
			$params['userName'] = $user['email'];
			$params['password'] = md5($user['password']);
			$resp = self::requestGet('apikey', $params);
			
			if(@$resp['apikey']) {
				$apikey = $resp['apikey'];
				$result = self::linkUserByApikey($resp['apikey'], $userid);
			} else {
				$result['action'] = 'getApiKey';
				$result['msg'] = $resp['error'];
			}

		} else {
			$result['action'] = 'addClient';
			$result['msg'] = $resp['error'];
		}
		return $result;
	}

	// user contact
	static function addContact($params, $userid) { 
		$params["contactType"] = 1;		// email
		$params["timezone"] = MonitisConf::$settings['timezone'];		// admin time zone
		$params["sendDailyReport"] = 'true';
		$params["confirmContact"] = 'true';
		$params["textType"] = '0';
		
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			return self::requestPost('addContact', $params, $user); 
		} else {
			return $user;
		}
	}
	
	static function editContact($params, $userid) {
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			return self::requestPost('editContact', $params, $user); 
		} else {
			return $user;
		}
	}
	
	static function userApiInfoById($userid) {
		$user = self::whmcsLinkUser($userid);
		$result = array('status'=>'error', 'data'=>null);
		
		if($user) {
			$result['status'] = 'ok';
			$result['msg'] = 'success';
			$result['data'] = $user;
		} else {
			$usr = self::clientDetail($userid);

			if($usr && $usr['status'] == 'active') {
				$resp = self::addMonitisUser($usr);
//monitisLog($resp, 'addMonitisUser userid='.$userid );
				if($resp['status'] == 'ok' && isset($resp['data']) ) {
					$user = $resp['data'];
					$result = $resp;
					// add contact action
					$resp = self::addContact(array('firstName'=>$usr['firstName'],'lastName'=>$usr['lastName'],'account'=>$usr['email']), $userid);
//monitisLog($resp, 'Action addContact userid='.$userid );
				} else {
					//
					$result['msg'] = $resp['msg'];
				}
			} else {
				$result['msg'] = $resp['msg'];
				$result['error'] = $resp['msg'];
			}
		}
		return $result;
	}
	
	static function unlinkUserMonitors($userid) {
		monitisSqlHelper::altQuery('DELETE FROM mod_monitis_product_monitor WHERE user_id='.$userid);
		return array('status'=>'ok');
	}
	static function unlinkUser($userid) {
		monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_USER_TABLE.' WHERE user_id='.$userid);
		return array('status'=>'ok');
	}
	
	// -------------------------------
	static function restorUserByEmail($email, $apikey) {
	
		$arr = explode('_', $email);
		if($arr && $arr[0]) {
			$userid = intval(substr($arr[0], 1));
			if($userid > 0) {
				$user = self::clientDetail($userid);

				if($user && $user['status'] == 'active') {
					return self::linkUserByApikey($apikey, $userid);
				} else {
					return array('status'=>'error', 'msg'=>'Incorrect or inactive WHMCS user');
				}
			} 
			return array('status'=>'error', 'msg'=>'Unable to recover this user');
		}
	}
	// -------------------------------

	static function deleteUserByApikey($apikey) {
	
		$params = array(
			'clientAPIKey' => $apikey
		);
		$user = array(
			'api_key' => MonitisConf::$apiKey,
			'secret_key' => MonitisConf::$secretKey
		);
		return self::requestPost('deleteClient', $params, $user);
	}
	
	static function deleteUserById($userid) {
		
		$result = array('status'=>'error', 'msg'=>'User does not exist');
		$user = self::whmcsLinkUser($userid);
		if($user) {
			$resp = self::deleteUserByApikey($user['api_key']);
			if($resp && $resp['status'] == 'ok') {
				self::unlinkUser($userid);
				self::unlinkUserMonitors($userid);
				$result['status'] = 'ok'; 
				$result['msg'] = 'User deleted successfully'; 
			} else {
				$result['status'] = 'error'; 
				$result['msg'] = $resp['error']; 
			}
		} else {
			// check monitis account
			$monitisAccount = self::generateAccount($userid);
			$resp = self::getUserByEmail($monitisAccount);
			if($resp && $resp['apikey']) {
				$result = self::deleteUserByApikey($resp['apikey']);
			}
		}
		return $result;
	}
	////////////////////////////////////////////////////////////////////////////////
    ////////////////////////// mod_monitis_servers 
	static function addExternalMonitor(& $product, $settings, $user) {

		$url = $product['web_site'];
		if (empty($url))
			return false;

		$locationIDs = array_map("intval", $settings["locationIds"]);
		$locations = implode(',', $locationIDs);
		$monitor_type = $product['monitor_type'];

		$params = array(
			'type' => $monitor_type,
			'name' => $url . '_' . $monitor_type,
			'url' => $url,
			'interval' => $settings["interval"],
			'timeout' => $settings["timeout"],
			'locationIds' => $locations,
			'tag' => $product["tag"]
		);
		return self::requestPost('addExternalMonitor', $params, $user);
	}
	
    static function deleteExternalMonitor(& $monitor) {
		$monitor_id = $monitor['monitor_id'];
		$user = self::userToken($monitor['user_id']);
		if($user['status'] == 'ok') {
			$params = array(
				'testIds' => $monitor_id
			);
			$resp = self::requestPost('deleteExternalMonitor', $params, $user);
			if(@$resp['status'] == 'ok') {
				monitisWhmcsServer::unlinkExternalMonitorById($monitor_id);
			}
			return $resp;
		} else
			return $user;
    }

	static function suspendExternal(& $monitor) {
		$user = self::userToken($monitor['user_id']);
		if($user['status'] == 'ok') {
			$params = array(
				'monitorIds' => $monitor['monitor_id']
			);
			return self::requestPost('suspendExternalMonitor', $params, $user);
		} else {
			return $user;
		}
	}
	
	static function activateExternal(& $monitor) {
		$user = self::userToken($monitor['user_id']);
		if($user['status'] == 'ok') {
			$params = array(
				'monitorIds' => $monitor['monitor_id']
			);
			return self::requestPost('activateExternalMonitor', $params, $user);
		} else {
			return $user;
		}
	}
	
	static function getExternalMonitorInfo($monitor_id, $userid) {
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			$params = array(
				'apikey' => $user['api_key'],
				'testId' => $monitor_id
			);
			return self::requestGet('testinfo', $params);
		} else {
			return $user;
		}
	}
	// get
	static function getWidget($params, $userid) {
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			$params["apikey"] = $user['api_key'];
			return self::requestGet('getWidget', $params);
		} else {
			return $user;
		}
	}

	// post
	static function editExternalMonitor(& $params, $userid) {
	
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			return self::requestPost('editExternalMonitor', $params, $user);
		} else {
			return $user;
		}
	}
	
	static function alertToObject($rule) {
		$notif = MonitisApiHelper::rulesToJson($rule);
		return array(
			'id' => $rule["id"],					// notification id
			'muteFlag' => $rule["muteFlag"],
			'contactId' => $rule["contactId"],
			'contactActive' => $rule["contactActive"],
			'monitorId' => $rule["monitorId"],
			'contactGroup' => $rule["contactGroup"],
			'rule' => json_encode($notif)
		);
	}
	
	// post
	static function addNotificationRule($params, $rule, $userid) {
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			$params = MonitisApiHelper::rulesFromJson($params, $rule);
			$resp = self::requestPost('addNotificationRule', $params, $user);  
			return $resp;
		} else {
			return $user;
		}
	}

	// post
	static function editNotificationRule($params, $rule, $userid) {

		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			$params = MonitisApiHelper::rulesFromJson($params, $rule);
			return self::requestPost('editNotificationRule', $params, $user);  
		} else {
			return $user;
		}
	}

	static function getNotificationRules($params, $userid) {
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			$params["apikey"] = $user['api_key'];
			$rules = self::requestGet('getNotificationRules', $params);  
			$obj = null;
			if($rules && count($rules) > 0) {
				$obj = array();
				for($i=0; $i<count($rules); $i++) {
					$obj[] = self::alertToObject($rules[$i]);
				}
			}
			return $obj;
		} else {
			return $user;
		}
	}

	static function contactsList($userid) {
		$resp = self::userToken($userid);
		if($resp['status'] == 'ok') {
			$params["apikey"] = $resp['api_key'];
			return self::requestGet('contactsList', $params);  
		} else {
			return $resp;
		}
	}

	
	static function externalMonitors($userid, $testId='') {
	
		$user = self::userToken($userid);
		if($user['status'] == 'ok') {
			if(!empty($testId))
				$params['testId'] = $testId;
			$params["apikey"] = $user['api_key'];
			return self::requestGet('tests', $params);
		} else {
			return $user;
		}
	}


	/*
	 * get and check user authToken
	 */
	static function userToken($userid) {
	
		$user = self::whmcsLinkUser($userid);
		if($user) {
			$user['status'] = 'ok';
			return $user;
		} else {
			// get whmcs user
			$usr = self::clientDetail($userid);
			if($usr && $usr['status'] == 'active') {
				$monitisAccount = self::generateAccount($userid);
				$resp = self::getUserByEmail($monitisAccount);
				if($resp) {
					$apikey = $resp['apikey'];
					
					$resp = self::linkUserByApikey($apikey, $userid);
					if($resp['status'] == 'ok' && $resp['data']) {
						$user = $resp['data'];
						$user['status'] = 'ok';
						return $user;
					} else {
						return array('status' => 'error', 'msg'=>'Not Monitis user');
					}
				} else {
					return array('status' => 'warning', 'msg'=>'Not Monitis user');
				}
			} else {
				return array('status' => 'error', 'msg'=>'Not WHMCS user or user user closed');
			}
		}	
	}

	///////////////////////////////////////////
	static function userHookHandler(& $vars, $hook) {
		$userid = $vars['userid'];
		$result = array('status'=>'nomonitis', 'userid'=>$userid);
		
		$action = MonitisConf::$settings['user_behavior'][$hook];
		if($action != 'noaction') {
			$user = self::whmcsLinkUser($userid);
			if($user) {
				if($action == 'unlink') {
					$resp = self::unlinkUserMonitors($userid);
				} elseif($action == 'delete') {
					$resp = self::deleteUserById($userid);
				}
				$result['status'] = 'ok';
			}
		}
		return $result;
	}
}


function monitisClientHookHandler(& $vars, $hook) {
	$resp = monitisClientApi::userHookHandler( $vars, $hook );
//monitisLog( $resp, 'userHookHandler');
}
?>