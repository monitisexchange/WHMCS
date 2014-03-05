<?php
$isNewAcc = empty(MonitisConf::$apiKey);

if (monitisPostInt('monitisFormSubmitted')) {
	$apiKey = trim(monitisPost('apiKey'));
	$secretKey = trim(monitisPost('secretKey'));
	
	$timezone = monitisPostInt('monitisTimeZone');
	
	if (empty($apiKey))
		MonitisApp::addError('Please provide valid API Key');
	elseif (empty($secretKey))
		MonitisApp::addError('Please provide valid Secret Key');
	elseif (!MonitisApi::checkKeysValid($apiKey, $secretKey))
		MonitisApp::addError('Wrong API and/or Secret keys provided.');
	else {

		MonitisConf::update_config( array('apiKey' => $apiKey, 'secretKey' => $secretKey, 'timezone'=> $timezone) );

		if ($isNewAcc) {
			//header('location: ' . MONITIS_APP_URL . '&monitis_page=configure&isNewAcc=1');
			header('location: ' . MONITIS_APP_URL . '&monitis_page=tabadmin&sub=settings&isNewAcc=1');
		}
	}
} else {
	if ($isNewAcc)
		MonitisApp::addMessage('Please enter your Monitis account information below.');
}

?>
<?php MonitisApp::printNotifications(); ?>
<script type="text/javascript">
$(document).ready(function() {
	var d = new Date();
	var minutes = d.getTimezoneOffset(); // minutes
	var hours = -parseInt(minutes); // hours
	//var hours = parseInt(minutes); // hours
	$('.monitisTimeZone').val( hours );
});
</script>
<center>
	<form action="" method="post">
		<table class="form" width="100%" border=0 cellspacing=2 cellpadding=3>
			<tr>
				<td class="fieldlabel">API Key</td>
				<td class="fieldarea">
					<input type="text" name="apiKey" size="40" value="<?php echo monitisPost('apiKey', MonitisConf::$apiKey); ?>" />
				</td>
			</tr>
			<tr>
				<td class="fieldlabel">Secret Key</td>
				<td class="fieldarea">
					<input type="text" name="secretKey" size="40" value="<?php echo monitisPost('secretKey', MonitisConf::$secretKey); ?>" />
				</td>
			</tr>
			<tr>
				<td class="fieldlabel"></td>
				<td class="fieldarea1">
					<input type="submit" value="Save" class="btn monitis_link_button" />
					<input type="hidden" name="monitisFormSubmitted" value="1" />
					<input type="hidden" name="monitisTimeZone" class="monitisTimeZone" value="1" />
				</td>
			</tr>
		</table>
	</form>
</center>