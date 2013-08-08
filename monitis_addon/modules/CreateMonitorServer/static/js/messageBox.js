m_CreateMonitorServer_Validator = function(jqForm) {
	jqForm.validate({
		errorElement: "div",
		errorClass: "monitisFormError",
		errorPlacement: function(error, element) {
		      element.parent().append(error);
		},
		rules: {

		},
		messages: {

		}
	});
};