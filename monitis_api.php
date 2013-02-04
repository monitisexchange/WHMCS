<?php

/* Monitis API calls to support WHMCS Monitis addon
 */

function monitis_extract_api_info($vars) {
  // extract API endpoint, apikey, secretkey from WHMCS request
  return array($vars['endpoint'], $vars['apikey'], $vars['secretkey']);
}

function monitis_hmac($params, $secretkey) {
  // in: a hash of key-value pairs of API parameters
  // out: a hash including all input KV pairs, plus the checksum
  //
  // 1. Sort request parameters by key name
  ksort($params);
  // 2. join param KV pairs in an undelimited string
  $joined = '';
  foreach ($params as $k => $v) $joined .= $k . $v;
  // 3. return base64 encoded hmacsha1 of the string from (2)
  $checksum =  base64_encode(hash_hmac('sha1', $joined, $secretkey, TRUE));
  $params['checksum'] = $checksum;
  return $params;
}

function monitis_get($apiurl, $apikey, $action, $params) {
  // TODO: error handling when JSON is not returned
  $params['version'] = '2';
  $params['action'] = $action;
  $params['apikey'] = $apikey;
  $query = http_build_query($params);
  $ch = curl_init($apiurl . '?' . $query);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $json = json_decode(curl_exec($ch), true);
  return $json;
}

function monitis_results_url($vars, $test_id) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $params['testId'] = $test_id;
  $params['day'] = date("d");
  $params['month'] = date("m");
  $params['year'] = date("Y");
  $params['version'] = '2';
  $params['action'] = 'testresult';
  $params['apikey'] = $ak;
  $query = http_build_query($params);
  return $ep . '?' . $query;
}


function monitis_post($apiurl, $apikey, $secretkey, $action, $params) {
  // TODO: error handling when JSON is not returned
  $params['version'] = '2';
  $params['action'] = $action;
  $params['apikey'] = $apikey;
  $params['timestamp'] = date("Y-m-d H:i:s");
  $params = monitis_hmac($params, $secretkey);
  $query = http_build_query($params);
  $ch = curl_init($apiurl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1 );
  curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
  $result = curl_exec($ch);
  $json = json_decode($result, true);
  return $json;
}

function monitis_add_ping_monitor($vars, $ip) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $params['type'] = 'ping';
  $params['name'] = 'WHMCS_' . $ip;
  $params['url'] = $ip;
  $params['interval'] = '1';
  $params['locationIds'] = '1,9,10';
  $params['tag'] = 'WHMCS';
  $params['url'] = $ip;
  
  $result = monitis_post($ep, $ak, $sk, 'addExternalMonitor', $params);
  return $result;
}

function monitis_remove_ping_monitor($vars, $test_id) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $params['testIds'] = $test_id;
  $result = monitis_post($ep, $ak, $sk, 'deleteExternalMonitor', $params);
  return $result;
}

function monitis_test_result($vars, $test_id) {
  // TODO: get data over a multi day time range and aggregate
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $params['testId'] = $test_id;
  $params['day'] = date("d");
  $params['month'] = date("m");
  $params['year'] = date("Y");
  //$params['timezone'] = "-360";

  $result = monitis_get($ep, $ak, 'testresult', $params);
  return $result;
}

?>
