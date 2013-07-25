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

$oClient = new monitisclientClass();
$monitors = $oClient->clientMonitors( $userid );

if( $monitors ) {
	echo "<section class='monitis_monitors'>";
	for( $i=0; $i<count($monitors); $i++){
		$item = $monitors[$i];
		echo "<h3>".$item['name']."</h3><figure>";
		echo $oClient->embed_module( $item['publickey'] );
		$int = $item['internals'];
		if( isset($int) && count($int) > 0 ){
			for( $j=0; $j<count($int); $j++){
				echo $oClient->embed_module( $int[$i] );
			}
		}
		echo "</figure>";
	}
	echo "</section>";
}

{/php}

<br />
<br />