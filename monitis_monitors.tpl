<script type="text/javascript" src="includes/jscript/statesdropdown.js"></script>


{include file="$template/pageheader.tpl" title="My monitors" desc="Monitors list"}

{if $noregistration}

    <div class="alert alert-error">
        <p>{$LANG.registerdisablednotice}</p>
    </div>

{else}

{if $errormessage}
<div class="alert alert-error">
    <p class="bold">{$LANG.clientareaerrors}</p>
    <ul>
        {$errormessage}
    </ul>
</div>
{/if}


{/if}


<link href="includes/jscript/css/ui.all.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
{literal}
<style type="text/css">
.ui-widget {
	font-size:12px;
}
figure {
	text-align:center;
}
figure.toolbar {
	margin-bottom:20px;
}
</style>
{/literal}
{php}

require_once('modules/addons/monitis_addon/MonitisApp.php');
//require_once('modules/addons/monitis_addon/lib/product.class.php');
require_once('modules/addons/monitis_addon/lib/client.class.php');

$userid = $this->_tpl_vars['clientsdetails']['userid'];
//echo "************ userid = $userid";

if( isset($userid) && $userid > 0) {

	$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
	foreach ($locations as $key => $value) {
		if (empty($value))
			unset($locations[$key]);
	}

	$rand = rand(1, 1000);
	$head = '<script>var countryname = '.json_encode($locations).'</script>';
	$head .= '<script type="text/javascript" src="modules/addons/monitis_addon/static/js/monitisclasses.js?'.$rand.'"></script>';
	echo $head;

	if( isset($_POST["action"])) {
		$action = $_POST["action"];
		$monitor_id = intval($_POST["monitor_id"]);
		$monitor_type = intval($_POST["monitor_type"]);
		$monitor = MonitisApi::getExternalMonitorInfo($monitor_id);
		
	//_dump($_POST);
		$locationIDs = isset($_POST['locationIDs']) ? $_POST['locationIDs'] : '';
		$interval = $monitor['interval'];
		$locationIDs = explode(',', $locationIDs);
		if( $locationIDs && count($locationIDs) ) {
			$locationIDs = array_map( "intval", $locationIDs );
			$locationIDs = array_map(function($v) use($interval) { return $v . '-' . $interval; }, $locationIDs);
			$locationIDs = implode(',',$locationIDs );
		}
		$monParams = array(
			'type' => $monitor_type,
			'testId' => $monitor_id,
			'name' => $monitor['name'],
			'url' => $monitor['url'],
			'interval' => $interval,
			'timeout' => $monitor['timeout'],
			'locationIds' => $locationIDs,			
			'tag' => $monitor['tag']
		);
		
	//_dump($monParams);

		$resp = MonitisApi::editExternalPing( $monParams );
		if( $resp["status"] == 'ok') {
			echo '<div>Monitor '.$monitor['name'].' successfully updated</div>';
		} else {
			echo '<div>Unable to edit monitor, API request failed: '.  $resp['error'].'</div>';
		}
	}

	$oService = new monitisClientClass();
	$monitors = $oService->clientProductMonitors( $userid );

	if( $monitors) {
		echo "<section>";
		for( $i=0; $i<count($monitors); $i++) {
			$publicKey = $monitors[$i]['publickey'];
			$monitor_id = $monitors[$i]['monitor_id'];
			$monitor_type = $monitors[$i]['monitor_type'];

			echo '<header><h3>'.$monitors[$i]["productname"].'</h3></header><figure>';
			echo monitis_embed_module( $publicKey, 770, 350 );
			echo "</figure>";

			$sets = $monitors[$i]["settings"];
			$settings = json_decode( $sets, true);
			$locationsMax = $settings["locationsMax"];
			$item = $oService->externalMonitorInfo( $monitors[$i]['monitor_id'] );
			echo '<figure><form method="post" id="locationForm'.$monitor_id.'"><input type="button" value="Locations" class="monitisLocationTrigger" monitor_id="'.$monitor_id.'" locationsMax="'.$locationsMax.'"/>
			<input type="hidden" value="'.$item['locationIds'].'" class="locationIds'.$monitor_id.'" name="locationIDs" />
			<input type="hidden" value="'.$monitor_id.'" name="monitor_id" />
			<input type="hidden" value="'.$monitor_type.'" name="monitor_type" />
			<input type="hidden" value="locationEdit" name="action" />
			</form></figure>';
		}
		echo "</section>";
	} else {
		echo '<div>No monitors</div>';
	}
} else {
	echo '<div>Please, relogin!</div>';
}
{/php}
<div id="monitisMultiselectInputs"></div>
{literal}
<script>
$(document).ready(function() {
	$('.monitisLocationTrigger').click(function(event) {
		var prefix = $(this).attr("monitor_id");
		var locationsMax = $(this).attr("locationsMax");
		var loc_ids = $('.locationIds'+prefix).val();
		
		var opt = {
			parentId:"monitisMultiselectInputs",
			max_loc: locationsMax,
			loc_ids: loc_ids
		}
		new monitisLocationDialogClass( opt, function(resp){
			$('.locationIds'+prefix).val(resp);
			$('#locationForm'+prefix).submit();
		});
	});
});

</script>
{/literal}
<br />
<br />