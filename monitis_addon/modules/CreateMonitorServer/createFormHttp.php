<monitis_data>
<?php
$locations = MonitisApiHelper::getExternalLocationsGroupedByCountry();
foreach ($locations as $key => $value) {
	if (empty($value))
		unset($locations[$key]);
}
//_dump($locations);
$monitorID = monitisPostInt('module_CreateMonitorServer_monitorID');
$isEdit = $monitorID > 0;

if ($isEdit) {
	$monitor = MonitisApi::getExternalMonitorInfo($monitorID);
}
?>
<form action="" method="post">
	<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
		<tr>
			<td class="fieldlabel" width="30%">Monitor type</td>
			<td class="fieldarea">
				<?php if (!$isEdit) : ?>
				<select name="type" onchange="javascript: m_CreateMonitorServer.loadCreateForm(this.value);">
					<optgroup label="External monitors">
						<option value="ping">Ping</option>
						<option value="http" selected="selected">HTTP</option>
						<option value="https">HTTPS</option>
					</optgroup>
					<optgroup label="Internal monitors">
						<option value="cpu">CPU</option>
						<option value="memory">Memory</option>
					</optgroup>
				</select>
				<?php else : ?>
					<b><?php echo ucfirst($monitor['type']); ?></b>
					<input type="hidden" name="type" value="http" />
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Name</td>
			<td class="fieldarea">
				<input type="text" name="name" size="50" placeholder="Name of the monitor" value="<?php if ($isEdit) echo $monitor['name']; ?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Url</td>
			<td class="fieldarea">
				<input type="text" name="url" size="50" placeholder="URL for the test" value="<?php if ($isEdit) echo $monitor['url']; ?>" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Request type</td>
			<td class="fieldarea">
				<select name="detailedTestType" onchange="javascript:
						var r = $(this).parent().parent().next();
						if ($(this).val() == 2)
							r.fadeIn(300);
						else
							r.fadeOut(300);
				">
					<option value="1">GET</option>
					<option value="2">POST</option>
					<option value="3">PUT</option>
					<option value="4">DELETE</option>
				</select>
			</td>
		</tr>
		<tr class="dn">
			<td class="fieldlabel">Post Data</td>
			<td class="fieldarea">
				<input type="text" name="postData" size="50" placeholder="key=value&otherKey=anotherValue" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check for string</td>
			<td class="fieldarea">
				<input type="checkbox" name="contentMatchFlag" value="1" onchange="javascript:
						var r = $(this).next().next();
						if ($(this).attr('checked') == 'checked')
							r.fadeIn(300);
						else
							r.fadeOut(300);
				" /> check for string existence<br/>
				<input type="text" class="dn" name="contentMatchString" size="50" placeholder="String to check for existence" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check interval</td>
			<td class="fieldarea">
				<select name="interval">
					<option value="1">1</option>
					<option value="3">3</option>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
					<option value="30">30</option>
					<option value="40">40</option>
					<option value="60">60</option>
				</select> min.
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Test timeout in</td>
			<td class="fieldarea">
				<input type="text" name="timeout" size="20" value="10" /> seconds
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Check locations</td>
			<td class="fieldarea">
				<!--input type="text" name="locationIDs" size="20" value="10000" /-->
				<div class="monitisMultiselect">
					<span class="monitisMultiselectText"><u>{count}</u> locations selected</span>
					<input type="button" class="monitisMultiselectTrigger" value="Select" />
					<div class="monitisMultiselectInputs" inputName="locationIDs[]"></div>
					<div class="monitisMultiselectDialog">
						<table style="width: 100%;" cellpadding=10>
							<tr>
								<?php foreach ($locations as $countryName => $country) { ?>
								<td style="vertical-align: top;">
									<div style="font-weight: bold; color: #71a9d2;">
										<?php echo $countryName; ?>
									</div>
									<hr/>
									<?php foreach ($country as $location) { ?>
										<div>
											<input type="checkbox" name="locationIDs[]" value="<?php echo $location['id']; ?>">
											<?php echo $location['fullName']; ?>
										</div>
									<?php } ?>
								</td>
								<?php } ?>
							</tr>
						</table>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Tag</td>
			<td class="fieldarea">
				<input type="text" name="tag" size="50" placeholder="Tag of the monitor" />
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Uptime SLA</td>
			<td class="fieldarea">
				<input type="text" name="uptimeSLA" size="40" placeholder="Minimum allowed uptime" /> %
			</td>
		</tr>
		<tr>
			<td class="fieldlabel">Response SLA</td>
			<td class="fieldarea">
				<input type="text" name="responseSLA" size="40" placeholder="Maximum allowed response time" /> seconds
			</td>
		</tr>
		
		<tr>
			<td class="fieldlabel">Basic authentication:</td>
			<td class="fieldarea">
				<table cellspacing=0 cellspacing=0>
					<tr>
						<td>&nbsp;Username:</td>
						<td><input type="text" name="basicAuthUser" size="40" placeholder="Username" /></td>
					</tr>
					<tr>
						<td>&nbsp;Password:</td>
						<td><input type="password" name="basicAuthPass" size="40" placeholder="Password" /></td>
					</tr>
				</table>
			</td>
		</tr>
		
		<tr>
			<td class="fieldlabel"></td>
			<td class="fieldarea">
				<input type="button" value="<?php echo $isEdit ? 'Save' : 'Create' ?>" onclick="javascript: m_CreateMonitorServer.submitForm();">
			</td>
		</tr>
	</table>
	<input type="hidden" name="module_CreateMonitorServer_action" value="createSubmited" />
</form>
</monitis_data>