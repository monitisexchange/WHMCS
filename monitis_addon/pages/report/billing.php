<?php

define('MONITIS_PLAN_ACTIONS', '[
{"id":1,"name":"uptimes","title":"Uptime monitors"},
{"id":135,"name":"servers","title":"Server/Device Monitors"},
{"id":15,"name":"transaction","title":"Transaction Monitors"},
{"id":63,"name":"fullPage","title":"Full Page Load Monitors"},
{"id":133,"name":"application","title":"Application Monitors"},
{"id":139,"name":"custom","title":"Custom Monitors"},
{"id":6,"name":"webTraffic","title":"Web Traffic Monitors"},
{"id":10,"name":"smsAndCall","title":"SMS and Call"},
{"id":23,"name":"subAccount","title":"Sub Accounts"}
]');

class billingPlan {

	private $plans = null;
	
	public function __construct () {
		$plans = json_decode(MONITIS_PLAN_ACTIONS, true);
		
		$this->plans = array();
		for($i=0; $i<count($plans); $i++) {
			$this->plans[$plans[$i]['name']] = $plans[$i];
		}
	}

	private function basketItemById(& $basketItems, $actionId) {

		for($i=0; $i<count($basketItems); $i++) {
			$basketItem = $basketItems[$i]["permissions"][0];
			if($basketItem["actionId"] == $actionId) {
				return $basketItems[$i];
			}
		}
		return null;
	}
	
	public function planDetails($sss) {
		
		$result = array(
			'clients' => 0,
			'clientsMonitors' => 0,
			'uptimeMonitors' => 0,
			'locations' => 0,
			'agents' => 0
		);
		/*
		 * parent user info, plan
		 */
		$params = array(
			'loadUserPlan' => 'true'
			//,'loadMonitors' => 'true'
		);
		$userInfo = MonitisApi::userInfo($params);
		if( isset($userInfo['status']) && $userInfo['status'] == 'error' && isset($userInfo['code']) && $userInfo['code'] == 101 )
			return null;
			
		$plan = array(
			'roleName' => '',
			'uptimePlan' => 0,
			'intMonitors' => 0
		);

		if($userInfo) {
			$roleName = $userInfo['plan']['roleName'];
			$basketItems = $userInfo['plan']['basketItems'];
			$plan['roleName'] = $roleName;

			if(strtoupper($roleName) == 'TRIAL') {
				$trial = MonitisHelper::in_array($basketItems, 'name', $roleName);
				if($trial) {
					$ext = MonitisHelper::in_array($trial['permissions'], 'actionId', $this->plans["uptimes"]["id"]);	// External monitoring / Uptime
					$plan['uptimePlan'] = $ext['value'];
					$int = MonitisHelper::in_array($trial['permissions'], 'actionId', $this->plans["servers"]["id"]);	// Server
					$plan['serverPlan'] = $int['value'];
				}
				
			} else {
				$ext = $this->basketItemById($basketItems, $this->plans["uptimes"]["id"]);
				$plan['uptimePlan'] = $ext['quantity'];
				$int = $this->basketItemById($basketItems, $this->plans["servers"]["id"]);
				$plan['serverPlan'] = $int['quantity'];
			}
			$result['plan'] = $plan;
		}
		
		/*
		 * clients info
		 */
		$params = array('loadUserPlan' => 'true','loadMonitors' => 'true');
		$subUsers = MonitisApi::clientsList($params);

		$userMonitors = 0;
		for($i=0; $i<count($subUsers); $i++) {
			$userMonitors += count($subUsers[$i]['monitors']);
		}
		
		$result['clients'] = 0;
		if($subUsers && count($subUsers) > 0)
			$result['clients'] = count($subUsers);
		$result['clientsMonitors'] = $userMonitors;
		
		/*
		 * parent info
		 * get all uptime monitors
		 */
		$snapshots = MonitisApi::externalSnapshot();
		
		$result['uptimeMonitors'] = 0;
		if($snapshots && count($snapshots) > 0)
			$result['uptimeMonitors'] = count($snapshots);

		/*
		 * Server / Device monitors
		 *
		 */
		$agentsSnapshot = MonitisApi::allAgentsSnapshot('', true);
		if($agentsSnapshot && isset($agentsSnapshot['agents'])) {
			$result['agents'] = count($agentsSnapshot['agents']);
		}
		$result['internalMonitors'] = 0;
		$internalMonitors = MonitisApi::getInternalMonitors();
		if($internalMonitors) {
			if(isset($internalMonitors['cpus'])) {
				$result['internalMonitors'] += count($internalMonitors['cpus']);
			}
			if(isset($internalMonitors['memories'])) {
				$result['internalMonitors'] += count($internalMonitors['memories']);
			}
			if(isset($internalMonitors['drives'])) {
				$result['internalMonitors'] += count($internalMonitors['drives']);
			}
		}

		return $result;
	}
}

$oPlan = new billingPlan();
$uptimeInfo = $oPlan->planDetails();

if($uptimeInfo) {
	$uptimePlan = $uptimeInfo['plan']['uptimePlan'];
	$serverPlan = $uptimeInfo['plan']['serverPlan'];
	$roleName = '';
	if($uptimeInfo['plan']['roleName'] == 'TRIAL') {
		$roleName = $uptimeInfo['plan']['roleName'];
	}

	$uptimeTotal = $uptimeInfo['uptimeMonitors'] + $uptimeInfo['clientsMonitors'];

	$available = 0;
	if($uptimeTotal < $uptimePlan) {
		$available = $uptimePlan - $uptimeTotal;
	} 

	$intAvailable = 0;
	if($serverPlan >= $uptimeInfo['agents']) {
		$intAvailable = $serverPlan - $uptimeInfo['agents'];
	}

?>
<style type="text/css">
.datatable th{
	padding-left:10px;
}
.datatable td{
	padding:5px 10px;
}
.datatable span{
	font-size:14px;
}
.datatable label{
	font-size:12px;
	color:#888888;
}
.monitis-role-name {
	text-align:left;
	font-weight:bold;
	padding: 10px 0px;
}
</style>
<div class="monitis-role-name"><?php echo $roleName?></div>


<table class="datatable" style="text-align: left;">
    <tr>
		<th width="200px">&nbsp;</th>
		<th width="20%">Plan</th>
		<th width="20%">Admin monitors</th>
		
		<th width="20%">Clients monitors</th>
		<th width="100px">Total</th>
		<th width="100px">Available</th>
    </tr>
    <tr>
		<td>Uptime</td>
		<td><span><?php echo $uptimePlan?></span></td>
		<td><span><?php echo $uptimeInfo['uptimeMonitors']?></span></td>
		
		<td><span><?php echo $uptimeInfo['clientsMonitors']?> </span> <!-- label>(from <?php echo $uptimeInfo['clients']?> clients)</label --></td>
		<td><?php echo $uptimeTotal?></td>
		<td><?php echo $available?></td>
	</tr>
	
    <tr>
		<td>Server/Device</td>
		<td><span><?php echo $serverPlan?></span></td>
		<td><?php echo $uptimeInfo['agents']?> <!-- label>(<?php echo $uptimeInfo['internalMonitors']?> monitors)</label --></td>
		<td><!-- <?php echo $uptimeInfo['internalMonitors']?> <label>from <?php echo $uptimeInfo['agents']?> agents</label> --></td>
		
		<td>&nbsp;</td>
		<td><?php echo $intAvailable?></td>
	</tr>
</table>

<?php
}
?>
