<?php
require_once ('../modules/addons/monitis_addon/lib/serverslisttab.class.php');
$oSrvrs = new serversListTab();

if (isset($_POST['create_NewMonitors']) && $_POST['create_NewMonitors'] > 0) {
	if (isset($_POST['serverId'])) {
		$servers = array_map("intval", $_POST['serverId']);
		$srv_ids = MonitisHelper::idsByField($servers, '');
		$srv_ids_str = implode(",", $srv_ids);

		$srvs = monitisWhmcsServer::serverByIds($srv_ids_str);
		$ext = monitisWhmcsServer::extMonitorsByServerIds($srv_ids_str);
		$int = monitisWhmcsServer::intMonitorsByServerIds($srv_ids_str);

		$whmcs = array('ext' => $ext, 'int' => $int);

		MonitisConf::$settings['ping']['autocreate'] = 1;
		MonitisConf::$settings['cpu']['autocreate'] = 1;
		MonitisConf::$settings['memory']['autocreate'] = 1;

		for ($i = 0; $i < count($srvs); $i++) {

			$resp = MonitisApiHelper::addAllDefault(MONITIS_CLIENT_ID, $srvs[$i], $whmcs);
			$ping = $resp['ping'];
			$msg = 'Server ' . $srvs[$i][name] . ' - PING monitor: ' . $ping['msg'];

			if ($ping['status'] == 'error') {
				MonitisApp::addError($msg);
			} elseif ($ping['status'] == 'warning') {
				//MonitisApp::addWarning($msg);
			}

			if ($resp['agent']['status'] == 'ok') {
				$internalMonitors = $resp['internal_monitors'];
				foreach ($internalMonitors as $key => $val) {
					$msg = 'Server ' . $srvs[$i][name] . ' - ' . strtoupper($key) . ' monitor: ' . $internalMonitors[$key]['msg'];

					if ($internalMonitors[$key]['status'] == 'error') {
						MonitisApp::addError($msg);
					} elseif ($internalMonitors[$key]['status'] == 'warning') {
						//MonitisApp::addWarning($msg);
					}
				}
			} else {
				//MonitisApp::addWarning('Server '.$srvs[$i][name]. ' - ' .$resp['agent']['msg']);
			}
		}
	} else {
		//MonitisApp::addWarning("The server is not selected.");
	}
	MonitisApp::printNotifications();
}

$limit = MONITIS_PAGE_LIMIT;
$sList = array(
	'name' => isset($_REQUEST['nameOrder']) ? $_REQUEST['nameOrder'] : 'ASC',
	'hostname' => isset($_REQUEST['hostnameOrder']) ? $_REQUEST['hostnameOrder'] : 'ASC',
	'ipaddress' => isset($_REQUEST['ipaddressOrder']) ? $_REQUEST['ipaddressOrder'] : 'ASC'
);
$sortname = isset($_REQUEST['sortname']) ? $_REQUEST['sortname'] : 'name';
$sOrder = $sortname . 'Order';
$sortorder = 'ASC';
if (isset($_REQUEST[$sOrder]) && !empty($_REQUEST[$sOrder])) {
	$sortorder = $_REQUEST[$sOrder];
}
$sList[$sortname] = $sortorder;


$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
$start = ($page - 1) * $limit;


$opts = array(
	'synchronize' => true,
	'start' => $start,
	'limit' => $limit,
	'sort' => $sortname,
	'sortorder' => $sortorder
);
$srvrs = $oSrvrs->init($opts);
$total = $oSrvrs->getTotal();

$pages = intval($total / $limit);
if ($total % $limit)
	$pages++;

?>
<div class="light_menu">
	<a href="<?php echo MONITIS_APP_URL?>&monitis_page=refresh" class="monitis_link_result">Refresh monitors</a> (recommended to use after adding or removing monitors on Monitis dashboard)
