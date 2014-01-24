<?php

//
if( isset($_POST) && isset($_POST['type']) && !empty($_POST['type']) ) {
	$action = $_POST['type'];

//_dump($_POST);

	$monitorID = monitisPostInt('monitor_id');
	$monitorType = monitisPost('monitor_type');
	$values = monitisPostInt('values');
	
	switch( $action ) {
		case 'suspend':
			$resp = MonitisApi::suspendExternal("$monitorID");
		break;
		case 'unsuspend':
			$resp = MonitisApi::activateExternal("$monitorID");
		break;
		
		case 'available':
		case 'noavailable':
			$val = 0;
			if($action == 'available')
				$val = 1;
			$table = 'mod_monitis_int_monitors';
			if( $monitorType == 'ping')
				$table = 'mod_monitis_ext_monitors';
			$update['available'] = $val;
			$where = array('monitor_id' => $monitorID);
			update_query($table, $update, $where);
			//update_query('mod_monitis_product_monitor', $update, $where);	
		break;
		case 'delete':
			$mtype = '';
			$resp = null;
			switch( $monitorType) {
				case 'ping':
					$resp = MonitisApi::deleteExternal($monitorID);
					if($resp['status'] == 'ok') {
						MonitisApp::addMessage('Uptime monitor successfully removed');
					} else {
						MonitisApp::addError($resp['error']);
					}
					// delete from tables
					monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_EXTERNAL_TABLE.' WHERE monitor_id='.$monitorID);
				break;
				case 'cpu':
				case 'memory':
				case 'drive':
					$mtype = 2;
					if( $monitorType == 'memory') $mtype = 3;
					if( $monitorType == 'cpu') $mtype = 7;

					$resp = MonitisApi::deleteInternal($monitorID, $mtype);
					if($resp['status'] == 'ok') {
						MonitisApp::addMessage('Server/Device monitor successfully removed');
					} else {
						MonitisApp::addError($resp['error']);
					}
					// delete from tables 
					monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id='.$monitorID);
				break;
			}
		break;
	}
}


$editMode = 'create';
$serverID = monitisGetInt('server_id');
if ($serverID == 0) {
	MonitisApp::redirect(MONITIS_APP_URL . '&monitis_page=servers');
}

	
//////////////////////////////////////
$isAgent = 0;

$serverObj = monitisWhmcsServer::serverInfo($serverID);
$serverName = $serverObj['name'];


$hostname = $serverObj['hostname'];
$oInt = new internalClass(); 
//$agentInfo = $oInt->getAgentInfo( $hostname );
$agentInfo = $oInt->getAgent( $hostname );


$drives = null;
if( $agentInfo ) {
	$agentKey = $agentInfo['agentKey'];
	$agentId = $agentInfo['agentId'];
	$isAgent = 1;
	$drives = $agentInfo['drives'];
} else {
	$isAgent = 0;
}
	
$createModule = MonitisApp::getModule('createmonitorserver');
$createModule->linkText = "Manage monitors";
$createModule->serverID = $serverID;
$createModule->serverName = $serverName;
$createModule->editMode = 'create';

$createModule->isAgent = $isAgent;
$createModule->agentId = $agentId;
$createModule->agentKey = $agentKey;
$createModule->agentStatus = $agentInfo['status'];

$createModuleContent = $createModule->execute();

$ext_monitors = monitisWhmcsServer::extMonitorsByServerId($serverID);
$int_monitors = monitisWhmcsServer::intMonitorsByServerId($serverID);

$agentInfo = $oInt->getAgentInfo( $hostname );
$drives = null;
if( $agentInfo ) {
	$drives = $agentInfo['drives'];
}

$arentName = '';
if( $int_monitors ) {
	$arentName = $agentInfo['agentKey'];
}

$int_monitors = $oInt->filterInternalMonitors( $agentInfo, $int_monitors, $serverID);

m_log( $int_monitors, 'agentInfo', 'agentInfo');

