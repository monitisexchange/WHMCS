<?php

class MonitisModuleAssociateMonitorServer {
	function execute() {
		ob_start();
		$this->render();
		$content = ob_get_clean();
		return $content;
	}
	
	function render($action = '') {
		if (empty($action))
			$action = monitisPost('module_AssociateMonitorServer_action');
		if (empty($action))
			$action = 'default';
		
		include str_replace('/', '_', $action) . '.php';
	}
}