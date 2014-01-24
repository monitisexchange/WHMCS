<? $subtab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'hook'; ?>
<div id="clienttabs">
	<ul class="monitis_link_result">
		<li class="tab <?= $subtab == 'hook' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabreport&sub=hook">Error Log</a></li>
		<li class="tab <?= $subtab == 'synchronize' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabreport&sub=synchronize">Inventory Report</a></li>
<?if(MONITIS_LOGGER) {?>
		<li class="tab <?= $subtab == 'log' ? 'tabselected' :'' ?>"><a href="<?=MONITIS_APP_URL?>&monitis_page=tabreport&sub=log">Activity Log</a></li>
<?}?>
	</ul>
</div>
<div id="tab_content">
<?if($subtab == 'log' && MONITIS_LOGGER) {
	include_once "report/log.php";
} elseif($subtab == 'synchronize') {
	include_once "report/synchronize.php";
} else {
	include_once "report/hook.php";
}
?>
</div>