<?php
$locations = MonitisConf::$locations;
$drives = array();

$serverId = $_GET['server_id'];
$serverMonitors = new serverMonitors();
$serverInfo = $serverMonitors->getServerInfo($serverId);
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    $monitorId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $monitorType = isset($_POST['type']) ? $_POST['type'] : '';
    switch($action) {
        case 'delete':
            switch($monitorType) {
                case 'ping':
                    $resp = MonitisApi::deleteExternal($monitorId);
                    if($resp['status'] == 'ok') {
                        MonitisApp::addMessage('Uptime monitor successfully removed');
                        $serverInfo['ping'] = NULL;
                    }
                    else {
                        MonitisApp::addError($resp['error']);
                    }
                    monitisSqlHelper::altQuery('DELETE FROM ' . MONITIS_EXTERNAL_TABLE . ' WHERE monitor_id=' . $monitorId);
                    break;
                case 'cpu':
                case 'memory':
                case 'drive':
                    $monitorTypeCodes = array(
                        'cpu' => 7,
                        'memory' => 3,
                        'drive' => 2
                    );
                    $resp = MonitisApi::deleteInternal($monitorId, $monitorTypeCodes[$monitorType]);
                    if($resp['status'] == 'ok') {
                            MonitisApp::addMessage('Server/Device monitor successfully removed');
                    }
                    else {
                            MonitisApp::addError($resp['error']);
                    }
                    monitisSqlHelper::altQuery('DELETE FROM ' . MONITIS_INTERNAL_TABLE . ' WHERE monitor_id=' . $monitorId);
                    if($monitorType == 'drive') {
                        for($i = 0; $i < count($serverInfo['agent']['drive']); $i++){
                            if(isset($serverInfo['agent']['drive'][$i]['id']) && $serverInfo['agent']['drive'][$i]['id'] === $monitorId){
                                $driveLetter = $serverInfo['agent']['drive'][$i]['letter'];
                                $serverInfo['agent']['drive'][$i] = array();
                                $serverInfo['agent']['drive'][$i]['driveLetter'] = $driveLetter;
                                break;
                            }
                        }
                    }
                    else {
                        $serverInfo['agent'][$monitorType] = NULL;
                    }
                    break;
            }
        break;
        case 'suspend':
            $resp = MonitisApi::suspendExternal($monitorId);
            $serverInfo['ping']['isSuspended'] = 1;
            break;
        case 'activate':
            $resp = MonitisApi::activateExternal($monitorId);
            $serverInfo['ping']['isSuspended'] = 0;
            break;
        case 'makeAvailable':
        case 'makeNotAvailable':
            $available = 0;
            if($action === 'makeAvailable'){
                $available = 1;
            }
            $table = 'mod_monitis_int_monitors';
            if($monitorType === 'ping'){
                $table = 'mod_monitis_ext_monitors';
            }
            $update['available'] = $available;
            $where = array('monitor_id' => $monitorId);
            update_query($table, $update, $where);
            
            if($monitorType === 'ping'){
                $serverInfo['ping']['available'] = $available;
            }
            elseif($monitorType === 'drive'){
                for($i = 0; $i < count($serverInfo['agent']['drive']); $i++){
                    if(isset($serverInfo['agent']['drive'][$i]['id']) && $serverInfo['agent']['drive'][$i]['id'] === $monitorId){
                        $serverInfo['agent']['drive'][$i]['available'] = $available;
                        break;
                    }
                }
            }
            else{
                $serverInfo['agent'][$monitorType]['available'] = $available;
            }
            break;
    }
}
elseif(isset($_POST['type'])) {
    switch($_POST['type']) {
        case 'ping':
            $interval = (int)$_POST['interval'];
            $locationIds = mysql_escape_string($_POST['locationIds']);
            $params = array(
                'type' => 'ping',
                'name' => $serverInfo['ipaddress'] . '_ping',
                'url' => $serverInfo['ipaddress'],
                'timeout' => (int) $_POST['timeout'],
                'tag' => $serverInfo['hostname'] . '_whmcs',
                'locationIds' => $_POST['locationIds']
            );
            if(isset($_POST['id'])) {
                $monitorId = (int) $_POST['id'];
                $params['testId'] = $monitorId;
                $params['locationIds'] = str_replace(',', '-' . $interval . ',', $params['locationIds']);
                $params['locationIds'].= '-' . $interval;
                
                $resp = MonitisApi::editExternalPing($params);
                if ($resp['status'] == 'ok' ) {
                    MonitisApp::addMessage('Ping Monitor successfully updated');
                    $serverInfo['ping'] = $serverMonitors->getMonitor($monitorId, 'ping');
                }
                else {
                    MonitisApp::addError($resp['error']);	
                }
            }
            else{
                $params['interval'] = $interval;
                
                $resp = MonitisApi::createExternalPing($params);
                if(isset($resp['data']) && isset($resp['data']['testId'])) {
                    $newId = $resp['data']['testId'];
                    $publicKey = MonitisApi::monitorPublicKey(array('moduleType' => 'external', 'monitorId' => $newId));
                    $values = array('server_id' => $serverInfo['id'], 'available' => MonitisConf::$settings['ping']['available'],
                                    'monitor_id' => $newId, 'monitor_type' => 'ping', 'client_id' => MONITIS_CLIENT_ID, 'publickey' => $publicKey);
                    insert_query('mod_monitis_ext_monitors', $values);
                    MonitisApp::addMessage('Ping Monitor successfully created');
                    $serverInfo['ping'] = $serverMonitors->getMonitor($resp['data']['testId'], 'ping');
                } else {
                    MonitisApp::addError($resp['error']);
                }
            }
            
            break;
        case 'cpu':
            if(isset($_POST['id'])){
                $monitorId = (int) $_POST['id'];
                $monitor = MonitisApi::getCPUMonitor($monitorId);

                $platform = $monitor['agentPlatform'];
                $params = array(
                    'testId' => $monitorId,
                    'name' => $serverInfo['ipaddress'] . '_cpu',
                    'tag' => $serverInfo['hostname'] . '_whmcs'
                );	
                $cpu = MonitisConf::$settings['cpu'][$platform];
                foreach($cpu as $key=>$val){
                    $params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $cpu[$key];
                }
                $resp = MonitisApi::editCPUMonitor($params);
                if($resp && $resp['status'] == 'ok') {
                    MonitisApp::addMessage('CPU Monitor successfully updated');
                    $serverInfo['agent']['cpu'] = $serverMonitors->getMonitor($monitorId, 'cpu');
                }
                else {
                    MonitisApp::addError($resp['error']);
                }
            }
            else{
                $hostname = $serverInfo['hostname'];
                $agents = MonitisApi::getAgent($hostname);
                $agentKey = $agents[0]['key'];
                $platform = $agents[0]['platform'];
                
                $agentInfo = array(
                    'agentKey' => $agents[0]['key'],
                    'agentId' => $agents[0]['id'],
                    'name' => $hostname,
                    'platform' => $platform 
                );                
                $internalMonitors = MonitisApi::getInternalMonitors();
                $cpu = MonitisConf::$settings['cpu'][$platform];
                $cpuSettings = array(
                    'platform' => array(
                        $platform => array()
                    )
                );
                foreach($cpu as $key=>$val) {
                    $cpuSettings['platform'][$platform][$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $cpu[$key];
                }
                $resp = MonitisApiHelper::addCPUMonitor($serverInfo, MONITIS_CLIENT_ID, $agentInfo, $internalMonitors['cpus'], $cpuSettings['platform'] );
                if($resp['status'] === 'ok') {
                    MonitisApp::addMessage('CPU Monitor successfully created');
                    $serverInfo['agent']['cpu'] = $serverMonitors->getMonitor($resp['id'], 'cpu');
                }
                else {
                    MonitisApp::addError($resp['error']);
                }
            }
            break;
        case 'memory':
            if(isset($_POST['id'])) {
                $monitor = MonitisApi::getMemoryInfo((int) $_POST['id']);
                $platform = $monitor['agentPlatform'];
                $monitorId = (int) $_POST['id'];
                $params = array(
                    'testId' => $monitorId,
                    'name' => $serverInfo['ipaddress'] . '_memory',
                    'tag' => $serverInfo['hostname'] . '_whmcs',
                    'platform'=>$platform
                );	
                $memory = MonitisConf::$settings['memory'][$platform];
                foreach($memory as $key=>$val){
                    $params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $memory[$key];
                }
                $resp = MonitisApi::editMemoryMonitor($params);
                if($resp && $resp['status'] == 'ok') {
                    MonitisApp::addMessage('Memory Monitor successfully updated');
                    $serverInfo['agent']['memory'] = $serverMonitors->getMonitor($monitorId, 'memory');
                }
                else {
                    MonitisApp::addError($resp['error']);
                }
            }
            else {
                $hostname=$serverInfo['hostname'];
                $agents = MonitisApi::getAgent( $hostname );
                if($agents) {
                    $agentKey = $agents[0]['key'];
                    $platform = $agents[0]['platform'];
                    $agentId = $agents[0]['id'];
                    $params = array(
                        'agentkey'	=>	$agentKey,
                        'name'		=>	'memory@'.$hostname,
                        'tag'		=> 	$hostname.'_whmcs',
                        'platform'	=>	$platform
                    );
                    $memory = MonitisConf::$settings['memory'][$platform];
                    foreach($memory as $key=>$val) {
                         $params[$key] = isset($_POST[$key]) ? intval($_POST[$key]) : $memory[$key];
                    }
                    $resp = MonitisApi::addMemoryMonitor( $params );
                    if(isset($resp['data']) && isset($resp['data']['testId'])) {
                        $memory_monitorId = $resp['data']['testId'];
                        $pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'memory','monitorId'=>$memory_monitorId) );
                        $values = array(
                                "server_id" => $serverInfo['id'],
                                "available" => MonitisConf::$settings['memory']['available'],
                                "monitor_id" => $memory_monitorId,
                                "agent_id" => $agentId,
                                "monitor_type" => 'memory',
                                "client_id"=> MONITIS_CLIENT_ID,
                                "publickey"=> $pubKey
                        );
                        insert_query('mod_monitis_int_monitors', $values);
                        MonitisApp::addMessage('Memory Monitor successfully created');
                        $serverInfo['agent']['memory'] = $serverMonitors->getMonitor($resp['data']['testId'], 'memory');
                    }
                    else {
                        MonitisApp::addError($resp['error']);
                    }
                }
                else {
                    MonitisApp::addError('This server agent does not have Memory');	
                }
            }
            break;
        case 'drive':
            $hostname=$serverInfo['hostname'];
            $agents = MonitisApi::getAgent($hostname);
            $agentId = $agents[0]['id'];
            $agentKey = $agents[0]['key'];
            $platform = $agents[0]['platform'];
            $driveLetter = $_POST['driveLetter'];
            $freeLimit = $_POST['freeLimit'];
            if(isset($_POST['id'])) {
                $monitorId = (int) $_POST['id'];
                $params = array(
                    'testId' => $monitorId,
                    'freeLimit' => $freeLimit,
                    //'name' => $serverInfo['ipaddress'] . '_drive',
					'name' => 'drive_' . $driveLetter . '@' . $serverInfo['hostname'],
                    'tag' => $serverInfo['hostname'] . '_whmcs'
                );

                $resp = MonitisApi::editDriveMonitor( $params );
                if($resp) {
                    if($resp['status'] == 'ok' ) {
                        $pubKey = MonitisApi::monitorPublicKey(array('moduleType'=>'drive','monitorId'=>$monitorId));
                        $values = array(
                            'server_id' => $serverInfo['id'],
                            "available" => MonitisConf::$settings['drive']['available'],
                            'agent_id' => $agentId,
                            'monitor_id' => (int) $_POST['id'],
                            'monitor_type' => 'drive',
                            'client_id'=> MONITIS_CLIENT_ID,
                            "publickey"=> $pubKey
                        );
                        insert_query('mod_monitis_int_monitors', $values);
                        MonitisApp::addMessage('Drive Monitor successfully updated');
                        for($i = 0; $i < count($serverInfo['agent']['drive']); $i++){
                            if(isset($serverInfo['agent']['drive'][$i]['letter']) &&
                                $serverInfo['agent']['drive'][$i]['letter'] === $driveLetter){
                                $serverInfo['agent']['drive'][$i] = $serverMonitors->getMonitor($monitorId, 'drive');
                            }
                        }
                    }
                    else {
                        MonitisApp::addError($resp['error']);
                    }
                }
            }
            else {
                $params = array(
                    'agentkey' => $agentKey,
                    'driveLetter' => $driveLetter,
                    'freeLimit' => $freeLimit,
                    'name' => 'drive_' . $driveLetter . '@' . $serverInfo['hostname'],
                    'tag' => $serverInfo['hostname'] . '_whmcs'
                );
                $resp = MonitisApi::addDriveMonitor($params);
                if($resp) {
                    if($resp['status'] == 'ok') {
                        $newID = $resp['data']['testId'];
                        $pubKey = MonitisApi::monitorPublicKey( array('moduleType'=>'drive','monitorId'=>$newID) );
                        $values = array(
                            'server_id' => $serverInfo['id'],
                            'available' => MonitisConf::$settings['drive']['available'],
                            'agent_id' => $agentId,
                            'monitor_id' => $newID,
                            'monitor_type' => 'drive',
                            'client_id'=> MONITIS_CLIENT_ID,
                            'publickey' => $pubKey
                        );
                        insert_query('mod_monitis_int_monitors', $values);
                        MonitisApp::addMessage('Drive Monitor successfully added');
                        for($i = 0; $i < count($serverInfo['agent']['drive']); $i++){
                            if(isset($serverInfo['agent']['drive'][$i]['driveLetter']) &&
                                $serverInfo['agent']['drive'][$i]['driveLetter'] === $driveLetter){
                                $serverInfo['agent']['drive'][$i] = $serverMonitors->getMonitor($resp['data']['testId'], 'drive');
                            }
                        }
                    }
                    else {
                        MonitisApp::addError($resp['error']);		
                    }
                }
            }
    }
}

