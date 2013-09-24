<style>
#tabs li a{
	line-height: 22px;
	font-size:13px;
	padding: 0 10px;
	font-family:Tahoma, Arial, Helvetica, sans-serif;
	
}
</style>
<script type="text/javascript">
$(document).ready(function(){
	$("#contentarea").find('h1').css({
		padding:'10px 15px 30px 15px',
		textAlign:'right',
		background:'url("../modules/addons/monitis_addon/static/img/logo-big.png") no-repeat left center'
	}).html('&nbsp;');

});
//}).html('<div class="contexthelp"><a href="../modules/addons/monitis_addon/help/?page=<?=$pageName?>" target="_blank"><img src="images/icons/help.png" border="0" align="<?=$pageName?> help"> Help</a></div>');

// }).html('<a href="../modules/addons/monitis_addon/help/?act=<?=$pageName?>" target="_blank"><img src="../images/help.gif" border="0" align="<?=$monitis_page?> help"></a>');
//$("#contentarea").find('h1').first().append('<span style="color:red; font-size:12px; font-weight: bold;"> Alpha</span>');
</script>
<div id="tabs">
	<ul>
		<li class="tab <?php if ($pageName == 'servers') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=servers">Servers</a>
		</li>
		<li class="tab <?php if ($pageName == 'monitorList') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=monitorList">Monitors</a>
		</li>
		<li class="tab  <?php if ($pageName == 'products') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=products">Products</a>
		</li>
		<li class="tab  <?php if ($pageName == 'addons') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=addons">Addons</a>
		</li>
 		<li class="tab  <?php if ($pageName == 'options') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=options">Configurable options</a>
		</li>
		<li class="tab  <?php if ($pageName == 'settings') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=settings">Settings</a>
		</li>
<!--
        	<li class="tab  <?php if ($pageName == 'notification') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=notification">Notifications</a>
		</li>
        	<li class="tab  <?php if ($pageName == 'testtab') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=testtab">for test</a>
		</li>
		<li class="tab  <?php if ($pageName == 'db_tables') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=db_tables">DB Tables</a>
		</li>
              <li class="tab  <?php if ($pageName == 'stest') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=stest">Stest</a>
		</li>
		<li class="tab  <?php if ($pageName == 'mml') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=mml">mml</a>
		</li>
-->

<?if(empty(MonitisConf::$apiKey) || empty(MonitisConf::$secretKey)) {?>
		<li class="tab  <?php if ($pageName == 'monitisAccount') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=monitisAccount">Monitis Account</a>
		</li>
<?}?>
	</ul>
</div>
