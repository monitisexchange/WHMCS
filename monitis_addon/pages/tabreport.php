<?php $subtab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'hook'; ?>

<div id="clienttabs">
	<ul class="monitis_link_result">
		<li class="tab <?php echo $subtab == 'hook' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabreport&sub=hook">Error Log</a></li>
		<li class="tab <?php echo $subtab == 'synchronize' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabreport&sub=synchronize">Inventory Report</a></li>
		<li class="tab <?php echo $subtab == 'billing' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabreport&sub=billing">Plan Details</a></li>
<?php if(MONITIS_LOGGER) {?>
		<li class="tab <?php echo $subtab == 'log' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabreport&sub=log">Activity Log</a></li>
<?php }?>
	</ul>
</div>
<div id="tab_content">
<?php if($subtab == 'log' && MONITIS_LOGGER) {
	include_once "report/log.php";
} elseif($subtab == 'synchronize') {
	include_once "report/synchronize.php";
}elseif($subtab == 'billing'){
       include_once "report/billing.php";
}else {
	include_once "report/hook.php";
}
?>
</div>