if($serverInfo['ping'] != NULL){
    $interval = explode(',', $serverInfo['ping']['intervals']);
    $settings = array(
        'interval' => $interval[0],
        'locationIds' => explode(',', $serverInfo['ping']['locations']),
        'timeout' => $serverInfo['ping']['timeout']
    );
    $serverInfo['ping']['settings'] = str_replace('"', "~", json_encode($settings));
}

if($serverInfo['agent'] != NULL){
    if($serverInfo['agent']['cpu'] != NULL){
        $settings = array(
            'kernelMax' => $serverInfo['agent']['cpu']['kernelMax'],
            'usedMax' => $serverInfo['agent']['cpu']['userMax'],
            'idleMin' => $serverInfo['agent']['cpu']['idleMin'],
            'ioWaitMax' => $serverInfo['agent']['cpu']['iowaitMax'],
            'niceMax' => $serverInfo['agent']['cpu']['niceMax']
        );
        $serverInfo['agent']['cpu']['settings'] = str_replace('"', "~", json_encode($settings));
    }
    if($serverInfo['agent']['memory'] != NULL){
        $settings = array(
            'freeLimit' => $serverInfo['agent']['memory']['freeLimit'],
            'freeSwapLimit' => $serverInfo['agent']['memory']['freeSwapLimit']
        );
        $serverInfo['agent']['memory']['settings'] = str_replace('"', "~", json_encode($settings));
    }
    if($serverInfo['agent']['drive'] != NULL){

        foreach($serverInfo['agent']['drive'] as $key => $drive){
            if(isset($drive['id'])){
                $settings = array(
                    'freeLimit' => $drive['freeLimit'],
                    'driveLetter' => $drive['letter']
                );
                $serverInfo['agent']['drive'][$key]['settings'] = str_replace('"', "~", json_encode($settings));
            }
            else{
                array_push($drives, $drive['driveLetter']);
            }
        }
    }
}

