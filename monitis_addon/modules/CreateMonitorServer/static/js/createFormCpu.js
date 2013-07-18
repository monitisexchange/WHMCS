function setDisplay( platform) {
	var value = 'none';
	if(platform == 'LINUX') value = '';
	document.getElementById('idleMin_id').style.display = value;
	document.getElementById('ioWaitMax_id').style.display = value;
	document.getElementById('ioWaitMax_id').style.display = value;
}

m_CreateMonitorServer_Validator = function(jqForm) {
	jqForm.validate({
		errorElement: "div",
		errorClass: "monitisFormError",
		errorPlacement: function(error, element) {
		      element.parent().append(error);
		},
		rules: {
			name: {
				required: true,
				minlength: 3,
				maxlength: 50,
				noSpace: true,
			},
			url: {
				required: true,
				urlNoProto: true,
			},
			timeout: {
				required: true,
				range: [1, 5000],
			},
			tag: {
				required: true,
				noSpace: true,
			}
			/*,
			uptimeSLA: {
				number: true,
				range: [1, 100],
			},
			responseSLA: {
				number: true,
				min: 1,
			},*/
		},
		messages: {
			name: {
				required: "Please provide monitor name",
				noSpace: "Monitor name can not contain spaces",
				minlength: "Monitor name must be minimum 3 character long",
				maxlength: "Monitor name can be maximum 20 characters"
			},
			url: {
				required: "Please provide valid test url",
				urlNoProto: "Url must not contain protocol"
			},
			timeout: {
				range: "Timeout can be set from 1 to 5000 miliseconds."
			},
			tag: {
				required: "Tag name is required",
				noSpace: "Tag name can not contain spaces",
			}/*,
			uptimeSLA: {
				number: "Please provide numeric value",
				range: "Uptime SLA can be from 1% to 100%",
			},
			responseSLA: {
				number: "Please provide numeric value",
				min: "Please provide positive number",
			},*/
		}
	});
};
