<?php
require_once('modules/addons/monitis_addon/monitisapp.php');
require_once('modules/addons/monitis_addon/lib/clientui.php');

$locations = MonitisApiHelper::getExternalLocationsGrouped();
//$version = rand(1, 1000);
$version = MONITIS_RESOURCE_VERSION;

//$language = $this->_tpl_vars['clientsdetails']['language'];
$language = 'english';

include_once('modules/addons/monitis_addon/lang/'.$language.'.php');
?>
<link href="http://code.jquery.com/ui/1.10.3/themes/<?php echo MonitisConf::$jqueryClientTheme?>/jquery-ui.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<link href="modules/addons/monitis_addon/static/css/monitis.css?<?php echo $version?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="modules/addons/monitis_addon/static/js/monitisclasses.js?<?php echo $version?>"></script>
<script type="text/javascript" src="modules/addons/monitis_addon/static/js/clientui.js?<?php echo $version?>"></script>
<script type="text/javascript" src="modules/addons/monitis_addon/lang/js/<?php echo $language?>.js?<?php echo $version?>"></script>
<style type="text/css">
.ui-widget {
	font-size:12px;
	z-index:1100;
}
figure {
	text-align:center;
	
}
header, h3 {
	display:block;
	clean:both;
}

header h3 {
	padding: 15px 0px;
}
section.monitis-monitor-item {
	margin-bottom:30px;
}
</style>
<div class="page-header">
    <div class="styled_title"><h1><?php echo $MLANG['my_monitors']?> </h1></div>
</div>
<?php
$userid = 0;


if(isset($_SESSION) && isset($_SESSION['uid']) && $_SESSION['uid'] > 0) {
	//$userid = $this->_tpl_vars['clientsdetails']['userid'];
	$userid = $_SESSION['uid'];
}
//echo "************ userid = $userid ***** $language <br>";

$hTimezone = null;
$contactId = 0;
if($userid > 0) {
	$hTimezone = intval(MonitisConf::$settings['timezone'])/60;		// admin time zone
	$contactsList = monitisClientApi::contactsList($userid);
	if($contactsList && count($contactsList) > 0) {
		$hTimezone = $contactsList[0]['timezone'];
		$contactId = $contactsList[0]['contactId'];
	}
}

if( isset($_POST["act"]) && isset($userid) && $userid > 0) {
	
	$action = $_POST["act"];
	$monitor_id = intval($_POST["monitor_id"]);
	$monitor_type = $_POST["monitor_type"];
	
	$params = array(
		'monitor_id'=>$monitor_id,
		'user_id'=>$userid
	);
	
	switch($action) {
		case 'suspend':
			$resp = monitisClientApi::suspendExternal($params);
			if( $resp["status"] == 'ok') {
				monitisClientUi::successMessage($MLANG['success_suspend']);
			} else {
				monitisClientUi::errorMessage($resp['error']);
			}
		break;
		case 'unsuspend':
			$resp = monitisClientApi::activateExternal($params);
			if( $resp["status"] == 'ok') {
				monitisClientUi::successMessage($MLANG['success_activate']);
			} else {
				monitisClientUi::errorMessage($resp['error']);
			}
		break;
		case 'edit':
			$monitor = monitisClientApi::getExternalMonitorInfo($monitor_id, $userid);

			$locationIDs = isset($_POST['locationIds']) ? $_POST['locationIds'] : '';

			$timeout =  '';
			if(isset($_POST['timeout'])) {
				$timeout = $_POST['timeout'];
			} else {
				$timeout =  $monitor['timeout'];
			}
			$locationIDs = explode(',', $locationIDs);
			$locationIDs = MonitisHelper::locationsInterval($monitor['locations'], $locationIDs);
			
			$monParams = array(
				'type' => $monitor_type,
				'testId' => $monitor_id,
				'name' => $monitor['url'].'_'.$monitor_type,			//$monitor['name'],
				'url' => $monitor['url'],
				//'interval' => $interval,
				'timeout' => $timeout,
				'locationIds' => $locationIDs,			
				'tag' => $monitor['tag']		// "$userid_whmcs"	
			);
			$resp = monitisClientApi::editExternalMonitor($monParams, $userid);

			if( $resp["status"] == 'ok') {
				monitisClientUi::successMessage($MLANG['monitor'].' '.$monitor['name'].' '.$MLANG['success_updated']);
			} else {
				monitisClientUi::errorMessage($resp['error']);
			}
		
		break;
		case 'edit_rule':
			$rules = str_replace("~", '"', $_POST["rule_external"]);
			$rules = json_decode($rules, true);
			$params = array(
				'notificationRuleIds' => $_POST["rule_id"],					// notification id
				'monitorId' => $monitor_id
			);
			$resp = monitisClientApi::editNotificationRule($params, $rules, $userid);
			if( $resp["status"] == 'ok') {
				monitisClientUi::successMessage($MLANG['rules_updated']);
			} else {
				monitisClientUi::errorMessage($resp['error']);
			}

			$newTimezone = $rules['timeZone'];
			if($newTimezone != $hTimezone) {
				$params = array(
					'contactId' => $contactId,
					'textType' => '0',
					'timezone' => $newTimezone*60
				);
				$resp = monitisClientApi::editContact($params, $userid);
				if( $resp["status"] == 'ok') {
					monitisClientUi::successMessage('Contact timezone updated');
					$hTimezone = $newTimezone;
				} else {
					monitisClientUi::errorMessage($resp['error']);
				}
			}

		break;
		case 'add_rule':
			$rules = str_replace("~", '"', $_POST["rule_external"]);
			$rules = json_decode($rules, true);
			$params = array(
				'monitorType' => 'external',
				'monitorId' => $monitor_id
			);
			
			$resp = monitisClientApi::addNotificationRule($params, $rules, $userid);
			if( $resp["status"] == 'ok') {
				monitisClientUi::successMessage($MLANG['rules_added']);
			} else {
				monitisClientUi::errorMessage($resp['error']);
			}

			$newTimezone = $rules['timeZone'];
			if($newTimezone != $hTimezone) {
				$params = array(
					'contactId' => $contactId,
					'textType' => '0',
					'timezone' => $newTimezone*60
				);
				$resp = monitisClientApi::editContact($params, $userid);
				if( $resp["status"] == 'ok') {
					monitisClientUi::successMessage('Contact timezone updated');
					$hTimezone = $newTimezone;
				} else {
					monitisClientUi::errorMessage($resp['error']);
				}
			}
		break;
	}
}

