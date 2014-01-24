<?
$mainTab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'servers';
?>
<div id="clienttabs">
	<ul class="monitis_link_result">
		<li class="tab <?= $mainTab == 'servers' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabadmin&sub=servers">Servers</a></li>
		<li class="tab <?= $mainTab == 'snapshots' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabadmin&sub=snapshots">Snapshots</a></li>
		<li class="tab <?= $mainTab == 'alerts' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabadmin&sub=alerts">Alerts</a></li>
		<li class="tab <?= $mainTab == 'settings' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabadmin&sub=settings">Monitor Configuration</a></li>
		<li class="tab <?= $mainTab == 'account' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabadmin&sub=account">Monitis Account Credentials</a></li>
	</ul>
</div>
<div id="tab_content">
<?if($mainTab == 'snapshots') {
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


