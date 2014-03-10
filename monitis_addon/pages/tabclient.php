<?php
$mainTab = ( isset($_GET['sub']) && !empty($_GET['sub']) ) ? $_GET['sub'] : 'products';
?>
<div id="clienttabs">
	<ul class="monitis_link_result">
		<li class="tab <?php echo $mainTab == 'products' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabclient&sub=products">Products/Services</a></li>
		<li class="tab <?php echo $mainTab == 'addons' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabclient&sub=addons">Addons</a></li>
		<li class="tab <?php echo $mainTab == 'options' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabclient&sub=options">Configurable Options</a></li>
		<li class="tab <?php echo $mainTab == 'settings' ? 'tabselected' :'' ?>"><a href="<?php echo MONITIS_APP_URL?>&monitis_page=tabclient&sub=settings">Settings</a></li>
	</ul>
</div>
<div id="tab_content">
<?php if($mainTab == 'addons') {
	include_once "client/addons.php";
} elseif($mainTab == 'options') {
	include_once "client/options.php";
} elseif($mainTab == 'settings') {
	include_once "client/settings.php";
} else {
	include_once "client/products.php";
}
?>
</div>