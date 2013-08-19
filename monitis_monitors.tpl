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
{php}

require_once('modules/addons/monitis_addon/config.php');
require_once('modules/addons/monitis_addon/lib/functions.php');
$userid = $this->_tpl_vars['clientsdetails']['userid'];
//echo "************ userid = $userid";

$table = "mod_monitis_product_monitor";
$fields = "*";
$where = array("user_id"=>$userid);
$result = select_query($table,$fields,$where);
$count = mysql_num_rows($result);

echo "<section>";
while($data = mysql_fetch_array($result)) {
	$publicKey = $data['publickey'];
	echo monitis_embed_module( $publicKey, 770, 350 );

}
echo "</section>";

{/php}

<br />
<br />