</div>
<form method="post" action="" id="serversListId">
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<td width="50%" align="left">
				<b><?php echo count($srvrs) ?></b> Servers Found, Page <b><?php echo $page ?></b> of <b><?php echo $pages ?></b>
			</td>
			<td width="50%" align="right">
				Jump to Page:&nbsp;&nbsp; 
				<select name="page" onchange="this.form.create_NewMonitors.value = 0;
						submit()">
					<?php
					for ($i = 1; $i <= $pages; $i++) {
						$selected = '';
						if ($i == $page)
							$selected = 'selected="selected"';
						echo '<option value="' . ($i) . '" ' . $selected . '>' . ($i) . '</option>';
					}
					?>
				</select>
				<input type="submit" value="Go" onclick="this.form.create_NewMonitors.value=0;" class="monitis_link_button" />
			</td>
		</tr>
	</table>
	<style type="text/css">
		#drivesListId a{
			cursor:pointer;
		}
		.drivesList {
			position:absolute;list-style:none;background-color:#fff;padding:0px;margin:10px 0px 0px 0px;top:5px;
			border:solid 1px #888888;
			z-index:2; 
		}
		.drivesList li {
			padding:2px 7px;
			margin:2px;
		}
		.drivesList div {
			font-size:10px;
		}
		.label.monitis-expire{
			padding-left: 12px;
			background-image: url('../modules/addons/monitis_addon/static/img/warning.png');
			background-repeat: no-repeat;
			background-position: 1px 5px;
		}
		.light_menu {
			text-align:left;
			padding:10px 0px;
		}
	</style>
	<script type="text/javascript">
	function sortRequest(sortname) {
		var order = $('#' + sortname + 'Order').val();
		if (order == 'ASC')
			$('#' + sortname + 'Order').val('DESC');
		else
			$('#' + sortname + 'Order').val('ASC');
		$('#sortnameId').val(sortname);
		$('#serversListId [name="create_NewMonitors"]').val(0);
	}

	$(document).ready(function() {

		$('#drivesListId ul').hide();
		$("#drivesListId a").click(function(ev) {
			$('#drivesListId ul').hide(500);
			$(this).next().fadeIn(1000);
			ev.stopPropagation();
		});
		$('#contentarea').click(function(ev) {
			$('#drivesListId ul').hide(500);
			ev.stopPropagation();
		});
	});
	</script>
	<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">

		<tr>
			<th width="20"><input type="checkbox" class="monitis-checkbox-all" ></th>
			<th><a href="javascript:void(0)" onclick="sortRequest('name');
						submit();">Server Name</a><input type="hidden" name="nameOrder" value="<?php echo $sList['name'] ?>" id="nameOrder" /></th>
			<th><a href="javascript:void(0)" onclick="sortRequest('ipaddress');
						submit();">IP address</a><input type="hidden" name="ipaddressOrder" value="<?php echo $sList['ipaddress'] ?>" id="ipaddressOrder" /></th>
			<th><a href="javascript:void(0)" onclick="sortRequest('hostname');
						submit();">Hostname</a><input type="hidden" name="hostnameOrder" value="<?php echo $sList['hostname'] ?>" id="hostnameOrder" /></th>
			<th><a style="text-decoration:none;">Current Status</a></th>
			<th><a style="text-decoration:none;">Monitis Monitors</a><input type="hidden" name="sortname" value="name" id="sortnameId" /></th>
		</tr>

		<tbody>
			<?php
		$expire = '';
		$maxTime = 3600; // min
			if (count($srvrs)) {
				for ($i = 0; $i < count($srvrs); $i++) {

					$monitors = $srvrs[$i]['monitors'];
					$monitorsCount = count($monitors);
					$server_id = $srvrs[$i]['id'];

					$pings = -1;
					$cpu = -1;
					$memory = -1;
					$drive = -1;
					$disabled = '';

					if ($monitors && $monitorsCount > 0) {
						$agentStatus = '';
						if (isset($srvrs[$i]['agent_id']))
							$agentStatus = $srvrs[$i]['agent_status'];

						if ($monitors['ping'])
							$pings = $monitors['ping'];
						if ($monitors['cpu'])
							$cpu = $monitors['cpu'];
						if ($monitors['memory'] && isset($monitors['memory']['id']) && $monitors['memory']['id'] > 0) {
							$memory = $monitors['memory'];
						}
						if ($monitors['drive']) {
							$drive = $monitors['drive'];
							$drive_status = 0;
							$noassociate = 0;
							for ($d = 0; $d < count($drive); $d++) {
								if ($drive[$d]['status'] == 'NOK')
									$drive_nok_status++;
								if ($drive[$d]['associate'] == 'yes')
									$noassociate++;
							}
						}
					}
					?>
					<tr>
						<td><input type="checkbox" class="monitis-checkbox" value="<?php echo $server_id ?>" name="serverId[]" <?php echo $disabled ?> /></td>
						<td><?php echo $srvrs[$i]['name'] ?></td>
						<td><?php echo $srvrs[$i]['ipaddress'] ?></td>
						<td><?php echo $srvrs[$i]['hostname'] ?></td>
						<td style="text-align:left;">
							<?php
							if ($monitorsCount == 0) {
								echo '<span class="label pending">No active monitors</span>';
							} else {
								if ($pings != -1 && $pings && count($pings) > 0) {
									$stl = '';
									if ($pings['associate'] == 'no') {
										$stl = 'pending';
									} elseif ($pings['status'] == 'suspended') {
										$stl = '';  // pending // suspended
									} elseif ($pings['status'] == 'OK') {
										$stl = 'active';
									} else {
										$stl = 'closed';
									}
									echo '<span class="label ' . $stl . '" >Ping</span>&nbsp;';
								}

								if ($agentStatus == 'running') {

									if ($cpu != -1 && $cpu && count($cpu) > 0) {
										$expire = '';
										if($cpu['diff'] > $maxTime)
											$expire = 'monitis-expire';
										
										$stl = '';
										if ($cpu['associate'] == 'no') {
											$stl = 'pending';
										} elseif ($cpu['isSuspended'] > 0) {
											$stl = 'suspended';
										} elseif ($cpu['status'] == "OK") {
											$stl = 'active';
										} else {
											$stl = 'closed';
										}
										echo '<span class="label '.$expire.' ' . $stl . '">CPU</span>&nbsp;';
									}
									if ($memory != -1 && $memory && count($memory) > 0) {
										$expire = '';
										if($memory['diff'] > $maxTime)
											$expire = 'monitis-expire';
											
										$stl = '';
										if ($memory['associate'] == 'no') {
											$stl = 'pending';
										} elseif ($memory['isSuspended'] > 0) {
											$stl = 'suspended';
										} elseif ($memory['status'] == "OK") {
											$stl = 'active';
										} else {
											$stl = 'closed';
										}
										echo '<span class="label '.$expire.' ' . $stl . '">memory</span>&nbsp;';
									}
									if ($drive != -1 && $drive && count($drive) > 0) {

										$stl = '';
										if ($noassociate == 0) {
											$stl = 'pending';
										} elseif ($drive_nok_status > 0) {
											$stl = 'closed';
										} else {
											$stl = 'closed';
										}

										echo '<lable class="label '.$stl.'" style="position:absolute;" id="drivesListId"><a class="label ' . $stl . '">drive</a>';

										echo '<ul class="drivesList">';
										for ($d = 0; $d < count($drive); $d++) {
											if (!empty($drive[$d]['name'])) {
											
												$expire = '';
												if($drive[$d]['diff'] > $maxTime)
													$expire = 'monitis-expire';
											
												$stl = '';
												if ($drive[$d]['associate'] == 'no')
													$stl = 'pending';
												elseif ($drive[$d]['status'] == 'OK')
													$stl = 'active';
												elseif ($drive[$d]['status'] == 'NOK')
													$stl = 'closed';
												echo '<li class="label '.$expire.' '.$stl.'"><div>' . $drive[$d]['name'] . '</div></li>';
											}
										}
										echo '</ul></lable>&nbsp;';
									}

									if ($cpu == -1 && $memory == -1 && $drive == -1 && $pings == -1) {
										//echo '<span class="label">agent - no monitors</span>&nbsp;';
										echo '<span class="label pending">No active monitors</span>';
									}
								} elseif (!empty($agentStatus)) {
									echo '<span class="label monitis-warning">agent stopped</span>&nbsp;';
								}
							}
							?>
						</td>

						<td style="text-align: center;" class="monitis_link_result">
							<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=monitors&server_id=<?php echo $server_id?>">Monitors &#8594;</a>
						</td>
					</tr>
				<?php
				}
			} else {
				?>
				<tr>
					<td colspan="6">No servers available.</td>
				</tr>
				<?php
			}
			?>
        </tbody>
	</table>

	<div class="monitis-page-bottom">
		<div>
			<label>With Selected:</label> <input type="submit" value="Create monitors" class="btn monitis-checkbox-subject monitis_link_button create_monitor" disabled="disabled" />
			<input type="hidden" name="create_NewMonitors" value="1" />
		</div>
	</div>
</form>