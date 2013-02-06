<?php

/* 
 * Helper functions to support addon controller and views
 */

/*
 * Get the Monitis test ID for a server IP
 */
function ip_to_test($server_ip) {
  // in: server ip
  // out: test id
  // Query the mod_monitis_server DB to get the address
  $qr = select_query('mod_monitis_server', 'test_id', array('ip_addr'=>$server_ip));
  $data = mysql_fetch_array($qr);
  return $data['test_id'];
}

/*
 * Get the server IP address monitored by the given test ID
 */
function test_to_ip($test_id) {
  // in: monitis test id
  // out: monitored IP address
  // Query the mod_monitis_server DB to get the address
  $qr = select_query('mod_monitis_server', 'ip_addr', array('test_id'=>$test_id));
  $data = mysql_fetch_array($qr);
  return $data['ip_addr'];
}

/*
 * Add a ping monitor, and create a corresponding record in the addon DB
 */
function add_ping_monitor($vars, $server_ip) {
  // in: request vars and IP to add
  // out: on success, returns test_id, on failure, returns err message
  //
  // Validate input on $server_ip
  if(!filter_var($ip, FILTER_VALIDATE_IP)) {
    $msg = "Adding monitor for IP failed: invalid IP address";
    return array('ok'=>'', 'err'=>$msg);
  }
  
  // valid IP, so create the ping monitor and add to DB
  $result = monitis_add_ping_monitor($vars, $server_ip);
  if ($result['status'] == 'ok') {
    $test_id = $result['data']['testId'];
    $query = "insert into mod_monitis_server (ip_addr, monitored, test_id) values ('"
           . $server_ip . "', TRUE, " . $test_id . ")";
    mysql_query($query);
    $msg = 'Monitor ID ' . $test_id . ' added successfully!';
    return array('ok'=>$msg, 'err'=>'', 'test_id'=>$test_id);
  }
  else {
    $msg = "Adding monitor for $server_ip failed: " . $result['status'];
    return array('ok'=>'', 'err'=>$msg);
  }
}

/*
 * Delete a ping monitor and remove the corresponding DB record
 */
function remove_ping_monitor($vars, $server_ip) {
  // Validate input on $server_ip
  if(!filter_var($ip, FILTER_VALIDATE_IP)) {
    $msg = "Removing monitor for IP failed: invalid IP address";
    return array('ok'=>'', 'err'=>$msg);
  }
  
  $test_id = ip_to_test($server_ip);
  if (!$test_id) {
    return array('ok'=>'', 'err'=>"Server $server_ip not currently monitored");
  }
  $result = monitis_remove_ping_monitor($vars, $test_id);
  if ($result['status'] == 'ok') {
    $query = "delete from mod_monitis_server where test_id = '" . $test_id . "'";
    mysql_query($query);
    $msg = 'Monitor ID ' . $test_id . ' removed successfully!';
    return array('ok'=>$msg, 'err'=>'');
  }
  elseif ($result['status'] == 'Invalid monitorId') {
    $query = "delete from mod_monitis_server where test_id = '" . $test_id . "'";
    mysql_query($query);
    $msg = 'Monitor ID ' . $test_id . ' already removed from Monitis.';
    return array('ok'=>$msg, 'err'=>'');
  }
  else {
    $msg = "Removing monitor for test $test_id failed: " . $result['status'];
    return array('ok'=>'', 'err'=>$msg);
  }
}

?>
