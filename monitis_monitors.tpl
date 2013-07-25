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
//require_once('modules/addons/monitis_addon/lib/whmcs.class.php');
//require_once('modules/addons/monitis_addon/lib/product.class.php');
//require_once('modules/addons/monitis_addon/lib/services.class.php');
$userid = $this->_tpl_vars['clientsdetails']['userid'];
//echo "************ userid = $userid";

$table = "mod_monitis_product_monitor";
$fields = "*";
$where = array("user_id"=>$userid);
$result = select_query($table,$fields,$where);
$count = mysql_num_rows($result);

echo "<section>";
while($data = mysql_fetch_array($result)) {
//echo " ***$count *** <pre>";
//var_dump($data);
//echo "</pre>";
			$publicKey = $data['publickey'];
	echo '<script type="text/javascript">
	monitis_embed_module_id="'.$publicKey.'";
	monitis_embed_module_width="500";
	monitis_embed_module_height="350";
	monitis_embed_module_readonlyChart ="false";
	monitis_embed_module_readonlyDateRange="false";
	monitis_embed_module_datePeriod="0";
	monitis_embed_module_view="1";
	</script>
	<script type="text/javascript" src="https://api.monitis.com/sharedModule/shareModule.js"></script>
	<noscript><a href="http://monitis.com">Monitoring by Monitis. Please enable JavaScript to see the report!</a> </noscript>';

}
echo "</section>";

{/php}

<br />
<br />