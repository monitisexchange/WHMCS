<?php

/* 
 * Views for components of Monitis WHMCS addon module.  Functions here
 * emit snippets of HTML rendered to the browser in monitis_addon.php
 */

/*
 * Format monitis results as series data for an HTML Rickshaw chart
 */
function monitis_to_rickshaw($data) {
  // in: monitis time series data from parsed json
  // out: rickshaw-formatted time series data as json
  $r_substrings = array();
  foreach ($data as $point) {
    list($time, $value, $ok) = $point;
    // ignore the $ok for now
    $r_time = $time;
    $r_substrings[] = "{ x: " . $r_time . ", y: " . $value . " }";
  }
  return "[ " . implode(", ", $r_substrings) . " ]";
}

/*
 * Format monitis results as series data for an HTML HighCharts chart
 */
function monitis_to_highchart($data) {
  // in: monitis time series data from parsed json
  // out: highcharts formatted time series data as json
  $r_substrings = array();
  foreach ($data as $point) {
    list($time, $value, $ok) = $point;
    // ignore the $ok for now
    //$r_time = strtotime($time)*1000 ;
    $r_time = $time*1000 ;
    $r_substrings[] = "[" . $r_time . "," . $value . "]";
  }
  return "[ " . implode(", ", $r_substrings) . " ]";
}

/*
 * Format monitis results in an HTML HighCharts chart
 */
function view_chart_highchart($ip, $series) {
  $series_strings = array();
  foreach ($series as $name => $data) {
    $series_tmp = '';
    $series_tmp .= "name: '$name',\n";
    $series_tmp .= "data: " . monitis_to_highchart($data) ;
    $series_strings[] = $series_tmp;
  }
  $series_html = implode($series_strings, "},\n{") . "\n";

  $html = <<<EOF
  <script src="../modules/addons/monitis_addon/js/vendor/highcharts.js"></script>
  <script src="../modules/addons/monitis_addon/js/vendor/gray.js"></script>
<div id="highcharts_container" style="width: 100%; height: 400px"></div>
<script>
var chart1; // globally available
$(document).ready(function() {
      chart1 = new Highcharts.Chart({
         chart: {
            renderTo: 'highcharts_container',
            type: 'spline'
         },
         title: {
            text: 'Ping response for $ip'
         },
         xAxis: {
            type: 'datetime',
            staggerLines: 2
         },
         yAxis: {
            min: 0,
            title: {
               text: 'Response time (ms)'
            }
         },
        plotOptions: {
          series: {

            marker: {
              radius: 4,
              symbol: "circle",
              enabled: false,
              states: {
                hover: {
                  enabled: true
                }
              }
            }
          }
        },
         series: [{ $series_html }]
      });
   });
</script>
EOF;
  return $html;
}

/*
 * Format monitis results in an HTML Rickshaw chart
 */
function view_chart($series) {
  // in: series, an array of datasets in the format expected by rickshaw
  //     i.e., "[ { x: 0, y: 40 }, { x: 1, y: 49 }, ...{ x: 4, y: 16 } ]"

  // loop over series
  $series_strings = array();
  foreach ($series as $name => $data) {
    $series_tmp = '';
    $series_tmp .= "{\n data: " . monitis_to_rickshaw($data) . ",\n";
    $series_tmp .= "name: '$name',\n";
    $series_tmp .= "padding: {top: '0.15', left: '0.15', right: '0.15', bottom: '15'},\n";
    $series_tmp .= "color: palette.color()\n}";
    $series_strings[] = $series_tmp;
  }
  $series_html = implode($series_strings, ",\n") . "\n";
  $html = <<<EOF
  <link type="text/css" rel="stylesheet" href="http://code.shutterstock.com/rickshaw/rickshaw.min.css">
  <script src="../modules/addons/monitis_addon/js/vendor/d3.v3.min.js"></script>
  <script src="../modules/addons/monitis_addon/js/vendor/d3.layout.min.js"></script>
  <script src="http://code.shutterstock.com/rickshaw/rickshaw.min.js"></script>
  <style>
    #chart_container { display: inline-block; font-family: Arial, Helvetica, sans-serif; }
    #chart { float: left; }
    #legend { float: left; margin-left: 15px; }
    #offset_form { float: left; margin: 2em 0 0 15px; font-size: 13px; }
    #y_axis { float: left; width: 40px; }
  </style>
  <div id="chart_container">
    <div id="y_axis"></div>
    <div id="chart"></div>
    <div id="legend"></div>
  </div>
  <script>
    var palette = new Rickshaw.Color.Palette();
    var series = [ $series_html ];
    Rickshaw.Series.zeroFill(series);
    var graph = new Rickshaw.Graph({
      element: document.querySelector("#chart"),
      width: 760,
      height: 400,
      stroke: true,
      renderer: 'scatterplot',
      series: series 
    });
    //graph.renderer.unstack = true;
    var x_axis = new Rickshaw.Graph.Axis.Time( { graph: graph } );
    var y_axis = new Rickshaw.Graph.Axis.Y( {
      graph: graph, orientation: 'left', tickFormat: Rickshaw.Fixtures.Number.formatKMBT,
      element: document.getElementById('y_axis'), } );
    var legend = new Rickshaw.Graph.Legend( {
      element: document.querySelector('#legend'), graph: graph } );
    var hoverDetail = new Rickshaw.Graph.HoverDetail( { graph: graph } );
    var shelving = new Rickshaw.Graph.Behavior.Series.Toggle({ graph: graph, legend: legend });
    var order = new Rickshaw.Graph.Behavior.Series.Order({ graph: graph, legend: legend });
    var highlighter = new Rickshaw.Graph.Behavior.Series.Highlight({ graph: graph, legend: legend });

    graph.render();
  </script> 
EOF;
  return $html;
}