if($userid > 0) {
	$oService = new monitisClientUi();
	$monitors = $oService->clientProductMonitors( $userid );
	if( $monitors) {
		
		/* all user's notification rules */
		$alerts = monitisClientApi::getNotificationRules(array('monitorType'=>'external'), $userid);
		/* default notification rule - type "All" */
		$allAlert = MonitisHelper::in_array( $alerts, 'monitorId', 'All');
		
		$rule = '';
		$allRuleid = 0;
		$allContactId = 0;
		if($allAlert) {
			$rule = $allAlert['rule'];
			$allRuleid = $allAlert['id'];
			$allContactId = $allAlert['contactId'];
		} else {
			$rule = MONITIS_NOTIFICATION_RULE;
		}
		$alertAll = str_replace('"', "~", $rule);
		
		echo "<section>";
	
		//$monitors = $monitors['monitors'];

		for( $i=0; $i<count($monitors); $i++) {
			$monitor = $monitors[$i];
			$monitor_id = $monitor['monitor_id'];
			$monitor_type = $monitor['monitor_type'];

			$item = $monitor['info'];
			$sets = $monitor["settings"];
			
			$sets = json_decode($sets, true);
			//$locationIds = explode(',', $item['locationIds']);
			$locationIds = explode(',', $item['locations']);

			$settingProduct = array(
				'interval' => $interval,
				'timeout' => $item['timeout'],
				//'name' => $products[$i]['name'],
				'name' => $monitor['info']['name'],
				'types' => $monitor_type,
				'locationIds'=> $locationIds,
				'locationsMax' => $sets['locationsMax']
			);
			
			$settingProduct = json_encode($settingProduct);
			$settingProduct = str_replace('"', "~", $settingProduct);
			
			/* notification rule by monitor id */
			$alert = MonitisHelper::in_array( $alerts, 'monitorId', $monitor_id);

			$alertAction = 'add_rule';
			$rule = $alertAll;
			$ruleid = $allRuleid;
			$contactId = $allContactId;
			if($alert) {
				$alertAction = 'edit_rule';
				$rule = str_replace('"', "~", $alert['rule']);
				$ruleid = $alert['id'];
				$contactId = $alert['contactId'];
			} 
			
			/* suspend */
			$suspend = 'unsuspend';
			if(!$item["isSuspended"]) {
				$suspend = 'suspend';
			}
			// class="btn btn-small btn-inverse"
			//
?>
			<section class="monitis-monitor-item">
				<header><h3 style="float:left"><?php echo $monitor["productname"]?></h3>

					<div id="test" style="width:100px;float:right">
						<div class="btn-group">
						<button class="btn btn-small dropdown-toggle" data-toggle="dropdown"><?php echo $MLANG['actions']?> <span class="caret"></span></button>
						<ul class="dropdown-menu">
						<?php if($suspend == 'suspend') { ?>
							<li><a href="#" class="alert_monitor" monitor_id="<?php echo $monitor_id?>" monitor_type="external"><?php echo $MLANG['alert_rules']?></a></li>
							<li><a href="#" class="monitor_settings" monitor_id="<?php echo $monitor_id?>"><?php echo $MLANG['settings']?></a></li>
							<li class="divider"></li>
							<li><a href="#" class="suspend_monitor" monitor_id="<?php echo $monitor_id?>" suspend="<?php echo $suspend?>"><?php echo $MLANG['suspend_monitoring']?></a></li>
						<?php } else { ?>
							<li><a href="#" class="suspend_monitor" monitor_id="<?php echo $monitor_id?>" suspend="<?php echo $suspend?>"><?php echo $MLANG['activate_monitoring']?></a></li>
						<?php } ?>
						</ul>
						</div>
					</div>
					
				</header>
				
				<figure>
				<?php echo monitis_embed_module($monitor['publickey'], 770, 350 ); ?>
				</figure>

				<form method="post" id="rulesForm<?php echo $monitor_id?>">
				
					<input type="hidden" name="monitor_name" value='<?php echo $item["name"]?>' />
					<input type="hidden" name="settings" value='<?php echo $settingProduct?>' />
					
					<input type="hidden" name="locationIds" value='' />
					<input type="hidden" name="timeout" value='' />

					<input type="hidden" name="contactId" value='<?php echo $contactId?>' />
					<input type="hidden" name="rule_id" value='<?php echo $ruleid?>' />
					<input type="hidden" name="rule_external" class="notificationRule_external" value='<?php echo $rule?>' />
					<input type="hidden" value="<?php echo $monitor_id?>" name="monitor_id" />
					<input type="hidden" value="<?php echo $monitor_type?>" name="monitor_type" />
					<input type="hidden" name="act" value="<?php echo $alertAction?>" />
					
				</form>
				<hr style="border:solid 1px #cccccc"/>
			</section>
	<?php } ?>
		</section>
<?php } else { ?>
	<div> No monitors</div>
<?php } ?>
<?php } else { ?>
	<div>You did not login</div>
<?php } ?>
<div id="monitis_dialogs" style="display:none;"></div>
<div id="monitis_notification_dialog_div"></div>
<script type="text/javascript">
var countryname = <?php echo json_encode($locations)?>;
var timezone = '<?php echo $hTimezone?>';


