<?php

class MonitisModuleServerSelector {
	function execute() {
		ob_start();
		$this->render();
		$content = ob_get_clean();
		return $content;
	}
	
	function render($action = '') {
//	echo "Module ---- modules/ServerSelector/default.php <br>"; 
//_dump($_GET);
//_dump($_POST);	
		if (empty($action))
			$action = monitisPost('module_ServerSelector_action');
		if (empty($action))
			$action = 'default';
		
		include str_replace('/', '_', $action) . '.php';
	}
}