$isShowPingButton = !isset($serverInfo['ping']) || !isset($serverInfo['ping']['publickey']);
if(isset($serverInfo['agent'])) {
    $isShowCpuButton = !isset($serverInfo['agent']['cpu']) || !isset($serverInfo['agent']['cpu']['publickey']);
    $isShowMemoryButton = !isset($serverInfo['agent']['memory']) || !isset($serverInfo['agent']['memory']['publickey']);
    $isShowDriveButton = false;
    if(isset($serverInfo['agent']['drive'])) {
        foreach($serverInfo['agent']['drive'] as $drive) {
            if(!isset($drive['publickey'])) {
                $isShowDriveButton = true;
                break;
            }
        }
    }
}
else{
    $isShowCpuButton = false;
    $isShowMemoryButton = false;
    $isShowDriveButton = false;
}
//_dump($serverInfo);
?>

<style>
.monitis-monitors-back{
    text-align: right;
}
.monitis-monitors-info{
    text-align: left;
}
.monitis-monitor{
    padding: 20px 0;
    border-bottom: 1px solid #EBEBEB;
}
.monitis-monitor:last-child{
    border-bottom: 0;
}
.monitis-monitor-buttons{
    width: 760px;
    margin: 10px auto;
}
.monitis-monitor-buttons:after{
    display: block;
    clear: both;
    content: '.';
    height: 0;
    visibility: hidden;
}
.monitis-monitor-buttons-status{
    float: left;
}
.monitis-monitor-buttons-actions{
    float: right;
}
.monitis-monitors-actions{
    text-align: left;
    padding: 10px 0;
}
</style>

