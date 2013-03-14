
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
