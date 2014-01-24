<?php
class MonitisRouter {
	static function route() {
		$pageName = isset($_GET['monitis_page']) ? $_GET['monitis_page'] : 'tabadmin';
		
		if ((empty(MonitisConf::$apiKey) || empty(MonitisConf::$secretKey)) && $pageName != 'account') {
			header('location: ' . MONITIS_APP_URL . '&monitis_page=account');
			//header('location: ' . MONITIS_APP_URL . '&monitis_page=settings&sub=account');
			exit;
		}
		// monitisAccount
		$moduleName = monitisGet('monitis_module', false);
		if ($moduleName !== false) {
			self::renderModule($moduleName);
			return;
		}
		self::showPage($pageName);
	}
	
	static function showPage($pageName, $wrapInTabs = true) {
		if ($wrapInTabs) {
			require_once MONITIS_APP_PATH . '/pages/mainmenu.php';
			echo '<div id="tab_content">';
		}
		
		//@include_once MONITIS_APP_PATH . '/pages/' . $pageName . '.php';
		try {
			@include_once MONITIS_APP_PATH . '/pages/' . $pageName . '.php';
		} catch (Exception $e) {
			echo 'Exception: ',  $e->getMessage(), "\n";
		}		
		
		
		if ($wrapInTabs)
			echo '</div>';
	}
	
	static function renderModule($moduleName) {
		$module = MonitisApp::getModule($moduleName);
		$module->render();
	}
}