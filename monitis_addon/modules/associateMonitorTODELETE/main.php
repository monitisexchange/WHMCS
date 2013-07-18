<input type="button" class="button" id="m_associateMonitor_trigger" value="Associate existing monitor with this server" />
<div style="display:none;" id="m_associateMonitorContent"></div>
<script type="text/javascript">
var m_associateMonitor = {
	init: function() {
		var that = this;
		$("#m_associateMonitor_trigger").click(function(){
			that.monitorList();
		});

		$("#m_associateMonitorContent").dialog({
			title: "Associate with this server",
			width: 800,
			autoOpen: false,
			modal: true,
		});
	},
	monitorList: function() {
		$("#m_associateMonitorContent").dialog( "open" );
	}
};

$(document).ready(function(){
	m_associateMonitor.init();
});
</script>