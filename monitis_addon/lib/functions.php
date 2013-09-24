<?

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

function m_log( $log_text, $title='', $filename=''){

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

function _logActivity($str) {
	if( MONITIS_LOGGER ) {
		logActivity("MONITIS LOG ***** ".$str);
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


function monitis_embed_module( $publicKey, $width, $height ) {
	

	//$endpoint = 'http://173.192.34.112:8080';
	return '<script type="text/javascript">
	monitis_embed_module_id="'.$publicKey.'";
	monitis_embed_module_width="'.$width.'";
	monitis_embed_module_height="'.$height.'";
	monitis_embed_module_readonlyChart ="false";
	monitis_embed_module_readonlyDateRange="false";
	monitis_embed_module_datePeriod="0";
	monitis_embed_module_view="1";
	//monitis_embed_module_detailedError=false;
	monitis_embed_module_detailedMsg="Error mesaage!!!!!!!!!!!!!";
	</script>
	<script type="text/javascript" src="'.MONITISAPIURL_JS.'/sharedModule/shareModule.js"></script>
	<noscript>...</noscript>';
// Please enable JavaScript to see the report!
}

/*
function groupNameByGroupId( $alertGroupId, & $groupList ) {
	for($i=0; $i<count($groupList); $i++) {
		if($groupList[$i]['id'] == $alertGroupId )
			return $groupList[$i]['name'];
	}
	return '';
}

function groupTitleInit( $alertGroupId, & $groupList ) {
	$max_len = 20;
	$grouptitle = $groupname = 'no alert';
	if($alertGroupId > 0 ) {
		$groupname = groupNameByGroupId( $alertGroupId, $groupList  );
		$grouptitle = (strlen($groupname) > $max_len ) ? substr($groupname, 0, $max_len) .'...' : $groupname;
	}
	$group = array( 'id'=>$alertGroupId, 'name'=>$groupname, 'title'=>$grouptitle );
	return $group;
}
*/

?>