<?php
define("I", "INFO");
define("D", "DEBUG");
define("W", "WARNING");
define("E", "ERROR");
class L{
	private static $logFile = "/var/www/whmcs/modules/addons/monitis_addon/_log.log";
	
	public function __construct(){
		
	}
//=============	
	public static function ii($info){
		self::log(self::$logFile, I,$info);
	}
	
	public static function i($logFile, $info){
		self::log($logFile, I,$info);
	}
		
	public static function d($logFile, $debug){
		self::log($logFile, D,$debug);
	}
	
	public static function w($logFile, $warning){
		self::log($logFile, W,$warning);
	}
	
	public static function e($logFile, $error){
		self::log($logFile, E,$error);
	}
	
	
	
//===============	
	private static function log($logFile, $LOG_TAG,$log_text,$script=null){
		
		$ip = $_SERVER ['REMOTE_ADDR'];
		$time = date ('Y-m-d H:i:s');
		if ($script == null) {
			$script = $_SERVER ['PHP_SELF'];
		}
		//$string = "$LOG_TAG -|- $ip - $time -|- $script -|- $log_text\r\n\n";
		
		$string = "=$LOG_TAG -|- $time -|- $log_text\r\n\n";
		
		if (! $f = fopen ( $logFile, "a" )) return FALSE;
		if (! fwrite ( $f, $string )) return FALSE;
		chmod($logFile, 0777);
		fclose ( $f );
		return TRUE;
	}
	
}

?>
