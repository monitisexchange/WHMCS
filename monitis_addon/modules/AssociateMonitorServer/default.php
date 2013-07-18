<input type="button" class="button" id="m_AssociateMonitorServer_Trigger" value="Associate existing monitor with this server" />
<div id="m_AssociateMonitorServer_Content"></div>
<script type="text/javascript">
var m_AssociateMonitorServer = {
	serverID: <?php echo monitisGet('server_id') ?>,
	init: function() {
		var that = this;
		$("#m_AssociateMonitorServer_Trigger").click(function(){
			that.loadMonitorList();
			that.openDialog();
		});

		$("#m_AssociateMonitorServer_Content").dialog({
			title: "Associate with this server",
			width: 800,
			autoOpen: false,
			modal: true,
		});
	},
	loadMonitorList: function(type) {
		var params = {
				type: (typeof type == 'undefined') ? '' : type
				};
		this.load('monitorList', params,
				function() {
					monitisCheckAll('#m_AssociateMonitorServer_Table');

					$("#m_AssociateMonitorServer_Associate").click(function() {
						form = $(this).closest('form');
						form.find('input[name="module_AssociateMonitorServer_action"]').val('associate');
						form.submit();
					});
					$("#m_AssociateMonitorServer_Unassociate").click(function() {
						form = $(this).closest('form');
						form.find('input[name="module_AssociateMonitorServer_action"]').val('unAssociate');
						//form.attr('action', window.location);
						form.submit();
					});
				}
		);
	},
	load: function(actionName, params, callback) {
		$("#m_AssociateMonitorServer_Content").prepend("<div class='monitisOverlay'></div><div class='monitisLoader'></div>");
		
		if (typeof params == 'undefined')
			params = {};
		params.module_AssociateMonitorServer_action = actionName;
		
		var url = "<?php echo MONITIS_APP_URL; ?>&monitis_module=AssociateMonitorServer&server_id="
			+ this.serverID; 
			
		$.post(url, params, function(data) {
			$('#m_AssociateMonitorServer_Content').html($(data).find('monitis_data').html());

			if (callback instanceof Function)
				callback();
		});
	},
	openDialog: function() {
		$("#m_AssociateMonitorServer_Content").dialog( "open" );
	}
};

$(document).ready(function(){
	m_AssociateMonitorServer.init();
});
</script>