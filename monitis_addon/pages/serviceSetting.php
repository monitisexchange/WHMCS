<?
define('MONITIS_ORDER_BEHAVIOR_TITLE', '{
		"noaction":"No action",
		"active":"Create/Active",
		"pending":"Pending",
		"suspended":"Suspend",
		"terminated":"Terminate",
		"delete":"Delete",
		"unlink":"Unlink"
	}');
	
	
$default_order_behavior = json_decode(MONITIS_ORDER_BEHAVIOR, true);
$order_title = json_decode(MONITIS_ORDER_BEHAVIOR_TITLE, true);

//_dump( MonitisConf::$settings["order_behavior"] );

if( isset($_POST["save"])){

//_dump($_POST);   

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
//_dump( MonitisConf::$settings['order_behavior'] );
	MonitisConf::update_settings( $newsets_json );
}

$order_behavior=MonitisConf::$settings["order_behavior"];

?>
    <form action="<?=MONITIS_APP_URL?>&monitis_page=settings&sub=services" method="post" >  

	<table cellspacing="1" cellpadding="3" width="300px" style="margin:30px 100px;">
	<? foreach ($default_order_behavior as $key => $val) {  ?>
		<tr>
			<td class="fieldlabel" style="width:100px;text-align:right;"><?=ucfirst($key) ?>:</td>
			<td>
				<select style="min-width:150px" name=<?= $key ?>>
					<? 
					foreach ($default_order_behavior[$key] as $k => $v) {                                                           
					  $selected = ( ($order_behavior && $k == $order_behavior[$key]) || (!$order_behavior && $default_order_behavior[$key][$k] > 0 )) ? 'selected' : ''; 
					?>    
					<option value="<?=$k?>" <?= $selected ?> ><?=$order_title[$k]?></option>

					<? } ?>
				</select>
			</td>
		</tr>
	<? } ?>
		<tr><td colspan="2" style="text-align:center"><input type="submit" value="Save" name="save" class="btn btn-primary" /></td></tr>
		</table> 
        </br>
    
  </form>
