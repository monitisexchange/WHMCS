<?php
//echo "Module ---- modules/CreateMonitorServer/default.php <br>"; 
	if ($this->renderTrigger) { ?>
	<?php if ($this->linkType == 'button') { ?>
		<input type="button" class="m_CreateMonitorServer_Trigger"
			serverID="<?php echo $this->serverID; ?>" 
			serverName="<?php echo $this->serverName; ?>" 
                        
			value="<?php echo $this->linkText; ?>" />
	<?php } elseif ($this->linkType == 'anchor') { ?>
		<a href="#" class="m_CreateMonitorServer_Trigger">
			<?php echo $this->linkText; ?>
		</a>
	<?php } ?>
<?php } ?>
<div id="m_CreateMonitorServer_Content"></div>
<script type="text/javascript">
var m_CreateMonitorServer = {
	serverID: <?php echo $serverID; ?>,
	editMode: '<?php echo $this->editMode; ?>',
	isAgent: <?php echo $this->isAgent; ?>,
	<?php if( isset($this->agentId) ) echo 'agentId:'.$this->agentId.','; ?>
	<?php if( isset($this->agentId) ) echo 'agentKey:"'.$this->agentKey.'",'; ?>
	//drivesList: '<?php /*echo $this->drivesList;*/ ?>',
	init: function() {
		//var that = this;
		$(".m_CreateMonitorServer_Trigger").click(function() {
			m_CreateMonitorServer.trigger();
		});

		$("#m_CreateMonitorServer_Content").dialog({
			title: "Create new monitor",
			width: 500,
			autoOpen: false,
			modal: true,
		});
	},
	trigger: function(monitorID, monitorType) {
//console.log('trigger **** monitorID = ' + monitorID + '; monitorType = '+monitorType + '; alertGroupId = '+alertGroupId );
		if (typeof monitorID != 'undefined' && typeof monitorType != 'undefined') {
			this.loadCreateForm(monitorType, monitorID);
		} else {
			this.loadCreateForm('ping');
		}
		this.openDialog();
	},
	loadMessagBox: function( monitorID, monitorType ) {
//console.log('loadMessagBox **** monitorID = ' + monitorID + '; monitorType = '+monitorType);
		this.width = 500;
		var that = this;
		//$("#m_CreateMonitorServer_Content").attr('title', 'Message box');
		
		var params = { module_CreateMonitorServer_monitorID:monitorID, module_CreateMonitorServer_monitorType:monitorType };
		
		this.load('messageBox', params, function() {
				$.getScript("../modules/addons/monitis_addon/modules/CreateMonitorServer/static/js/messageBox.js",
						function(data, textStatus, jqxhr) {
							var form = $("#m_CreateMonitorServer_Content").find("form").first();
							m_CreateMonitorServer_Validator(form);
						}
				);
				var form = $("#m_CreateMonitorServer_Content").find("form").first();
				if (typeof monitorID != 'undefined') {
					$(form).find('input[name="module_CreateMonitorServer_monitorID"]').val(monitorID);
				} else {
					$(form).find('input[name="module_CreateMonitorServer_monitorID"]').val(0);
				}
				
				initMonitisMultiselect('#m_CreateMonitorServer_Content');
			}
		);
		this.openDialog();
	},
	loadCreateForm: function(type, monitorID, alertGroupId) {
//console.log('loadCreateForm **** monitorID = ' + monitorID + '; type = '+type);
		type = type.charAt(0).toUpperCase() + type.slice(1);
		var params = {};
		if (typeof monitorID != 'undefined')
			params.module_CreateMonitorServer_monitorID = monitorID;
                        params.module_CreateMonitorServer_alertGroupId = alertGroupId;
		var that = this;
		this.load('createForm' + type, params, function() {
				$.getScript("../modules/addons/monitis_addon/modules/CreateMonitorServer/static/js/createForm" + type + ".js",
						function(data, textStatus, jqxhr) {
							var form = $("#m_CreateMonitorServer_Content").find("form").first();
							m_CreateMonitorServer_Validator(form);
						}
				);

				var form = $("#m_CreateMonitorServer_Content").find("form").first();
				if (typeof monitorID != 'undefined') {
					$(form).find('input[name="module_CreateMonitorServer_monitorID"]').val(monitorID);
                                        $(form).find('input[name="module_CreateMonitorServer_alertGroupId"]').val(alertGroupId);
				} else {
					$(form).find('input[name="module_CreateMonitorServer_monitorID"]').val(0);
				}
				
				initMonitisMultiselect('#m_CreateMonitorServer_Content');
			}
		);
	},
	loadCreateDriveForm: function(type, letterIndex) {
//console.log('loadCreateDriveForm **** letterIndex = ' + letterIndex + '; type = '+type);
		type = type.charAt(0).toUpperCase() + type.slice(1);
		var params = {};
		if (typeof letterIndex != 'undefined')
			params.module_CreateMonitorServer_letterIndex = letterIndex;
		else 
			params.module_CreateMonitorServer_letterIndex = 0;
		var that = this;
		this.load('createForm' + type, params, function() {
				$.getScript("../modules/addons/monitis_addon/modules/CreateMonitorServer/static/js/createForm" + type + ".js",
						function(data, textStatus, jqxhr) {
							var form = $("#m_CreateMonitorServer_Content").find("form").first();
							m_CreateMonitorServer_Validator(form);
						}
				);

				var form = $("#m_CreateMonitorServer_Content").find("form").first();
				if (typeof letterIndex != 'undefined') {
					$(form).find('input[name="module_CreateMonitorServer_letterIndex"]').val(letterIndex);
				} else {
					$(form).find('input[name="module_CreateMonitorServer_letterIndex"]').val(0);
				}
				
				initMonitisMultiselect('#m_CreateMonitorServer_Content');
			}
		);
	},
	load: function(actionName, params, callback) {
//console.log('load **** actionName = ' + actionName);
		$("#m_CreateMonitorServer_Content").prepend("<div class='monitisOverlay'></div><div class='monitisLoader'></div>");
		
		if (typeof params == 'undefined')
			params = {};
		params.module_CreateMonitorServer_action = actionName;
		
		var url = "<?php echo MONITIS_APP_URL; ?>&monitis_module=CreateMonitorServer&server_id=" + this.serverID+
		"&editMode="+this.editMode+"&isAgent="+this.isAgent;
		
//console.log('load **** url = ' + url);

		if( this.agentId && typeof this.agentId != 'undefined') url += '&agentId='+this.agentId;
		if( this.agentKey && typeof this.agentKey != 'undefined') url += '&agentKey='+this.agentKey;
		//if( this.drivesList) url += '&drivesList='+this.drivesList;
		
		$.post(url, params, function(data) {
			$('#m_CreateMonitorServer_Content').html($(data).find('monitis_data').html());

			if (callback instanceof Function)
				callback();
		});
	},
	openDialog: function() {
		$("#m_CreateMonitorServer_Content").dialog( "open" );
	},
/*	submitForm: function(id) {
		var form = $("#m_CreateMonitorServer_Content").find("form#"+id).first();
		if (form.valid())
			form.submit();
	}	*/
	submitForm: function() {
		var form = $("#m_CreateMonitorServer_Content").find("form").first();
		if (form.valid())
			form.submit();
	}
};

 
$(document).ready(function(){
	m_CreateMonitorServer.init();
        
  
});
</script>