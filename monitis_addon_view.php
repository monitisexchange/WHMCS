<?php

function monitis_to_rickshaw($data) {
  // in: monitis time series data from parsed json
  // out: rickshaw-formatted time series data as json
  $r_substrings = array();
  foreach ($data as $point) {
    list($time, $value, $ok) = $point;
    // ignore the $ok for now
    $r_time = strtotime($time);
    $r_substrings[] = "{ x: " . $r_time . ", y: " . $value . " }";
  }
  return "[ " . implode(", ", $r_substrings) . " ]";
}

function view_chart($series) {
  // in: series, an array of datasets in the format expected by rickshaw
  //     i.e., "[ { x: 0, y: 40 }, { x: 1, y: 49 }, ...{ x: 4, y: 16 } ]"

  // loop over series
  $series_strings = array();
  foreach ($series as $name => $data) {
    $series_tmp = '';
    $series_tmp .= "{\n data: " . $data . ",\n";
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

//function print_server_table($vars) {
//  $html .= view_server_table($vars);
//}

function view_server_table($vars) {
  $html = <<<EOF
<br/><h3>Servers</h3>
  <form method="post" action="{$vars['modulelink']}">\n
  <div class='tablebg'>
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
  $query = "select s.name, s.hostname, s.ipaddress, s.noc, s.maxaccounts, "
         . "s.type, s.active, s.disabled, m.monitored, m.test_id "
         . "from tblservers s left outer join mod_monitis_server m on "
         . "m.ip_addr = s.ipaddress";
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr>\n";
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

//function print_deleted_server_table($vars) {
//  print view_deleted_server_table($vars);
//}

function view_deleted_server_table($vars) {
  // find the monitors that don't correspond to servers, and remove them
  // remove both from mod_monitis_server and monitis API

  $html = <<<EOF
  <br/><h3>Monitors for removed Servers</h3>
  <form method="post" action="{$vars['modulelink']}">\n
  <div class='tablebg'>
  <table class="datatable" width="100%" cellspacing="1" cellpadding="3" border="0">\n
  <thead><tr>\n
  <th></th>
  <th>IP Address</th>
  </tr></thead>\n
  <tbody>\n
EOF;
  $query = 'select * from mod_monitis_server m where m.ip_addr not in (select ipaddress from tblservers)';
  $result = mysql_query($query);
  while  ($data = mysql_fetch_array($result)) {
    $html .= "<tr>\n";
    $html .= "<td><input type=\"checkbox\" name=\"servers[]\" value=\""
        . $data['ip_addr'] . "\"/></td>\n";
    $html .= "<td>" . $data['ip_addr']. "</td>\n";

    $html .= "</tr>\n";
  }
  $html .= <<<EOF
  </tbody>\n</table>\n
  <button class='btn-primary' name='action' value='remove_deleted'>Remove from Monitis</button></td>\n
  </div>\n
  </form>
EOF;
  return $html;
}

function view_detail($vars, $test_id) {
  // returns the string to be output
  // TODO: ensure that the length of each series is the same, truncate
  $return = '';
  $result = monitis_test_result($vars, $test_id);
  $series = array();
  foreach ($result as $loc) {
    $name = $loc['locationName'];
    $series[$name] = monitis_to_rickshaw($loc['data']);
  }
  // truncate any longer arrays
  // matching lengths currently handled via zeroFill
  //$lengths = array_map(count, $series);
  //$shortest_length = min($lengths);

  $ip_addr = test_to_ip($test_id);
  $return .= "<h3>Ping Monitor Detail for $ip_addr</h3>\n";
  if ($series) {
    $return .= view_chart($series);
  }
  else {
    $return .= "<span class='textred'>No data available. Please allow several minutes " 
             . "for Monitis to collect initial results.</span><br />";
  }
  return $return;
}

?>
