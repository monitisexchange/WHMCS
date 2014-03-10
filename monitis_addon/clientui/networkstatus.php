<?php
require_once('modules/addons/monitis_addon/monitisapp.php');
require_once('modules/addons/monitis_addon/lib/clientui.php');

$userid = 0;
//$language = $this->_tpl_vars['clientsdetails']['language'];
$language = 'english';

if(isset($_SESSION) && isset($_SESSION['uid']) && $_SESSION['uid'] > 0) {
	//$userid = $this->_tpl_vars['clientsdetails']['userid'];
	$userid = $_SESSION['uid'];
}

include_once('modules/addons/monitis_addon/lang/'.$language.'.php');


?>
<div class="page-header">
    <div class="styled_title"><h1><?php echo $MLANG['network_status']?></h1></div>
</div>
<?php
//echo "************ userid = $userid";
if( isset($userid) && $userid > 0) {

	$oClient = new monitisClientUi();
	$monitors = $oClient->userNetworkStatus( $userid );
	$isMonitor = false;
	if( $monitors && $monitors["status"] == 'ok') {
		echo '<section class="monitis_monitors">';
		$mons = $monitors["data"];
			
			for( $i=0; $i<count($mons); $i++){
				$item = $mons[$i];
				if( (isset($item['external']) && count($item['external']) > 0) || isset($item["internal"]) && count($item["internal"]) > 0 ) {
					echo '<h3>'.$item["groupname"].' - '.$item["name"].'</h3>';
					$isMonitor = true;
				} 
				if( isset($item['external']) && count($item['external']) > 0 ) {
					echo '<figure>' . monitis_embed_module( $item['external'][0]['publickey'], 770, 350 ).'</figure>';
					$isMonitor = true;
				}

				$int = $item["internal"];
				if( isset($int) && count($int) > 0 ){
					for( $j=0; $j<count($int); $j++){
						echo '<figure>' . monitis_embed_module( $int[$j]['publickey'], 770, 350 ).'</figure>';
						$isMonitor = true;
					}
				}
			}
			if($isMonitor == false) {
				echo '<div>No monitors for active products or they are not available</div>';
			}
		echo "</section>";
	} else {
			echo '<div>'.$monitors["msg"].'</div>';
	}
} else {
	//echo '<div>'.$MLANG['not_login'].'</div>';
	echo '<div>'.$MLANG['not_login'].'</div>';
}
?>