/*
 * Format status messages as WHMCS error/succcess message DIVs
 */
function view_status_messages($success_msg, $error_msg) {
  // If any of the actions set a message, display it
  // TODO print separate box per message (params should be array)
  $html = '<div id="status_messages"><div></div>';
  foreach ($success_msg as $msg) {
    $html .= '<div class="successbox">'.$msg.'</div>';
  }
  foreach ($error_msg as $msg) {
    $html .= '<div class="errorbox">'.$msg.'</div>';
  }
  $html .= '</div>';
  return $html;
}

/*
 * Save updates to database
 * Return is irrevelant, as these should be called anync, even though
 * they may come in via the addon_output() methods which will
 * still return some HTML (to a client that should ignore it)
 * 
 * These are the things we must do within the restrictions of the
 * WHMCS addon toolset.
 */
function update_agent_name($vars, $agent_name, $server_ip) {
  $name = filter_var($agent_name, FILTER_SANITIZE_STRING);
  $ip = filter_var($server_ip, FILTER_VALIDATE_IP);
  $response_html = '';
  debug("In update_agent_name", array('name' => $name, 'ip' => $ip, 'vars' => $vars));

  // ensure that the provided name is the name of an existing agent
  debug("Check that $name is in agent names", agent_names($vars));
  if (! in_array($name, agent_names($vars))) {
    return "<div id='jse_response'>No agent named $name</div>";
  }

  if ($name && $ip ) {
    // check if exists, and update
    // otherwise, create new entry
    $result = select_query('mod_monitis_server', 'ip_addr',
      array("ip_addr" => $ip));
    $data = mysql_fetch_array($result);
    if ($data) {
      update_query('mod_monitis_server',
        array("agent_name" => $name),
        array("ip_addr" => $ip));
    }
    else {
      insert_query('mod_monitis_server',
        array("agent_name" => $name, "ip_addr" => $ip));
    }
    $response_html = ""; // no need to return anything on success
  }
  else {
    $response_html = "<div id='jse_response'>FAILURE</div>";
  }
  return $response_html;
}

/*
 * Return HTML snippet for a table of WHMCS managed servers
 */
