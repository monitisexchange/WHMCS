<?php
$mainTab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'servers';
?>
<div id="clienttabs">
	<ul class="monitis_link_result">
		<li class="tab <?php echo $mainTab == 'servers' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabadmin&sub=servers">Servers</a></li>
		<li class="tab <?php echo $mainTab == 'snapshots' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabadmin&sub=snapshots">Snapshots</a></li>
		<li class="tab <?php echo $mainTab == 'alerts' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabadmin&sub=alerts">Alerts</a></li>
		<li class="tab <?php echo $mainTab == 'settings' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabadmin&sub=settings">Monitor Configuration</a></li>
		<li class="tab <?php echo $mainTab == 'account' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabadmin&sub=account">Monitis Account Credentials</a></li>
	</ul>
</div>
<div id="tab_content">
<?php if($mainTab == 'snapshots') {
	include_once "admin/snapshots.php";
} elseif($mainTab == 'alerts') {
	include_once "admin/alerts.php";
} elseif($mainTab == 'settings') {
	include_once "admin/configure.php";
} elseif($mainTab == 'account') {
	include_once "account.php";
} else {
	include_once "admin/servers.php";
}
?>
</div>


