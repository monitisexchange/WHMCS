<?
$subtab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'monitors';

$subslist = array("monitors"=>0,"services"=>1,"account"=>2);

$subs = array('monitors','services','account');
$subtabnum = $subslist[$subtab];

?>
<style>


.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default { border: 1px solid #c5dbec; font-weight: bold; color: #2e6e9e; }
.ui-state-default a, .ui-state-default a:link, .ui-state-default a:visited { color: #2e6e9e; text-decoration: none; }
.ui-state-hover, .ui-widget-content .ui-state-hover, .ui-widget-header .ui-state-hover, .ui-state-focus, .ui-widget-content .ui-state-focus, .ui-widget-header .ui-state-focus { border: 1px solid #79b7e7; font-weight: bold; color: #1d5987; }
.ui-state-hover a, .ui-state-hover a:hover { color: #1d5987; text-decoration: none; }
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active { border: 1px solid #79b7e7; font-weight: bold; color: #e17009; }
.ui-state-active a, .ui-state-active a:link, .ui-state-active a:visited { color: #e17009; text-decoration: none; }

#subtabs ul{ border: none; background: none; }
#subtabs li { background: none; }
#subtabs li a{ font-weight: normal; }
</style>
<script>
$(document).ready(function(){

	$( "#subtabs" ).tabs(  );
	$( "#subtabs" ).tabs( "select" , <?=$subtabnum?>  );
});
</script> 


<br /><br />
<div id="subtabs">
    <ul class="ulsubtabs">
		<li><a href="#tab_monitors" name="monitors"> Monitor Settings</a></li>
		<li><a href="#tab_services" name="services">Product/Addon status behavior</a></li>
		<li><a href="#tab_account" name="account"> Monitis Account</a></li>
    </ul>        
    <div id="tab_monitors" class="tabcontent"> <? include_once "configure.php" ?> </div> 
    <div id="tab_services" class="tabcontent"><? include_once "serviceSetting.php" ?></div>
    <div id="tab_account" class="tabcontent"><? include_once "monitisAccount.php" ?> </div> 
</div>
