<?php
class MonitisRouter {
	static function route() {
		$pageName = isset($_GET['monitis_page']) ? $_GET['monitis_page'] : 'servers';
		
		if ((empty(MonitisConf::$apiKey) || empty(MonitisConf::$secretKey)) && $pageName != 'monitisAccount') {
			header('location: ' . MONITIS_APP_URL . '&monitis_page=monitisAccount');
			exit;
		}
		
		$moduleName = monitisGet('monitis_module', false);
		if ($moduleName !== false) {
			self::renderModule($moduleName);
			return;
		}
		
		self::showPage($pageName);
	}
	
	static function showPage($pageName, $wrapInTabs = true) {
		if ($wrapInTabs) {
			require_once MONITIS_APP_PATH . '/pages/mainMenu.php';
			echo '<div id="tab_content">';
		}
		require_once MONITIS_APP_PATH . '/pages/' . $pageName . '.php';
		if ($wrapInTabs)
			echo '</div>';
	}
	
	static function renderModule($moduleName) {
		$module = MonitisApp::getModule($moduleName);
		$module->render();
	}
}