$(document).ready(function() {

	// edit notification rules
	$('.alert_monitor').click(function(event){
		event.preventDefault();
		var monitor = $(this).attr("monitor_type");
		var prefix = $(this).attr("monitor_id");
		
		var groupid = $('#rulesForm'+prefix+' [name=groupid_external]').val();
		var sets_json = $('#rulesForm'+prefix+' [name=rule_external]').val();
		
		var that = $(this);
		if (sets_json ) {
			var group = {
				id: groupid,
				name: '',
				list:null
			}
			var obj = new monitisNotificationRuleClass(sets_json, monitor, group, function(not_json, group) {
				setTimeout( 'monitisModal.open({content: null});', 1);
				$('.notificationRule_' + monitor).attr('value', not_json);
				$('#rulesForm'+prefix).submit();
			}, timezone);
		}

	});
	
	// suspend / unsuspend actions
	$('.suspend_monitor').click(function(event){
		event.preventDefault();
		setTimeout( 'monitisModal.open({content: null});', 1);

		var prefix = $(this).attr("monitor_id");
		var suspend = $(this).attr("suspend");
		$('#rulesForm'+prefix+' [name=act]').val(suspend);
		$('#rulesForm'+prefix).submit();
	});
	
	
	$('.monitor_settings').click(function(event){
		event.preventDefault();
		var prefix = $(this).attr("monitor_id");
		
		var form = $('#rulesForm'+prefix);
		$(form).find('input[name="settings"]').val()
		
        var options = {
            name: $(form).find('input[name="monitor_name"]').val(),
			settings: $(form).find('input[name="settings"]').val(),
            locations: <?php echo json_encode(MonitisConf::$locations) ?>
        }

		new monitisClientProductDialog(options,function(response){
			setTimeout( 'monitisModal.open({content: null});', 1);
			
			$(form).find('input[name="timeout"]').val(response.timeout);
			$(form).find('input[name="locationIds"]').val(response.locationIds.join());
			$(form).find('input[name="act"]').val('edit');
			$(form).submit();
		});
		
	});
	
});

</script>