MonitisApp::printNotifications();
?>
<style type="text/css">
.sectionblock {
	text-align: center;
}
.sectionblock .monitor {
	padding: 20px;
	margin: 10px;
	text-align: center;
	border-top:solid 1px #cccccc;
}
.sectionblock .monitor .btn{
	font-size:11px;
	line-height:14px;
	padding: 3px 7px;
}
.sectionblock .tools {
	border:solid 1px #cccccc;
	width: 750px;
	text-align: left;
	padding: 7px 7px;
	margin: 0px auto;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

.snapshot_title {
	width:200px;
	text-align:left;
	padding:3px 10px;
}
</style>

<div align="left">
	<?php echo $createModuleContent; ?>
</div>

<div style="text-align:right;" class="monitis_link_result">
	<a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabadmin&sub=servers">&#8592; Back to servers list</a>
</div>
<section class="sectionblock">
<div class="snapshot_title"><?php if($serverName!='') echo "Server: <b>$serverName</b>"; ?></div>
<div class="snapshot_title"><?php if($arentName!='') echo "Agent status is <b>".$agentInfo['status']."</b>" ?></div>
<?
$singletype = 1;



	

		//$pings = monitisWhmcsServer::externalSnapShotsStatus( $ext_monitors );
	$ping = monitisSnapShots::externalSnapShots( $ext_monitors, $serverObj );

	if( $ping ) {	
		
		//$ping = null;
		//if( $pings && count($pings) > 0 ) 
		//	$ping = $pings[0]['ping'];
			
//m_log( $ping, 'ping', 'server');

		//for($i=0; $i<count($ext_monitors); $i++) {
                 
			//$item = $ext_monitors;
			$monitor_id = $ping['monitor_id'];
			$monitor_type = $ping['monitor_type'];
			$publickey = $ping['publickey'];
   
			if($publickey) { ?>
				<figure class="monitor"> 
				
				<? echo monitis_embed_module( $publickey, 800, 350); ?>
				
					<form action="" method="post" id="form<?=$monitor_type?>_<?=$monitor_id?>">
						<div class="tools">
							
							<? if( $ping['isSuspended']) { ?>
								<input type="submit" value="Activate" onclick="this.form.type.value='unsuspend'" class="btn btn-success"  />
							<? } else { ?>
								<input type="button" value="Edit" onclick="m_CreateMonitorServer.trigger(<?=$monitor_id?>, '<?=$monitor_type?>', '1');" class="btn" />
								<input type="submit" value="Suspend" onclick="this.form.type.value='suspend'" class="btn btn-suspended"  />
							<? }
							if( $ping['available'] > 0 ) { ?>
								<input type="submit" value="No available to clients" onclick="this.form.type.value='noavailable'" class="btn btn-suspended"  />
							<? } else { ?>
								<input type="submit" value="Available to clients" onclick="this.form.type.value='available'" class="btn btn-success"  />
							<? } ?>
							
							<input name="delete_action" type="button" value="Delete" class="btn btn-danger" monitor_id="<?=$monitor_id?>" monitor_type="<?=$monitor_type?>" />
					
							<input type="hidden" name="type" value="" />
							<input type="hidden" name="monitor_id" value="<?=$monitor_id?>" />
							<input type="hidden" name="monitor_type" value="<?=$monitor_type?>" />
							<input type="hidden" name="values" value="" />
						</div>
					</form>
				</figure>
<?
			}
/*			
			else {
				
				monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_EXTERNAL_TABLE.' WHERE monitor_id='.$monitor_id);
				//MonitisHelper::lostMonitors($monitor_id, MONITIS_EXTERNAL_TABLE);

				//MonitisApp::addError($monitor_type.' monitor does not exist and it removed');
			}
*/
		//}
	}
	
	
	if( $int_monitors ) {
		for($i=0; $i<count($int_monitors); $i++) {

			$item = $int_monitors[$i];
			$monitor_id = $item['monitor_id'];
			$monitor_type = $item['monitor_type'];
			$publickey = $item['publickey'];
			
//echo "********************** $publickey <br>";
			
			if( $int_monitors[$i] && $publickey) { 
				$letterIndex = -1;
				if($drives && $monitor_type == 'drive') {
					$letterIndex = $oInt->driveById($drives, $monitor_id);
				}
			
			?>
			

			<figure class="monitor">
				<? echo monitis_embed_module( $publickey, 800, 350 ); ?>
				
				<form action="" method="post" id="form<?=$monitor_type?>_<?=$monitor_id?>">
					<div class="tools">
						<? if($letterIndex > -1) {?>
							<input type="button" value="Edit" onclick="m_CreateMonitorServer.trigger(<?=$monitor_id?>, '<?=$monitor_type?>', '1', '<?=$letterIndex?>');" class="btn" />
						<? } else { ?>
							<input type="button" value="Edit" onclick="m_CreateMonitorServer.trigger(<?=$monitor_id?>, '<?=$monitor_type?>', '1' );" class="btn" />
						<? } ?>

						<? if( $item['available'] > 0 ) { ?>
							<input type="submit" value="No available to clients" onclick="this.form.type.value='noavailable'" class="btn btn-suspended" />
						<? } else { ?>
							<input type="submit" value="Available to clients" onclick="this.form.type.value='available'" class="btn btn-success" />
						<? } ?>
				
						<input name="delete_action" type="button" value="Delete" class="btn btn-danger" monitor_id="<?=$monitor_id?>" monitor_type="<?=$monitor_type?>" />

						<input type="hidden" name="type" value="" />
						<input type="hidden" name="monitor_id" value="<?=$monitor_id?>" />
						<input type="hidden" name="monitor_type" value="<?=$monitor_type?>" />
						<input type="hidden" name="values" value="" />
					</div>	
				</form>
				
			</figure>
<?
			} else {
				//monitisSqlHelper::altQuery('DELETE FROM '.MONITIS_INTERNAL_TABLE.' WHERE monitor_id='.$monitor_id);
				//MonitisHelper::lostMonitors($monitor_id, MONITIS_INTERNAL_TABLE);
				//MonitisApp::addError($monitor_type.' monitor does not exist and it removed');
			}
			
		}
	}
?>
<div id="dialogBoxId"></div>
</section>
<script type="text/javascript">
$(document).ready(function(){

	var form = $(".sectionblock").find("form");
	$(form).find('input[name="delete_action"]').click(function(event){
		var monitor_id = $(this).attr('monitor_id');
		var monitor_type = $(this).attr('monitor_type');
		
		var str = '<div id="messageId" title="Message box"><p align="center" style="padding:20px">Are you sure you want to remove the <b>'+monitor_type.toUpperCase()+'</b> monitor?</p></div>';
		$('#dialogBoxId').html(str);
		
		var dialog = $('#messageId').dialog({
			width: 400,
			autoOpen: false,
			modal: true,
			buttons: {
				'yes': {
					text: 'Yes', class: 'btn',
					click: function() {
						var form = $('#form'+monitor_type+'_'+monitor_id);
						$(form).find('input[name="type"]').val('delete');
						form.submit();
						$(this).dialog("close");
					}
				},
				'no': {
					text: 'No',class: 'btn',
					click: function() {
						$(this).remove();
					}
				}
			},
			close: function() {
				$(this).remove();
			}
		});
		dialog.dialog('open');	
		event.preventDefault();	
	});
});
</script>
