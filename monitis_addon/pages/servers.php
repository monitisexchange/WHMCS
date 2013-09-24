<?php
require_once ('../modules/addons/monitis_addon/lib/serverslisttab.class.php');
$oSrvrs = new serversListTab();

if ( isset( $_POST['create_NewMonitors']) && $_POST['create_NewMonitors'] > 0 ) {
	if( isset( $_POST['serverId']) ) {
		$servers = array_map( "intval", $_POST['serverId'] );
		
		$srv_ids = $oSrvrs->_idsList( $servers, '' );
		$srv_ids_str = implode(",", $srv_ids);

		$oWhmcs = new WHMCS_class( MONITIS_CLIENT_ID );
		$srvs = $oWhmcs->servers_list($srv_ids_str);
		
		$ext = $oWhmcs->servers_list_ext($srv_ids_str);
		$int = $oWhmcs->servers_list_int($srv_ids_str);
		$whmcs = array( 'ext'=>$ext, 'int'=>$int );
		for($i=0; $i<count($srvs); $i++) {
			$resp = MonitisApiHelper::addAllDefault( MONITIS_CLIENT_ID, $srvs[$i], $whmcs );
			
//echo "******** ". $srvs[$i][name]." ****** ".json_encode($resp)."<br>";
		/*	if( !$oSrvrs->isMonitor( $srvs[$i]['id'], $ext) ) {
				$resp = MonitisApiHelper::addAllDefault( MONITIS_CLIENT_ID, $srvs[$i] );
			} else {
				MonitisApp::addWarning("Server {$srvs[$i][name]} has a monitor.");
			}*/
		}
	} else {
		MonitisApp::addWarning("The server is not selected.");
	}
	MonitisApp::printNotifications();
}



$limit = MONITIS_PAGE_LIMIT;
$sList = array(
	'name'=> isset( $_REQUEST['nameOrder'] ) ? $_REQUEST['nameOrder'] : 'ASC',
	'hostname'=> isset( $_REQUEST['hostnameOrder'] ) ? $_REQUEST['hostnameOrder'] : 'ASC',
	'ipaddress'=> isset( $_REQUEST['ipaddressOrder'] ) ? $_REQUEST['ipaddressOrder'] : 'ASC'
);
$sortname = isset( $_REQUEST['sortname'] ) ? $_REQUEST['sortname'] : 'name';
$sOrder = $sortname.'Order';
$sortorder = 'ASC';
if( isset( $_REQUEST[$sOrder] ) && !empty($_REQUEST[$sOrder]) ) {
	$sortorder = $_REQUEST[$sOrder];
}
$sList[$sortname] = $sortorder;


$page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
$start = ($page-1)*$limit;


$opts = array('start'=>$start,'limit'=>$limit,'sort'=>$sortname,'sortorder'=>$sortorder);
$srvrs = $oSrvrs->init($opts);
$total = $oSrvrs->getTotal();

//_dump( $srvrs );

$pages = intval($total/$limit);
if( $total % $limit )
	$pages++;

//////////////////////////////////////////////////////////////////////////////
?>
<form method="post" action="" id="serversListId">
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<td width="50%" align="left">
				<?php /*echo $createModuleContent;*/ ?>
			<!-- 	<input type="submit" value="Create new monitors" />
				<input type="hidden" name="createNewMonitors" value="1" /> -->
				<input type="submit" value="Create new monitors" />
				<input type="hidden" name="create_NewMonitors" value="1" />
			</td>
			<td width="50%" align="right">
			Jump to Page:&nbsp;&nbsp; 
				<select name="page" onchange="submit()">
				<?	for($i=1; $i<=$pages; $i++) {
						$selected = '';
						if($i == $page)
							$selected = 'selected="selected"';
						echo '<option value="'.($i).'" '.$selected.'>'.($i).'</option>';
					}
				?>
				</select>
				<input type="submit" value="Go">
			</td>
		</tr>
	</table>
<style>
#drivesListId a{
	cursor:pointer;
}
.drivesList {
	position:absolute;list-style:none;background-color:#fff;padding:0px;margin:10px 0px 0px 0px;top:5px;
	border:solid 1px #888888;z-index:2; 
}
.drivesList li {
	padding:2px 7px;
	margin:2px;
}
.drivesList div {
	font-size:10px;
}

</style>
<script>

function sortRequest(sortname) {
	var order = $('#'+sortname+'Order').val();
	if(order == 'ASC') 
		$('#'+sortname+'Order').val('DESC');
	else
		$('#'+sortname+'Order').val('ASC');
	$('#sortnameId').val(sortname);
	$('#serversListId [name="create_NewMonitors"]').val(0);
}

$('document').ready(function(){
	$('#drivesListId ul').hide();

	$("#drivesListId a").click(function(ev){
		$('#drivesListId ul').hide(500);
		$(this).next().fadeIn(1000);
		ev.stopPropagation();
	});
	$('#contentarea').click(function(ev){
		$('#drivesListId ul').hide(500);
		ev.stopPropagation();
	});
	//////////////////
});
</script>
<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" style="text-align: left;">
	<tr>
		<th width="20"><input type="checkbox" class="monitis_checkall" ></th>
		<th><a href="javascript:void(0)" onclick="sortRequest('name'); submit();">Server Name</a><input type="hidden" name="nameOrder" value="<?=$sList['name']?>" id="nameOrder" /></th>
		<th><a href="javascript:void(0)" onclick="sortRequest('ipaddress'); submit();">IP address</a><input type="hidden" name="ipaddressOrder" value="<?=$sList['ipaddress']?>" id="ipaddressOrder" /></th>
		<th><a href="javascript:void(0)" onclick="sortRequest('hostname'); submit();">Hostname</a><input type="hidden" name="hostnameOrder" value="<?=$sList['hostname']?>" id="hostnameOrder" /></th>
		<th><a style="text-decoration:none;">Current Status</a></th>
		<!-- th><a style="text-decoration:none;">Customer available</a></th -->
		<th><a style="text-decoration:none;">Monitis Monitors</a><input type="hidden" name="sortname" value="hostname" id="sortnameId" /></th>
	</tr>
