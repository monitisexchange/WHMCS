<?
//
define('MONITISAPIURL_JS', 'https://api.monitis.com');
//define('MONITISAPIURL_JS', 'http://dashboard.monitis.com');
//define('MONITISAPIURL_JS', 'http://prelive.monitis.com');



define('MONITISAPIURL', MONITISAPIURL_JS.'/api');

define('MONITIS_APP_URL', '?module=monitis_addon');
define('MONITIS_PAGE_LIMIT', 20);
define('MONITIS_CLIENT_ID', 1 );	// admin
define('MONITIS_LOGGER', true );
define('MONITIS_m_log_LOGGER', false );

/*
 *	for notification Rule
 */

define('MONITIS_NOTIFICATION_RULE','{"period":{"always":{"value":1,"params":null},"specifiedTime":{"value":0,"params":{"timeFrom":"00:00:00","timeTo":"23:59:00"}},"specifiedDays":{"value":0,"params":{"weekdayFrom":{"day":1,"time":"00:00:00"},"weekdayTo":{"day":7,"time":"23:59:00"}}}},"notifyBackup":1,"continuousAlerts":1,"failureCount":2}');
/*
 *	order behavior settings
 */
define('MONITIS_ORDER_BEHAVIOR', '{
	"active":{ "active":1,"noaction":0,"suspended":0},
	"pending":{"noaction":0,"suspended":1,"unlink":0,"delete":0},
	"suspended":{"noaction":0,"suspended":1,"unlink":0,"delete":0},
	"terminated":{"noaction":0,"suspended":0,"unlink":0,"delete":1},
	"deleted":{"noaction":0,"suspended":0,"unlink":0,"delete":1},
	"cancelled":{"noaction":0,"suspended":0,"unlink":0,"delete":1},
	"fraud":{"noaction":0,"suspended":0,"unlink":1,"delete":0}
}');

/*
 *	for create monitors for the services
 */
define('MONITIS_FIELD_WEBSITE', 'URL/IP');
define('MONITIS_FIELD_MONITOR', 'Monitor type');
define('MONITIS_EXTERNAL_MONITOR_TYPES', 'http,https,ping');

define('MONITIS_ADMIN_MONITOR_TYPES', 'ping,cpu,memory,drive');
?>
