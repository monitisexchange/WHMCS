<?

define('MONITISAPIURL_JS', 'https://api.monitis.com');
//define('MONITISAPIURL_JS', 'http://dashboard.monitis.com');
//define('MONITISAPIURL_JS', 'http://173.192.34.112:8080');


define('MONITISAPIURL', MONITISAPIURL_JS.'/api');


define('MONITIS_APP_URL', '?module=monitis_addon');
define('MONITIS_PAGE_LIMIT', 20);
define('MONITIS_CLIENT_ID', 1 );	// admin
define('MONITIS_LOGGER', true );


/*
 *	order behavior settings
 */
define('MONITIS_ORDER_BEHAVIOR_TITLE', '{
		"noaction":"No action",
		"create":"Create/Active",
		"suspend":"Suspend",
		"delete":"Delete"
	}');

define('MONITIS_ORDER_BEHAVIOR', '{
	"active":{ "create":1,"noaction":0,"suspend":0},
	"pending":{"noaction":0,"suspend":1,"delete":0},
	"cancelled":{"noaction":0,"suspend":0,"delete":1},
	"fraud":{"noaction":0,"suspend":0,"delete":1}
}');

/*
 *	for notification Rule
 */
define('MONITIS_WEEKDAYS', '["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]');
/*
{
	"period": {
		"always":{
			"title":"Active always",
			"desc":"active always"

		},
		"specifiedTime":{
			"title":"Active every day",
			"desc":"active every day on specified time interval",
			"params":{"timeFrom":"Time From", "timeTo":"Time To"}
		},
		"specifiedDays":{
			"title":"Active day and time period",
			"desc":"active on specified day and time period",		
			"params":{
				"weekdayFrom":"weekday From",
				"weekdayTo":"weekday To"
			}
		}

	}
}
*/

define('MONITIS_NOTIFICATION_RULE','{

	"period":{
		"always":{"value":1, "params":null},
		
		"specifiedTime":{"value":0, "params":{
			"timeFrom":"00:00",
			"timeTo":"23:59"
		}},
		"specifiedDays":{"value":0, "params":{
			"weekdayFrom":{
				"day":1,
				"time":"00:00"
			},
			"weekdayTo":{
				"day":7,
				"time":"23:59"
			}
		}}
	},
	"notifyBackup":1,
	"continuousAlerts":1,
	"failureCount":2
}');




/*
 *	for create monitors for the services
 */
define('MONITIS_FIELD_WEBSITE', 'URL/IP');
define('MONITIS_FIELD_MONITOR', 'Monitor type');
define('MONITIS_MONITOR_TYPES', 'http,https,ping');

?>