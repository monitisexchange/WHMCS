<?php
/*
 * MonitisHelper monitisSqlHelper
 */
class MonitisHelper {

	/*
	 * addon name - monitis_addon;	field name - adminuser
	 */ 
	static function getAdminName() {
		return MonitisConf::$adminName; 
	}

	static function parentDomain() {
		$resp = MonitisApi::userInfo(array("apikey"=>MonitisConf::$apiKey));
		if( isset($resp['account']) ) {
			$userDomain = explode('@', $resp['account']);
			return $userDomain[1];
		} else {
			return 'monitis.ccom';
		}
	}
	
	static function checkAdminName() {
		$admin = monitisSqlHelper::objQuery('SELECT value, tbladmins.id, tbladmins.firstname, tbladmins.lastname 
			FROM tbladdonmodules
			LEFT JOIN tbladmins ON (tbladmins.username = tbladdonmodules.value AND tbladmins.username=tbladdonmodules.value)
			WHERE tbladdonmodules.module="monitis_addon" AND tbladdonmodules.setting="adminuser" && tbladmins.id > 0 ');
		$username = '';
		if(!$admin) {
			$vals = monitisSqlHelper::objQuery('SELECT tbladmins.username FROM tbladmins 
			LEFT JOIN tbladminroles on (tbladminroles.id=tbladmins.roleid) ORDER BY tbladmins.id');
			$username = $vals['username'];
		} else {

			$username = $admin['value'];
		}
		return $username;
	}

	static function adminUrl() {
		$url = $_SERVER['HTTP_REFERER'];
		$pos = strpos($url, 'addonmodules.php');
		return substr($url, 0, $pos);
	}

	static function adminOrderUrl($orderid) {
		$adminUrl = self::adminUrl();
		return $adminUrl.'orders.php?action=view&id='.$orderid;
	}

	static function adminAddonUrl() {
		$adminUrl = self::adminUrl();
		return $adminUrl.'addonmodules.php?module=monitis_addon';
	}

	static function adminServicerUrl($userid, $serviceid) {
		$adminUrl = self::adminUrl();
		return $adminUrl.'clientsservices.php?userid='.$userid.'&id='.$serviceid;
	}

	// utilities
	static function in_array( & $arr, $fieldName, $fieldValue, $caseSense=0 ){
		if($arr && count($arr) > 0) {
			for($i=0; $i<count($arr); $i++) {
				if(isset($arr[$i][$fieldName]) && 
				( (!$caseSense && strtolower($arr[$i][$fieldName]) == strtolower($fieldValue)) || 
					($caseSense && $arr[$i][$fieldName] == $fieldValue)	))
					return $arr[$i];
			}
		}
		return null;
	}
	static function in_array_index( & $arr, $fieldName, $fieldValue){
		if($arr && count($arr) > 0) {
			for($i=0; $i<count($arr); $i++) {
				if($arr[$i][$fieldName] == $fieldValue)
					return $i;
			}
		}
		return -1;

	}
	
	static function locationsInterval($locs, $locIds) {
		$arr = array();
		for($i=0; $i<count($locIds); $i++) {
			$item = self::in_array( $locs, 'id', $locIds[$i]);
			if($item) {
				$arr[] = ''.$locIds[$i].'-'.$item['checkInterval'];
			} else {
				$arr[] = ''.$locIds[$i].'-1';
			}
		}
		if(count($arr) > 0 )
			return implode(',',$arr );
		else
			return null;
	}

	static function idsByField(& $list, $fieldName){
		$ids = array();
		if($list && count($list) > 0) {
			$cnt = count($list);
			for($i=0; $i<$cnt;$i++) {
				if( empty($fieldName) )
					$ids[] = $list[$i];
				else
					$ids[] = $list[$i][$fieldName];
			}
			$ids = array_unique($ids); 
		}
		return $ids;
	}

	// log
	static function log($description, $title='') {
		if(MONITIS_LOG_ALLOW) {
			$values = array('date' => date("Y-m-d H:i:s", time()));
			$values['title'] = $title;
			if(is_array($description)) {
				$values['description'] = json_encode($description);
				$values['type'] = 'json';
			} else {
				$values['description'] = $description;
				$values['type'] = 'text';
			}
			insert_query(MONITIS_LOG_TABLE, $values);
		}
	}
	
	// dump log
	static function dump() {
		return monitisSqlHelper::query('SELECT * FROM '.MONITIS_LOG_TABLE.' ORDER BY `date` DESC');
	}
}

class monitisSqlHelper {
	// mySql select query
	static function query($sql) {
		$result = @mysql_query($sql); // or die("Error in query: " . mysql_error() . "<br>" . $sql . "<br>");
		if (!is_resource($result))
			return null;
		$num_rows = mysql_num_rows($result);
		$vObj = array();
		if ($num_rows > 0) {
			$i = 0;
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$vObj[$i] = array();
				foreach ($row as $key => $value)
					$vObj[$i][$key] = $value;
				$i++;
			}
		} else
			$vObj = null;
		return $vObj;
	}

	static function objQuery($sql) {		// oQuery
		$vals = self::query($sql);
		if($vals) {
			return $vals[0];
		} else
			 return null;
	}

	static function altQuery($sql) {
		return @mysql_query($sql); // or die("Error in DELETE query: " . mysql_error() . "<br>" . $sql . "<br>");
	}

    static function pageQuery($sql) {
		$vals = self::query($sql);
		if($vals) {
			$result = @mysql_query('SELECT FOUND_ROWS() as __count'); // or die("Error in FOUND_ROWS query: " . mysql_error() . "<br>" . $sql . "<br>");
			$count = 0;
			while ($row = mysql_fetch_object($result)) {
				$count = $row->__count;
			}
			$vals[0]['total'] = $count;
			return $vals;
		}
		else
			return null;
	}
}


function _dump($var) {
	echo "<div style='border: 2px solid #ccc; padding: 3px; margin: 2px; text-align: left;'><pre>";
	var_dump($var);
	echo "</pre></div>";
}

function _db_table ( $table ) {
	$sql = 'SELECT * FROM '.$table;
echo "<p>$sql</p>";
	$result = mysql_query( $sql );
	$vObj = null;
	if($result !== false) {
		$i = 0;
		$vObj = array();
		while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$vObj[$i] = array();
			foreach ($row as $key => $value) {
				$vObj[$i][$key] = $value;
			}
			$i++;
		}
	} 
_dump($vObj);
}

