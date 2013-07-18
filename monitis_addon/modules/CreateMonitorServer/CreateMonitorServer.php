<?php

class MonitisModuleCreateMonitorServer {	
	public $serverID = 0;
	public $serverName = 0;
	public $editMode = 'edit';
	
	var $linkType = 'button'; // button, anchor
	var $linkText = 'Create new monitor';
	var $renderTrigger = true;
	
	function execute() {
		ob_start();
//echo "Module( class - MonitisModuleCreateMonitorServer ) ---- modules/CreateMonitorServer/CreateMonitorServer.php <br>";

		$this->render();
		$content = ob_get_clean();
		return $content;
	}
	
	function render($action = '') {
		$serverID = monitisGetInt('server_id');
		
		if (empty($action))
			$action = monitisPost('module_CreateMonitorServer_action');
		if (empty($action))
			$action = 'default';
		
		include str_replace('/', '_', $action) . '.php';
	}
}