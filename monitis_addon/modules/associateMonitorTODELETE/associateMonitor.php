<?php
/*$moduleAction = monitisGet('module_action');

switch ($moduleAction) {
	case 'listMonitors':
		include 'listMonitors.php';
		break;
	default:
		include 'main.php';
}*/

class MonitisModuleAssociateMonitor {
	static function doActions() {
		echo "performing actions";
	}
	
	static function render() {
		echo "rendering result";
	}
}