function view_server_table($vars) {

  $snapshot = external_snapshot($vars);
  $html = <<<EOF
<script src="../modules/addons/monitis_addon/js/vendor/jquery.jeditable.js"></script>
<script>
function select_change(sel) {
  checkboxes = document.getElementsByName('servers[]');
  len = checkboxes.length;
  for (var i = 0; i < len; i++) {
    cn = checkboxes[i].parentNode.parentNode.className;
    checkboxes[i].checked = (cn == sel.value || sel.value == "all");
  }
};
</script>
<br/><h3>Servers</h3>
  <form method="post" action="{$vars['modulelink']}">\n
  <div class='tablebg'>
  Select: <select id='monitors' onchange="select_change(this)" >
    <option value=""></option>
    <option value="none">None</option>
    <option value="monitored">Monitored</option>
    <option value="unmonitored">Unmonitored</option>
    <option value="deleted">Deleted</option>
    <option value="all">All</option>
  </select>
  <table class="datatable" width="100%" cellspacing="1" cellpadding="3" border="0">
  <thead><tr>
  <th></th>
  <th>Name</th>
  <th>Hostname</th>
  <th>IP Address</th>
  <th>NOC</th>
  <th>Max Accounts</th>
  <th>Type</th>
  <th>Active</th>
  <th>Disabled</th>
  <th>Status</th>
  <th>Agent Name</th>
  <th>Monitored</th>
  </tr></thead>
  <tbody>
EOF;
  // No user input in query
  $query = "select s.name, s.hostname, s.ipaddress, s.noc, s.maxaccounts, "
         . "s.type, s.active, s.disabled, m.monitored, m.test_id, m.agent_name "
         . "from tblservers s left outer join mod_monitis_server m on "
         . "m.ip_addr = s.ipaddress";
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr class='" . ($data['monitored']?"monitored":"unmonitored") . "'>\n";
    $html .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\""
        . $data['ipaddress'] . "\"/></td>\n";
    $test_id = $data['test_id'];
    $status = $snapshot['status'][$test_id];
    if ($status == 'OK') {
      $status_html = '<img src="images/success.png" title="OK" alt="OK">';
    }
    else {
      $status_html = '<img src="images/error.png" title="NOK" alt="NOK">';
    }
    $html .= <<<EOF
      <td>{$data['name']}</td>
      <td>{$data['hostname']}</td>
      <td>{$data['ipaddress']}</td>
      <td>{$data['noc']}</td>
      <td>{$data['maxaccounts']}</td>
      <td>{$data['type']}</td>
      <td>{$data['active']}</td>
      <td>{$data['disabled']}</td>
      <td>$status_html</td>
      <td><div class="edit" id="{$data['ipaddress']}">{$data['agent_name']}</div></td>
EOF;
    if ($data['monitored']) {
      $html .= '<td><span style="display: block; text-align: center">'
        . '<span class="textgreen">YES</span>' . "\n"
        . '<br/><a href="' . $vars['modulelink'] . '&view=detail&test_id=' . $data['test_id'] 
        . '" >details</a></span></td>' . "\n";
    }
    else {
      $html .= "<td align='center'><span class='textred'>NO</span></td>\n";
    }
    $html .= "</tr>\n";
  }
  $html .= view_deleted_server_table($vars);
  $html .= <<<EOF
  </tbody>\n
  </table>\n
  <button class='btn-primary' name='action' value='add'>Add to Monitis</button></td>\n
  <button class='btn-primary' name='action' value='remove'>Remove from Monitis</button></td>\n
  </div>\n
  </form>\n
<script>

$(document).ready(function() {
  $('.edit').editable(function(value, settings) {
    msg = "test";
    console.log("Editable called");
    $.post('{$vars['modulelink']}', {action: "agent_name", ip: this.id, agent_name: value, prev_name: this.parentElement.innerHTML})
      .done(function(response) {
        //console.log(this, value, settings);
        match = response.match(/<div id='jse_response'>([^<]*)<\/div>/);
        if (match) {
          msg = match[1];
          $('#status_messages').children().replaceWith('<div class="errorbox">'.concat(msg,'</div>'));
        }
        else {
          $('#status_messages').children().replaceWith('<div></div>');
          msg = 'value okay';
        }
        console.log("inside", msg);
      });
    console.log("outside", msg);
    return value;
  },{
    indicator : "Saving...",
    tooltip   : "Click to edit...",
  });
});

</script>
EOF;
  return $html;
}

/*
 * Return HTML snippet listing servers managed by Monitis, but deleted from WHMCS
 */
function view_deleted_server_table($vars) {
  // find the monitors that don't correspond to servers, and remove them
  // remove both from mod_monitis_server and monitis API

  $html = "";
  // No user input in query
  $query = 'select * from mod_monitis_server m where m.ip_addr not in (select ipaddress from tblservers)';
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr class='deleted'>\n";
    $html .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\""
        . $data['ip_addr'] . "\"/></td>\n";
    $html .= "<td></td><td></td><td>" . $data['ip_addr']. "</td>\n";
    $html .= "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
    $html .= "<td align='center'><span class='textred'>DELETED</span></td>\n";

    $html .= "</tr>\n";
  }
//EOF;
  return $html;
}

/*
 * Detail view of a particular server, primarily including a chart 
 * of recent Monitis ping monitor results.
 */
function view_detail($vars, $test_id) {
  // returns the string to be output
  $return = '';
  $series = monitis_test_result_recent($vars, $test_id, 7200); // 2 hours
  $series_count = count($series);
  debug("view_detail: retreived {$series_count} series to chart", array_keys($series));

  $ip_addr = test_to_ip($test_id);
  //$return .= "<h3>Ping Monitor Detail for $ip_addr</h3>\n";
  if ($series) {
    //$return .= view_chart_highchart($series) . view_chart($series);
    $return .= view_chart_highchart($ip_addr, $series);
  }
  else {
    $return .= "<span class='textred'>No data available. Please allow several minutes " 
             . "for Monitis to collect initial results.</span><br />";
  }
  return $return;
}

?>
