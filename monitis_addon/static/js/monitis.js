function monitisCheckAll(jqSelector) {
	$(jqSelector).find(".monitis-checkbox-all").click(function() {
		elements = $('.monitis-checkbox');
		elements.attr('checked', this.checked?true:false);
		$('.monitis-checkbox-subject').attr('disabled', $('.monitis-checkbox:checked').length?false:true);
	});
	$(jqSelector).find('.monitis-checkbox').click(function() {
		$('.monitis-checkbox-subject').attr('disabled', $('.monitis-checkbox:checked').length?false:true);
	});
}

function initSingleCheckbox(jqSelector) {
	elements = $(jqSelector).find('tr td input.monitisSingleCheckbox');
	elements.each(function(index, elem) {
		$(elem).change(function() {
			$(elem).closest('table').find('input.monitisSingleCheckbox').removeAttr('checked');
			$(elem).attr('checked', 'checked');
		});
	});
}

function initMonitisNotifications(jqSelector) {
//.monitis-message .close
	$(jqSelector).find(".notification_x").each(function(){
		$(this).click(function() {
			$(this).parent().fadeOut(300);
		});
	});
}

function initMonitisMultiselect(jqSelector) {
	$(jqSelector).find(".monitisMultiselect").each(function(index, container) {
		var dialog = $(container).find(".monitisMultiselectDialog").dialog({
			width: 600,
			autoOpen: false,
	    	modal: true,
	    	buttons: {
	    		'Select': {
	    			text: 'Save',
	    			class: 'btn',
	    			click: function() {
	    				$( this ).dialog( "close" );
	    			},
	    		}
	    	},
			close: function() {
				updateInput();
			}
	    });
		$(container).find(".monitisMultiselectTrigger").click(function(){
			dialog.dialog('open');
		});
		
		var textFormat = $(container).find(".monitisMultiselectText").html();
		function updateInput() {
			var selectedCount = 0;
			var inputContainer = $(container).find(".monitisMultiselectInputs");
			inputContainer.empty();
			$(dialog).find('input[type="checkbox"]').each(function() {
				if ($(this).is(':checked')) {
					selectedCount++;
					inputContainer.append('<input type="hidden" name="' + inputContainer.attr('inputName') + '" value="' + $(this).val() + '" />');
				}
			});
			$(container).find(".monitisMultiselectText").html(textFormat.replace("{count}", selectedCount));
		}
		
		function init() {
			var inputContainer = $(container).find(".monitisMultiselectInputs");
			inputContainer.find('input').each(function(index, elem) {
				$(dialog).find('input[type="checkbox"]').each(function(i, e) {
					if ($(e).val() == $(elem).val())
						$(e).attr('checked', 'checked');
				});
			});
		};
		
		init();
		updateInput();
		
	});
}

//init page
$(document).ready(function() {
	initMonitisNotifications('body');
	initMonitisMultiselect('body');
	monitisCheckAll('body');

	// jquery validation
	$.validator.addMethod("noSpace", function(value, element) { 
		  return value.indexOf(" ") < 0 && value != ""; 
		}, "No space please and don't leave it empty");
	$.validator.addMethod("urlNoProto", function(value, element) {
			return value.indexOf("://") < 0; 
		}, "This field must not contain protocol");
});

// Timezone offset
var d = new Date();
var n = d.getTimezoneOffset(); // minutes
//var monitisTZOffset = n * 60 * 1000; // microseconds
var monitisTZOffset = -n; // microseconds  (n * 1000);


