<script type="text/javascript" src="includes/jscript/statesdropdown.js"></script>


{include file="$template/pageheader.tpl" title="Network Status" desc=""}

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

{php}
require_once('modules/addons/monitis_addon/MonitisApp.php');
require_once('modules/addons/monitis_addon/lib/client.class.php');
$userid = $this->_tpl_vars['clientsdetails']['userid'];
//echo "************ userid = $userid";


if( isset($userid) && $userid > 0) {
		
	logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus userid = $userid");

	$oClient = new monitisClientClass();
	$monitors = $oClient->userNetworkStatus( $userid );

	logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus monitors = ". json_encode($monitors));
	
	if( $monitors && $monitors["status"] == 'ok') {
		echo '<section class="monitis_monitors">';
		$mons = $monitors["data"];
		for( $i=0; $i<count($mons); $i++){
			$item = $mons[$i];
			echo '<h3>'.$item["groupname"].' - '.$item["name"].'</h3><figure>';
			if( isset($item['external']) && count($item['external']) > 0 ) {
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus publickey = ".$item['external'][0]['publickey']);
				echo monitis_embed_module( $item['external'][0]['publickey'], 770, 350 );
			}

			$int = $item["internal"];
			if( isset($int) && count($int) > 0 ){
				for( $j=0; $j<count($int); $j++){
logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus publickey = ".$int[$j]['publickey'] );
					echo monitis_embed_module( $int[$j]['publickey'], 770, 350 );
				}
			}
			echo "</figure>";
		}
		echo "</section>";
	} else {
			echo '<div>'.$monitors["msg"].'</div>';
	}

} else {
	echo '<div>Please, relogin!</div>';
}
{/php}

<br />
<br />