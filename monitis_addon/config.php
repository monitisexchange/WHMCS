<?php
//
define('MONITISAPIURL_JS', 'https://api.monitis.com');

define('MONITISAPIURL', MONITISAPIURL_JS.'/api');

define('MONITIS_APP_URL', '?module=monitis_addon');
define('MONITIS_PAGE_LIMIT', 20);
define('MONITIS_API_VERSION', 3);		// Monitis API version
define('MONITIS_RESOURCE_VERSION', 3);
define('MONITIS_CLIENT_ID', 1 );		// admin
define('MONITIS_REMOVE_TABLES', false);		// drop Monitis addon all tables
define('MONITIS_LOGGER', false);			// hide Monitis addon Activity Log

/*
 *	for notification Rule
 */
define('MONITIS_NOTIFICATION_RULE','{"period":{"always":{"value":1,"params":null},"specifiedTime":{"value":0,"params":{"timeFrom":"00:00:00","timeTo":"23:00:00"}},"specifiedDays":{"value":0,"params":{"weekdayFrom":{"day":1,"time":"00:00:00"},"weekdayTo":{"day":7,"time":"23:59:00"}}}},"notifyBackup":1,"continuousAlerts":1,"failureCount":2, "minFailedLocationCount":null }');

/*
 *	order behaviour settings
 */
define('MONITIS_ORDER_BEHAVIOR', '{
	"active":{ "active":1,"noaction":0,"suspended":0},
	"pending":{"noaction":0,"suspended":1,"delete":0},
	"suspended":{"noaction":0,"suspended":1,"delete":0},
	"terminated":{"noaction":0,"suspended":0,"delete":1},
	"deleted":{"noaction":0,"suspended":0,"delete":1},
	"cancelled":{"noaction":0,"suspended":0,"delete":1},
	"fraud":{"noaction":0,"suspended":1,"delete":0}
}');

/*
 *	client status behaviour settings
 */
define('MONITIS_USER_STATUS_BEHAVIOR', '{
	"closed":{"delete":1,"noaction":0},
	"deleted":{"delete":1,"noaction":0}
}');

/*
 *	for create monitors for the services
 */
define('MONITIS_FIELD_WEBSITE', 'URL/IP');
define('MONITIS_FIELD_MONITOR', 'Monitor type');
define('MONITIS_EXTERNAL_MONITOR_TYPES', 'http,https,ping');

define('MONITIS_ADMIN_MONITOR_TYPES', 'ping,cpu,memory,drive');
define('MONITIS_ADMIN_CONTACT_GROUPS', '{
		"external":"Uptime",		
		"internal":"Server/Device"
	}');

/*
 *	tables
 */
 
define('MONITIS_EXTERNAL_TABLE', 'mod_monitis_ext_monitors');
define('MONITIS_INTERNAL_TABLE', 'mod_monitis_int_monitors');
define('MONITIS_SETTING_TABLE', 'mod_monitis_setting');
define('MONITIS_USER_TABLE', 'mod_monitis_user');
define('MONITIS_HOOK_REPORT_TABLE', 'mod_monitis_report');
define('MONITIS_LOG_TABLE', 'mod_monitis_log');


define('MONITIS_LOG_ALLOW', true);
define('MONITIS_LOG_PAGE_LIMIT', 50);

?>