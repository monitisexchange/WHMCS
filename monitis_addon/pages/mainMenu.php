<script type="text/javascript">
$("#contentarea").find('h1').css({
	paddingLeft:'140px',
	background:'url("../modules/addons/monitis_addon/static/img/logo-big.png") no-repeat left top'
}).html('&nbsp;');
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
		<li class="tab  <?php if ($pageName == 'configure') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=configure">Monitor Settings</a>
		</li>
        <li class="tab  <?php if ($pageName == 'notification') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=notification">Notifications</a>
		</li>
		<li class="tab  <?php if ($pageName == 'monitisAccount') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=monitisAccount">Monitis Account</a>
		</li>
	</ul>
</div>