<?php 
	//foreach ($servers as $server): 
	for($i=0; $i<count($srvrs); $i++) {
	
		$monitors = $srvrs[$i]['monitors'];
		$monitorsCount = count($monitors);
		$server_id = $srvrs[$i]['id'];
		
//$available = $srvrs[$i]['available'];
		$pings = -1;
		$cpu = -1;
		$memory = -1;
		$drive = -1;
		$disabled = '';
		if( $monitors && $monitorsCount > 0 ){
			//$disabled = 'disabled="disabled"';
			$agentStatus = '';
			if( isset($srvrs[$i]['agent_id']) )
				$agentStatus = $srvrs[$i]['agent_status'];
			if( $monitors['ping'] ) 	$pings = $monitors['ping'];
			if( $monitors['cpu'] ) 	$cpu = $monitors['cpu'];
			if( $monitors['memory'] && isset($monitors['memory']['id']) && $monitors['memory']['id'] > 0 ) {
				$memory = $monitors['memory'];
	
			}
			if( $monitors['drive'] ) {
				$drive = $monitors['drive'];
				$drive_status = 0;
				$noassociate = 0;
				for($d=0; $d<count( $drive ); $d++) {
					if( $drive[$d]['status'] == 'OK') $drive_status++;
					if( $drive[$d]['associate'] == 'yes') $noassociate++;
				}
			}
		}	
?>
	<tr>
		<td><input type="checkbox" class="monitis_checkall" value="<?=$server_id?>" name="serverId[]" <?=$disabled?> /></td>
		<td><?php echo $srvrs[$i]['name'] ?></td>
		<td><?php echo $srvrs[$i]['ipaddress'] ?></td>
		<td><?php echo $srvrs[$i]['hostname'] ?></td>
		<td style="text-align:left;">
<?php

		if ( $monitorsCount == 0) { 
				echo '<span class="label pending">No active monitors</span>';
		} else { 
			if( $pings != -1 && $pings && count( $pings ) > 0 ) {
				//$status = $pings['pin';
				$stl = '';
				//$title = '';
				if( $pings['associate'] == 'no' ) {
					$stl = 'pending';
				} elseif( $pings['status'] == 'suspended'  ) {
					$stl = '';		// pending // suspended
					//$title = 'suspended';
				} elseif( $pings['status'] == 'OK' ) {
					$stl = 'active';
					//$title = 'active';
				} else {
					$stl = 'closed';
					//$title = 'nok';
				}
				echo '&nbsp;<span class="label '.$stl.'" >Ping</span>';
			}		

			if( $agentStatus == 'running') {

				
				if( $cpu && count( $cpu ) > 0 ) {
					$stl = '';
					
					if( $cpu['associate'] == 'no' ) {
						$stl = 'pending';
					} elseif( $cpu['isSuspended'] > 0  ) {
						$stl = 'suspended';
					} elseif( $cpu['status'] == "OK" ) {
						$stl = 'active';
					} else {
						$stl = 'closed';
					}
					echo '&nbsp;<span class="label '.$stl.'">CPU</span>';
				}
				if( $memory != -1 && $memory && count( $memory ) > 0 ) {
					$stl = '';

					if( $memory['associate'] == 'no' ) {
						$stl = 'pending';
					} elseif( $memory['isSuspended'] > 0  ) {
						$stl = 'suspended';
					} elseif( $memory['status'] == "OK" ) {
						$stl = 'active';
					} else {
						$stl = 'closed';
					}
					echo '&nbsp;<span class="label '.$stl.'">memory</span>';
				}
				if( $drive != -1 && $drive && count( $drive ) > 0 ) {
					
					$stl = '';
					//$title = '';
					if( $noassociate == 0) {
						$stl = 'pending';
						//$title = 'no associate';
					} elseif( $drive_status > 0) {
						$stl = 'active';
						//$title = 'active';
					} else {
						$stl = 'closed';
						//$title = 'NOK';
					}
					
					echo '&nbsp;<lable class="label '.$stl.'" style="position:absolute;" id="drivesListId"><a class="label '.$stl.'">drive</a>';

					echo '<ul class="drivesList">';
					for($d=0; $d<count( $drive ); $d++) {
						if( !empty($drive[$d]['name']) ) {
							$stl = '';
							if( $drive[$d]['associate'] == 'no' )
								$stl = 'pending';
							elseif( $drive[$d]['status'] == 'OK' ) 
								$stl = 'active';
							elseif( $drive[$d]['status'] == 'NOK' ) 
								$stl = 'closed';
							echo '<li class="label '.$stl.'"><div>'.$drive[$d]['name'].'</div></li>';
						}
					}
					echo '</ul></lable>';

				}
			} elseif( !empty($agentStatus) ) {
				echo '&nbsp;<span class="label">agent stopped</span>';
			}
		}
?>
		</td>

		<td style="text-align: center;">
<? if($monitorsCount > 0) {?>
			<a href="<?=MONITIS_APP_URL?>&monitis_page=monitors&server_id=<?=$server_id?>">Monitors &#8594;</a>
<? } else {?>
			<a href="<?=MONITIS_APP_URL?>&monitis_page=monitors&server_id=<?=$server_id?>">Add monitors &#8594;</a>
<?}?>
		</td>
	</tr>
<? } ?>
</table>
</form>


