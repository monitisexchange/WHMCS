<?php

/* Monitis API calls to support WHMCS Monitis addon
 */

// TODO: update functions that take $vars argument to query WHMCS database

/*
 * Get API info from $vars passed in with HTTP requests 
 */
function monitis_extract_api_info($vars) {
  // extract API endpoint, apikey, secretkey from WHMCS request
  return array($vars['endpoint'], $vars['apikey'], $vars['secretkey']);
}

/*
 * Calculate HMAC for Monitis POST requests
 */
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

/*
 * Monitis GET API, for queries without side effects
 */
function monitis_get($apiurl, $apikey, $action, $params) {
  // TODO: error handling when JSON is not returned
  $params['version'] = '2';
  $params['action'] = $action;
  $params['apikey'] = $apikey;
  $query = http_build_query($params);
  $ch = curl_init($apiurl . '?' . $query);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $json = json_decode(curl_exec($ch), true);
  //debug("GET: ", $params);
  //debug("RESPONSE: ", $json);
  return $json;
}

/*
 * Monitis POST API, used for calls with side effects, i.e. create, delete
 */
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
  //debug("POST: ", $params);
  //debug("RESPONSE: ", $json);
  return $json;
}

/*
 * Get Monitis agents
 */
function monitis_agents($vars) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $result = monitis_get($ep, $ak, 'agents', $params);
  debug("monitis_agents: ", $result);
  return $result;
}

/*
 * Add a new Monitis ping monitor, returning the API JSON result
 */
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

/*
 * Delete Monitis ping monitor with ID $test_id
 */
function monitis_remove_ping_monitor($vars, $test_id) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  $params['testIds'] = $test_id;
  $result = monitis_post($ep, $ak, $sk, 'deleteExternalMonitor', $params);
  return $result;
}

/*
 * Get recent Monitis test results, covering time span from 
 * ($end - $span) until $end.  If $end is not provided, then
 * it defaults to the current time.  If $span is not provided,
 * then it defaults to 4 hours prior to $end.
 */
function monitis_test_result_recent($vars, $test_id, $span, $end) {
  // return results extending $span seconds prior to $end
  // $end defaults to time()
  // span defaults to 4 hours
  if (!$end) $end = time();
  if (!$span) $span = 14400;

  // Get enough days' results to cover the span
  $results = array();
  $current = $end - $span;
  while ($current < $end) {
    $results[] = monitis_test_result($vars, $test_id, $current);
    $current += 86400; // add one day
  }

  // Collect the results that are in {f(t): ($end - $span) < t <= $end}
  // Repth-first traversal of the tree of all data points, with the
  // ones we want added to $combined
  $combined = array();
  foreach ($results as $result) {
    foreach ($result as $loc => $data) {
      foreach ($data as $point) {
        list($time, $value, $status) = $point;
        //print_r(strtotime($time));
        $unix_time = strtotime($time);
        if ($unix_time > ($end - $span) && $unix_time <= $end)
          $combined[$loc][] = array($unix_time, $value);
      }
    }
  }
  return $combined;
}

/*
 * Get results for the given $test_id for the day including $time
 */
function monitis_test_result($vars, $test_id, $time) {
  // Returns test_result data for the day including $time || time()
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params = array();
  if (!$time) $time = time();
  $params['testId'] = $test_id;
  $date_info = getdate($time);
  $params['day'] = $date_info["mday"];
  $params['month'] = $date_info["mon"];
  $params['year'] = $date_info["year"];
  //$params['timezone'] = "-360";

  $result = monitis_get($ep, $ak, 'testresult', $params);
  $series = array();
  foreach ($result as $loc) {
    $name = $loc['locationName'];
    //$series[$name] = monitis_to_rickshaw($loc['data']);
    $series[$name] = $loc['data'];
  }
  return $series;
}

/*
 * Get most recent status for all external monitors
 */
function external_snapshot($vars) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $results = monitis_get($ep, $ak, 'testsLastValues');

  $agg_status = array();
  $agg_perf = array();
  // iterate over the results, extract interesting features, keyed on test_id
  foreach ($results as $loc) {
    foreach ($loc['data'] as $d) {
      $test_id = $d['id'];
      $status = $d['status'];
      $perf = $d['perf'];
      // aggregate status as OK if any are OK, NOK otherwise
      if ($status && ($agg_status[$test_id] != 'OK')) $agg_status[$test_id] = $status;
      // aggregate performance as minimum of ping time
      if (!$agg_perf[$test_id] || $agg_perf[$test_id] > $perf) $agg_perf[$test_id] = $perf;
    }
  }
  return array('status' => $agg_status, 'perf' => $agg_perf);
}

/*
 * Add pages in the Monitis Dashboard
 */
function monitis_add_page($vars, $title) {
  // create a page with the given title, return a page id
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params['title'] = $title;
  $result = monitis_post($ep, $ak, $sk, 'addPage', $params);
  return $result;
}

/*
 * Delete pages in the Monitis Dashboard
 */
function monitis_delete_page($vars, $page_id) {
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params['pageId'] = $page_id;
  $result = monitis_post($ep, $ak, $sk, 'deletePage', $params);
  return $result;
}

/*
 * Add module to page in the Monitis Dashboard
 */
function monitis_add_page_module($vars, $page_id, $test_id) {
  // for now, default to inserting the content top-left
  // and, assume ping monitor
  list($ep, $ak, $sk) = monitis_extract_api_info($vars);
  $params['moduleName'] = 'External';
  $params['pageId'] = $page_id;
  $params['column'] = '1';
  $params['row'] = '1';
  $params['dataModuleId'] = $test_id;
  $result = monitis_post($ep, $ak, $sk, 'addPageModule', $params);
  return $result;
}

?>
