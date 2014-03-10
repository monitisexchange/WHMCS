<script type="text/javascript">
$(document).ready(function(){
	$("#contentarea").find('h1').css({
		padding:'10px 15px 30px 15px',
		textAlign:'right',
		background:'url("../modules/addons/monitis_addon/static/img/logo-big.png") no-repeat left center'
	}).html('&nbsp;');
});
</script>
<div id="monitis_api_error"></div>
<div id="monitis_dialogs" style="display: none;"></div>
<div id="tabs">
	<ul class="monitis_link_result">
		<li class="tab  <?php if ($pageName == 'tabadmin') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabadmin">Admin</a>
		</li>
		<li class="tab  <?php if ($pageName == 'tabclient') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabclient">Clients</a>
		</li>
		<li class="tab  <?php if ($pageName == 'tabreport') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabreport">Reports</a>
		</li>
		<?php if(empty(MonitisConf::$apiKey) || empty(MonitisConf::$secretKey)) {?>
		<li class="tab  <?php if ($pageName == 'account') echo 'tabselected'; ?>">
			<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=account">Monitis Account</a>
		</li>
		<?php }?>
	</ul>
</div>
