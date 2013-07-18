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
				maxlength: 20,
				noSpace: true,
			},
			driveLetter: {
				minlength: 1,
				maxlength: 1,
			},
			tag: {
				required: true,
				noSpace: true,
			},
			freeLimit: {
				required: true,
				range: [1, 50],
			}/*,
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
			driveLetter: {
				required: "Please provide valid test drive letter",
				minlength: "Drive letter must be one character"
			},
			freeLimit: {
				range: "Free limit can be set from 1 to 10 GB."
			},
			tag: {
				required: "Tag name is required",
				noSpace: "Tag name can not contain spaces",
			}
			/*,
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