<script type="text/javascript" src="../modules/addons/monitis_addon/static/js/monitisproductdialog.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    var monitisMonitors = {};
    monitisMonitors.locations = <?php echo json_encode($locations)?>;
    monitisMonitors.drives =  <?php echo json_encode($drives)?>;
    monitisMonitors.submit = function(data){
        for(var key in data){
            $('.monitis-monitors-form').append('<input type="hidden" name="' + key + '" value="' + data[key] + '" />');
        }
        $('.monitis-monitors-form').submit();
    }
    
    $('.monitis-add-ping').click(function() {
        monitisProductDialog({
                'type': 'server-ping',
                'name': 'Create Ping Monitor',
                'settings': {
                        'type': 'ping',
                        'interval': <?php echo MonitisConf::$settings['ping']['interval'] ?>,
                        'timeout': <?php echo MonitisConf::$settings['ping']['timeout'] ?>,
                        'locationIds': <?php echo json_encode(MonitisConf::$settings['ping']['locationIds']) ?>
                },
                'locations': monitisMonitors.locations
        }, function(settings) {
            settings.type = 'ping';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-monitor[data-type=ping] .monitis-monitor-edit').click(function() {
        var $monitor = $(this).closest('.monitis-monitor');
        var settings = $monitor.attr('data-settings');
        settings = settings.replace(/~/g, '"');
        settings = JSON.parse(settings);
        
        settings['id'] = $monitor.attr('data-id');
        monitisProductDialog({
                'type': 'server-ping',
                'name': $monitor.attr('data-name'),
                'settings': settings,
                'locations': monitisMonitors.locations
        }, function(settings) {
            settings.type = 'ping';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-add-cpu').click(function() {
        monitisProductDialog({
                'type': 'server-cpu',
                'name': 'New CPU Monitor',
                'settings': <?php echo json_encode(MonitisConf::$settings['cpu']['LINUX']) ?>
        }, function(settings) {
            settings.type = 'cpu';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-monitor[data-type=cpu] .monitis-monitor-edit').click(function() {
        var $monitor = $(this).closest('.monitis-monitor');
        var settings = $monitor.attr('data-settings');
        settings = settings.replace(/~/g, '"');
        settings = JSON.parse(settings);
        
        settings['id'] = $monitor.attr('data-id');
        monitisProductDialog({
                'type': 'server-cpu',
                'name': $monitor.attr('data-name'),
                'settings': settings
        }, function(settings) {
            settings.type = 'cpu';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-add-memory').click(function() {
        monitisProductDialog({
                'type': 'server-memory',
                'name': 'New Memory Monitor',
                'settings': <?php echo json_encode(MonitisConf::$settings['memory']['LINUX']) ?>,
                'locations': monitisMonitors.locations
        }, function(settings) {
            settings.type = 'memory';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-monitor[data-type=memory] .monitis-monitor-edit').click(function() {
        var $monitor = $(this).closest('.monitis-monitor');
        var settings = $monitor.attr('data-settings');
        settings = settings.replace(/~/g, '"');
        settings = JSON.parse(settings);
        
        settings['id'] = $monitor.attr('data-id');
        monitisProductDialog({
                'type': 'server-memory',
                'name': $monitor.attr('data-name'),
                'settings': settings
        }, function(settings) {
            settings.type = 'memory';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-add-drive').click(function() {
        monitisProductDialog({
                'type': 'server-drive',
                'name': 'New Drive Monitor',
                'settings': {
                    'freeLimit': <?php echo MonitisConf::$settings['drive']['freeLimit'] ?>
                },
                'drives': monitisMonitors.drives
        }, function(settings) {
            settings.type = 'drive';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-monitor[data-type=drive] .monitis-monitor-edit').click(function() {
        var $monitor = $(this).closest('.monitis-monitor');
        var settings = $monitor.attr('data-settings');
        settings = settings.replace(/~/g, '"');
        settings = JSON.parse(settings);
        
        settings['id'] = $monitor.attr('data-id');
        monitisProductDialog({
                'type': 'server-drive',
                'name': $monitor.attr('data-name'),
                'settings': settings
        }, function(settings) {
            settings.type = 'drive';
            monitisMonitors.submit(settings);
        });
    });
    $('.monitis-monitor-delete').click(function(){
        var $monitor = $(this).closest('.monitis-monitor');
        var $dialog = $('<div style="padding: 20px;">Are you sure you want to remove the <b>' + $monitor.attr('data-type') + '</b> monitor?</div>');
        $dialog.dialog({
            'modal': true,
	    'title': $monitor.attr('data-name'),
            'buttons':{
                'yes': {
                    text: 'Yes',
                    class: 'btn',
                    click: function() {
			monitisModal.open({content: null});
                        monitisMonitors.submit({
                            'action': 'delete',
                            'type':  $monitor.attr('data-type'),
                            'id': $monitor.attr('data-id')
                        });
                        $(this).dialog("close");
                    }
                },
                'no': {
                    text: 'No',
                    class: 'btn',
                    click: function() {
                        $(this).dialog("close");
                    }
                }
            },
            'close': function(){
                $(this).remove();
            }
        });
    });
    $('.monitis-monitor-suspend').click(function(){
        var $monitor = $(this).closest('.monitis-monitor');
        monitisMonitors.submit({
            'action': 'suspend',
            'id': $monitor.attr('data-id')
        });
    });
    $('.monitis-monitor-activate').click(function(){
        var $monitor = $(this).closest('.monitis-monitor');
        monitisMonitors.submit({
            'action': 'activate',
            'id': $monitor.attr('data-id')
        });
    });
    $('.monitis-monitor-make-available').click(function(){
        var $monitor = $(this).closest('.monitis-monitor');
        monitisMonitors.submit({
            'action': 'makeAvailable',
            'type':  $monitor.attr('data-type'),
            'id': $monitor.attr('data-id')
        });
    });
    $('.monitis-monitor-make-not-available').click(function(){
        var $monitor = $(this).closest('.monitis-monitor');
        monitisMonitors.submit({
            'action': 'makeNotAvailable',
            'type':  $monitor.attr('data-type'),
            'id': $monitor.attr('data-id')
        });
    });
});
</script>

<div class="monitis-monitors">
    <div class="monitis-monitors-back">
        <a href="<?php echo MONITIS_APP_URL ?>&monitis_page=tabadmin&sub=servers" class="monitis_link_result">← Back to servers list</a>
    </div>
    <?php MonitisApp::printNotifications(); ?>
    <div class="monitis-monitors-info">
        Server name: <b><?php echo $serverInfo['name'] ?></b><br/>
        <?php if(isset($serverInfo['agent'])): ?>
        Agent is <b><?php echo $serverInfo['agent']['status'] ?></b><br/>
        <?php else: ?>
        No agent
        <?php endif ?>
    </div>
    <div class="monitis-monitors-actions">
        <?php if(isset($serverInfo['agent']) && $serverInfo['agent']['status'] == 'running'): ?>
        <b>Add monitor:</b>
        <button class="btn monitis-add-ping" <?php if(!$isShowPingButton):?>disabled="disabled"<?php endif?>>Ping</button>
        <button class="btn monitis-add-cpu" <?php if(!$isShowCpuButton):?>disabled="disabled"<?php endif?>>CPU</button>
        <button class="btn monitis-add-memory" <?php if(!$isShowMemoryButton):?>disabled="disabled"<?php endif?>>Memory</button>
        <button class="btn monitis-add-drive" <?php if(!$isShowDriveButton):?>disabled="disabled"<?php endif?>>Drive</button>
        <?php elseif($isShowPingButton): ?>
        <button class="btn monitis-add-ping">Add Ping Monitor</button>
        <?php endif ?>
    </div>
    
    <?php if(isset($serverInfo['ping']) && isset($serverInfo['ping']['publickey'])): ?>
    <div class="monitis-monitor" data-id="<?php echo $serverInfo['ping']['id'] ?>" data-type="ping" data-name="<?php echo $serverInfo['ping']['name'] ?>" data-settings="<?php echo $serverInfo['ping']['settings'] ?>">
        <div class="monitis-monitor-buttons">
            <div class="monitis-monitor-buttons-status">
                <?php if($serverInfo['ping']['isSuspended']): ?>
                <button class="btn btn-mini btn-success monitis-monitor-activate monitis_link_button">Activate</button>
                <?php else: ?>
                <button class="btn btn-mini monitis-monitor-suspend monitis_link_button">Suspend</button>
                <?php endif ?>
                <?php if($serverInfo['ping']['available']): ?>
                <button class="btn btn-mini monitis-monitor-make-not-available monitis_link_button">Make not available to clients</button>
                <?php else: ?>
                <button class="btn btn-mini btn-success monitis-monitor-make-available monitis_link_button">Make availlable to clients</button>
                <?php endif ?>
            </div>
            <div class="monitis-monitor-buttons-actions">
                <button class="btn btn-mini monitis-monitor-edit">Edit</button>
                <button class="btn btn-mini btn-danger monitis-monitor-delete">Delete</button>
            </div>
        </div>
        <?php echo monitis_embed_module($serverInfo['ping']['publickey'], 800, 320); ?>
    </div>
    <?php endif ?>
    
    <?php if(isset($serverInfo['agent']) && $serverInfo['agent']['status'] == 'running'): ?>
    <?php if(isset($serverInfo['agent']['cpu']) && isset($serverInfo['agent']['cpu']['publickey'])): ?>
    <div class="monitis-monitor" data-id="<?php echo $serverInfo['agent']['cpu']['id'] ?>" data-type="cpu" data-name="<?php echo $serverInfo['agent']['cpu']['name'] ?>" data-settings="<?php echo $serverInfo['agent']['cpu']['settings'] ?>">
        <div class="monitis-monitor-buttons">
            <div class="monitis-monitor-buttons-status">
                <?php if($serverInfo['agent']['cpu']['available']): ?>
                <button class="btn btn-mini monitis-monitor-make-not-available monitis_link_button">Make not available to clients</button>
                <?php else: ?>
                <button class="btn btn-mini btn-success monitis-monitor-make-available monitis_link_button">Make availlable to clients</button>
                <?php endif ?>
            </div>
            <div class="monitis-monitor-buttons-actions">
                <button class="btn btn-mini monitis-monitor-edit">Edit</button>
                <button class="btn btn-mini btn-danger monitis-monitor-delete">Delete</button>
            </div>
        </div>
        <?php echo monitis_embed_module($serverInfo['agent']['cpu']['publickey'], 800, 350); ?>
    </div>
    <?php endif ?>
    
    <?php if(isset($serverInfo['agent']['memory']) && isset($serverInfo['agent']['memory']['publickey'])): ?>
    <div class="monitis-monitor" data-id="<?php echo $serverInfo['agent']['memory']['id'] ?>" data-type="memory" data-name="<?php echo $serverInfo['agent']['memory']['name'] ?>" data-settings="<?php echo $serverInfo['agent']['memory']['settings'] ?>">
        <div class="monitis-monitor-buttons">
            <div class="monitis-monitor-buttons-status">
                <?php if($serverInfo['agent']['memory']['available']): ?>
                <button class="btn btn-mini monitis-monitor-make-not-available monitis_link_button">Make not available to clients</button>
                <?php else: ?>
                <button class="btn btn-mini btn-success monitis-monitor-make-available monitis_link_button">Make availlable to clients</button>
                <?php endif ?>
            </div>
            <div class="monitis-monitor-buttons-actions">
                <button class="btn btn-mini monitis-monitor-edit">Edit</button>
                <button class="btn btn-mini btn-danger monitis-monitor-delete">Delete</button>
            </div>
        </div>
        <?php echo monitis_embed_module($serverInfo['agent']['memory']['publickey'], 800, 350); ?>
    </div>
    <?php endif ?>
    
    <?php if(isset($serverInfo['agent']['drive'])): ?>
    <?php foreach($serverInfo['agent']['drive'] as $drive): ?>
    <?php if(isset($drive['publickey'])): ?>
    <div class="monitis-monitor" data-id="<?php echo $drive['id'] ?>" data-type="drive" data-name="<?php echo $drive['name']?>" data-settings="<?php echo $drive['settings'] ?>">
        <div class="monitis-monitor-buttons">
            <div class="monitis-monitor-buttons-status">
                <?php if($drive['available']): ?>
                <button class="btn btn-mini monitis-monitor-make-not-available monitis_link_button">Make not available to clients</button>
                <?php else: ?>
                <button class="btn btn-mini btn-success monitis-monitor-make-available monitis_link_button">Make availlable to clients</button>
                <?php endif ?>
            </div>
            <div class="monitis-monitor-buttons-actions">
                <button class="btn btn-mini monitis-monitor-edit">Edit</button>
                <button class="btn btn-mini btn-danger monitis-monitor-delete">Delete</button>
            </div>
        </div>
        <?php echo monitis_embed_module($drive['publickey'], 800, 350); ?>
    </div>
    <?php endif ?>
    <?php endforeach ?>
    <?php endif ?>
    <?php endif ?>
</div>
<form class="monitis-monitors-form" action="" method="POST" style="display: none;"></form>