function monitisForQA($action='', $method='', $params='', $result=''){

    $values=array( 'action' =>  $action, 
	           'method' =>  $method, 	      
		   'params' =>  json_encode($params),
		   'date'   =>  date("Y-m-d H:i:s", time()),
		   'code'    =>  md5(json_encode($params)),
		   'result' =>  json_encode($result),
                );
    $sql = 'SELECT * FROM mod_monitis_qa WHERE action="'.$action.'" AND code= "'.md5(json_encode($params)).'"';
    $resultSet = monitisSqlHelper::query($sql);     
    if($resultSet){
	$update = array( 'action' =>$action, 'result'=>json_encode($result), 'params'=>json_encode($params), 'date'=>date("Y-m-d H:i:s", time()));
	$where = array('id' =>$resultSet[0]['id']);
	update_query('mod_monitis_qa', $update, $where);
    }else{
	insert_query('mod_monitis_qa', $values);
    }
   
} 



function m_log( $log_text, $title='', $filename=''){
	
	define('MONITIS_m_log_LOGGER', false );
	
	if( MONITIS_m_log_LOGGER ) {
		$logPath = '/var/www/whmcs/modules/addons/monitis_addon/_logs/';
		$file = 'log';
		if( !empty($filename) ) $file = $filename;
		$logFile = "$logPath$file.log";
		$time = date ('Y-m-d H:i:s');
		
		$text = '';
		if( is_array($log_text) ) {
			//$text = json_encode($log_text);
			$text = var_export($log_text, true);
		} else {
			$text = $log_text;
		}
		$string = "$time -|- $title -|- $text\r\n\n";

		if (! $f = fopen ( $logFile, "a" )) return FALSE;
		if (! fwrite ( $f, $string )) return FALSE;
		fclose ( $f );
		chmod($logFile, 0777);
	}
}

function monitisMacros($log_text = '', $write = ''){
    
			$filename = 'monitis_macros';
			$logPath  = '/var/www/whmcs/modules/addons/monitis_addon/_logs/';
			$file     = 'log';
			
			if( !empty($filename) ) $file = $filename;		
			$logFile = "$logPath$file.log";	
			
		if($write){
		    
			$st = array('monitis_macros'=>$log_text);
			$st = json_encode($st);
			if (! $f = fopen ( $logFile, "w" )) return FALSE;
			if (! fwrite ( $f, $st )) return FALSE;
		    
		    }else{
		    
			$file_contnet = file_get_contents($logFile, true);
			$file_contnet = json_decode($file_contnet, true);
			return $file_contnet;
		    }
		    
		fclose ( $f );
		chmod($logFile, 0777);

}


function _logActivity($str, $title='') {
	if( MONITIS_LOGGER ) {
		logActivity("MONITIS LOG ***** ".$str);
		MonitisHelper::log($str, $title);
	}
}

function monitisLog($str, $title='') {

	if( MONITIS_LOG_ALLOW ) {
		MonitisHelper::log($str, $title);
	}
}


function monitisGet($varName, $default = '') {
	return isset($_GET[$varName]) ? $_GET[$varName] : $default;
}
function monitisGetInt($varName, $default = 0) {
	return isset($_GET[$varName]) ? (int)$_GET[$varName] : $default;
}
function monitisPost($varName, $default = '') {
	return isset($_POST[$varName]) ? $_POST[$varName] : $default;
}
function monitisPostInt($varName, $default = 0) {
	return isset($_POST[$varName]) ? (int)$_POST[$varName] : $default;
}


function monitis_embed_module( $publicKey, $width, $height, $suspendmsg='Error message!!!!!!!!!!!!!') {
	
	return '<script type="text/javascript">
	monitis_embed_module_id="'.$publicKey.'";
	monitis_embed_module_width="'.$width.'";
	monitis_embed_module_height="'.$height.'";
	monitis_embed_module_readonlyChart ="false";
	monitis_embed_module_readonlyDateRange="false";
	monitis_embed_module_datePeriod="0";
	monitis_embed_module_view="1";
	//monitis_embed_module_detailedError=false;
	monitis_embed_module_detailedMsg="'.$suspendmsg.'";
	</script>
	<script type="text/javascript" src="'.MONITISAPIURL_JS.'/sharedModule/shareModule.js"></script>
	<noscript>...</noscript>';
// Please enable JavaScript to see the report!
}

?>