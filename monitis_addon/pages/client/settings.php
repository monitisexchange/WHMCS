<?php
define('MONITIS_ORDER_BEHAVIOR_TITLE', '{
		"noaction":"No action",
		"active":"Create/Activate",
		"pending":"Pending",
		"suspended":"Suspend",
		"terminated":"Terminate",
		"delete":"Delete",
		"unlink":"Unlink"
	}');
$default_order_behavior = json_decode(MONITIS_ORDER_BEHAVIOR, true);
$order_title = json_decode(MONITIS_ORDER_BEHAVIOR_TITLE, true);

//_dump( MonitisConf::$settings["order_behavior"] );


define('MONITIS_CLIENTSTATUS_BEHAVIOR_TITLE', '{
		"noaction":"No action",
		"delete":"Delete"
	}');

$defaultStatusBehavior = json_decode(MONITIS_USER_STATUS_BEHAVIOR, true);
$clientStatusTitle = json_decode(MONITIS_CLIENTSTATUS_BEHAVIOR_TITLE, true);



if( isset($_POST["save_service"])){
	MonitisConf::$settings["order_behavior"] = array(
		'active' => $_POST['active'], 
		'pending' => $_POST['pending'],
		'suspended' => $_POST['suspended'], 
		'terminated' => $_POST['terminated'],
		'deleted' => $_POST['deleted'],
		'cancelled' => $_POST['cancelled'], 
		'fraud' => $_POST['fraud']
	);
	$newsets_json = json_encode(MonitisConf::$settings);
	MonitisConf::update_settings( $newsets_json );
} elseif( isset($_POST["save_client"])){
	MonitisConf::$settings["user_behavior"] = array(
		'closed' => $_POST['closed'], 
		'deleted' => $_POST['deleted']
	);
	$newsets_json = json_encode(MonitisConf::$settings);
	MonitisConf::update_settings( $newsets_json );
}

?>
<style type="text/css">
.monitis-setting {
	text-align: left;
	margin: 15px 0px;
}
.monitis-setting .title{
	color: #555555;
	font-size: 12px;
	font-weight: bold;
	padding: 10px 0px;
}
.monitis-setting table.form {
	width: 100%;
}
.monitis-setting table.form .fieldlabel{
	width: 30%;
}
</style>
<div class="monitis-setting">
    <form action="" method="post" >  
	<table class="form" cellspacing="1" cellpadding="3">
		<tr>
			<td class="fieldlabel title">Product/Addon status</td>
			<td class="fieldarea title">&nbsp;Monitis API action (Monitors)</td>
		</tr>
	<?php	$order_behavior=MonitisConf::$settings["order_behavior"];
		foreach ($default_order_behavior as $key => $val) {  ?>
		<tr>
			<td class="fieldlabel"><?php echo ucfirst($key) ?>:</td>
			<td class="fieldarea">
				<select style="min-width:150px" name=<?php echo $key; ?>>
					<?php 
					foreach ($default_order_behavior[$key] as $k => $v) {                                                           
					  $selected = ( ($order_behavior && $k == $order_behavior[$key]) || (!$order_behavior && $default_order_behavior[$key][$k] > 0 )) ? 'selected' : ''; 
					?>    
					<option value="<?php echo $k ?>" <?php echo $selected; ?> ><?php echo $order_title[$k] ?></option>

					<?php } ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr><td class="fieldlabel" >&nbsp;</td><td><input type="submit" value="Save" name="save_service" class="btn" /></td></tr>
		</table> 
  </form>
</div>

<?php
//MONITIS_APP_URL
//&monitis_page=tabclient&sub=settings

?>
<div class="monitis-setting">
    <form action="" method="post" >  
	<table class="form" cellspacing="1" cellpadding="3">
		<tr>
			<td class="fieldlabel title">Client status</td>
			<td class="fieldarea title">&nbsp;Monitis API action (Sub Accounts)</td>
		</tr>
	<?php 
		$userStatusBehavior=MonitisConf::$settings["user_behavior"];

		foreach ($defaultStatusBehavior as $key => $val) { ?>
		<tr>
			<td class="fieldlabel" ><?php echo ucfirst($key) ?>:</td>
			<td class="fieldarea">
				<select style="min-width:150px" name=<?php echo $key ?>>
					<?php 
					foreach ($defaultStatusBehavior[$key] as $k => $v) {                                                           
					  $selected = ( ($userStatusBehavior && $k == $userStatusBehavior[$key]) || (!$userStatusBehavior && $defaultStatusBehavior[$key][$k] > 0 )) ? 'selected' : ''; 
					?>    
						<option value="<?php echo $k ?>" <?php echo $selected ?> ><?php echo $clientStatusTitle[$k]?></option>

					<?php } ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr><td class="fieldlabel" >&nbsp;</td><td><input type="submit" value="Save" name="save_client" class="btn" /></td></tr>
		</table> 
   
  </form>
</div>
