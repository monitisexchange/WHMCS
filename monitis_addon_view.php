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
function view_chart_highchart($series) {
  $series_strings = array();
  foreach ($series as $name => $data) {
    $series_tmp = '';
    $series_tmp .= "name: '$name',\n";
    $series_tmp .= "data: " . monitis_to_highchart($data) ;
    $series_strings[] = $series_tmp;
  }
  $series_html = implode($series_strings, "},\n{") . "\n";

  $html = <<<EOF
  <script src="http://new.monitis.com/libs/jquery/js/highcharts.js"></script>
  <script src="http://www.highcharts.com/js/themes/gray.js"></script>
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
            text: 'Monitis Ping Monitor'
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
  <script src="http://code.shutterstock.com/rickshaw/vendor/d3.min.js"></script>
  <script src="http://code.shutterstock.com/rickshaw/vendor/d3.layout.min.js"></script>
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
  $html = '';
  foreach ($success_msg as $msg) {
    $html .= '<div class="successbox">'.$msg.'</div>';
  }
  foreach ($error_msg as $msg) {
    $html .= '<div class="errorbox">'.$msg.'</div>';
  }
  return $html;
}

/*
 * Return HTML snippet for a table of WHMCS managed servers
 */
function view_server_table($vars) {
  $html = <<<EOF
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
  <thead><tr>\n
  <th></th>
  <th>Name</th>
  <th>Hostname</th>
  <th>IP Address</th>
  <th>NOC</th>
  <th>Max Accounts</th>
  <th>Type</th>
  <th>Active</th>
  <th>Disabled</th>
  <th>Monitored</th>
  </tr></thead>\n
  <tbody>\n
EOF;
  // No user input in query
  $query = "select s.name, s.hostname, s.ipaddress, s.noc, s.maxaccounts, "
         . "s.type, s.active, s.disabled, m.monitored, m.test_id "
         . "from tblservers s left outer join mod_monitis_server m on "
         . "m.ip_addr = s.ipaddress";
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr class='" . ($data['monitored']?"monitored":"unmonitored") . "'>\n";
    $html .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\""
        . $data['ipaddress'] . "\"/></td>\n";
    $html .= "<td>" . $data['name'] . "</td>\n";
    $html .= "<td>" . $data['hostname'] . "</td>\n"; 
    $html .= "<td>" . $data['ipaddress'] . "</td>\n";
    $html .= "<td>" . $data['noc'] . "</td>\n";
    $html .= "<td>" . $data['maxaccounts'] . "</td>\n";
    $html .= "<td>" . $data['type'] . "</td>\n";
    $html .= "<td>" . $data['active'] . "</td>\n";
    $html .= "<td>" . $data['disabled'] . "</td>\n";
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
EOF;
  return $html;
}

/*
 * Return HTML snippet listing servers managed by Monitis, but deleted from WHMCS
 */
function view_deleted_server_table($vars) {
  // find the monitors that don't correspond to servers, and remove them
  // remove both from mod_monitis_server and monitis API

  //$html = <<<EOF
  //<br/><h3>Monitors for removed Servers</h3>
    //<form method="post" action="{$vars['modulelink']}">
    //<div class='tablebg'>
      //<table class="datatable" width="100%" cellspacing="1" cellpadding="3" border="0">
      //<thead><tr>
        //<th></th>
        //<th>IP Address</th>
      //</tr></thead>
      //<tbody>
//EOF;
  $html = "";
  // No user input in query
  $query = 'select * from mod_monitis_server m where m.ip_addr not in (select ipaddress from tblservers)';
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr class='deleted'>\n";
    $html .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\""
        . $data['ip_addr'] . "\"/></td>\n";
    $html .= "<td></td><td></td><td>" . $data['ip_addr']. "</td>\n";
    $html .= "<td></td><td></td><td></td><td></td><td></td>";
    $html .= "<td align='center'><span class='textred'>DELETED</span></td>\n";

    $html .= "</tr>\n";
  }
  //$html .= <<<EOF
      //</tbody>
    //</table>
  //<button class='btn-primary' name='action' value='remove_deleted'>Remove from Monitis</button></td>
  //</div>
  //</form>
//EOF;
  return $html;
}

/*
 * Detail view of a particular server, primarily including a chart 
 * of recent Monitis ping monitor results.
 */
function view_detail($vars, $test_id) {
  // returns the string to be output
  // TODO: ensure that the length of each series is the same, truncate
  $return = '';
  $series = monitis_test_result_recent($vars, $test_id, 7200); // 2 hours

  $ip_addr = test_to_ip($test_id);
  $return .= "<h3>Ping Monitor Detail for $ip_addr</h3>\n";
  if ($series) {
    //$return .= view_chart_highchart($series) . view_chart($series);
    $return .= view_chart_highchart($series);
  }
  else {
    $return .= "<span class='textred'>No data available. Please allow several minutes " 
             . "for Monitis to collect initial results.</span><br />";
  }
  return $return;
}

?>
