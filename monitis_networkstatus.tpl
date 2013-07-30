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
require_once('modules/addons/monitis_addon/lib/whmcs.class.php');
require_once('modules/addons/monitis_addon/lib/client.class.php');

$userid = $this->_tpl_vars['clientsdetails']['userid'];
//echo "************ userid = $userid";


if( isset($userid) && $userid > 0) {

	$whmcs = new WHMCS_class();
	$adm = $whmcs->getAdminName( 'monitis_addon', 'adminuser');

	$adminuser = $adm['value'];
		
	logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus userid = $userid");

	$oClient = new monitisclientClass();
	$monitors = $oClient->clientMonitors( $userid, $adminuser );

	logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus monitors = ". json_encode($monitors));

	if( $monitors && $monitors["status"] == 'ok') {
		echo '<section class="monitis_monitors">';
		$mons = $monitors["data"];
		for( $i=0; $i<count($mons); $i++){
			$item = $mons[$i];
			echo '<h3>'.$item["name"].'</h3><figure>';
	logActivity("MONITIS CLIENT LOG ***** monitis_networkstatus publickey = ".$item["publickey"]);
			echo $oClient->embed_module( $item["publickey"] );
			$int = $item["internals"];
			if( isset($int) && count($int) > 0 ){
				for( $j=0; $j<count($int); $j++){
					echo $oClient->embed_module( $int[$j] );
				}
			}
			echo "</figure>";
		}
		echo "</section>";
	} else {
		echo '<div>'.$monitors["msg"].'</div>';
	}
}
{/php}

<br />
<br />