///////////////////////////////////////////////////////////////////////////////////////

function monitisNotificationRuleClass( settings, groupObj, callback ) {
	var noalert = 'no alert';
	var NotificationTitles = {
		'always':'24/7',
		'specifiedTime':'Everyday',
		'specifiedDays':'Specific days only'
	};
	var weekDays = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
	var hour = [];
	var minute = [];
	
	var sets = settings;
	if( typeof settings == 'string' ) {
		var str = settings.replace(/~/g, '"');
		sets = JSON.parse(str);
//console.log(sets);
	}
	
	function add0(s){ var str=""+s; if(str.length == 1) return "0"+s; else	return ""+s; }
	
	(function(){
		for(var i=0; i<24; i++) { hour[hour.length] = add0( i); }
		for(var i=0; i<60; i=i+15) { minute[minute.length] = add0( i); }
		
		var str = '<style>'+
		'.monitis_dialog_content h4 { font-weight:normal;padding-left:20px;}'+
	//	'.monitis_dialog_content select, .monitis_dialog_content option { color:#006699; }'+
		'.monitis_dialog_content ul { margin-left:0px;padding-left:20px;}'+
		'.monitis_dialog_content li { line-height:25px;}'+
		'.monitis_dialog_content hr { margin:10px 0px;width:90%}'+
		'.monitis_dialog_content .monitis_schedule {width:140px;float:left;}'+
		'.monitis_dialog_content #scheduleContent {width:260px;float:left;height:70px;border:dotted 0px red;}'+
		'.monitis_dialog_content #scheduleContent div {text-align:right;}'+
		'.monitis_dialog_content #scheduleContent select {width:60px;}'+
		'.monitis_dialog_content select.contactgroup {width:160px;}'+
		'</style>';
		str += '<form id="notificationRuleForm"><div class="monitis_dialog_content" title="Notification rule">';	// <h4>&nbsp;</h4>
		
		str += '<ul class="monitis_notification_options">'
		
		if( groupObj && groupObj.list ){
			var groups = groupObj.list;
			str += '<li><lable>Select contact group to alert:</lable> <select class="contactgroup">';
			str += '<option value="0"> '+noalert+' </option>';
			for(var i=0; i<groups.length; i++) {
				var sel = '';
				if( parseInt(groupObj['id']) == parseInt(groups[i]['id']) ) sel = 'selected';
				str += '<option value="'+groups[i]['id']+'" '+sel+'>'+groups[i]['name']+'</option>';
			}
			str += '</select></li>';
		}
		
		str += '<li><lable>Alert upon</lable> <select name="failureCount" class="fwDefault">';
		for(var i=1; i<6; i++) {
			var selected = ( sets.failureCount == i ) ? 'selected' : '';
			str += '<option value='+i+' '+selected+'>'+i+'</option>';
		}
		str += '</select> <lable> failure/s</lable></li>';
		
		str += '<li><lable> <hr /></lable></li>';
		
		var checked = ( sets.notifyBackup > 0 ) ? 'checked' : '';
		str += '<li><input type="checkbox" name="notifyBackup" '+checked+' /> <lable>Alert when fixed</lable></li>'
		checked = ( sets.continuousAlerts > 0 ) ? 'checked' : '';
		str += '<li><input type="checkbox" name="continuousAlerts" '+checked+' /> <lable>Receive continuous alerts</lable></li>';
		str += '<li><lable> <hr /></lable></li></ul>';

		var period = sets.period;
		str += '<div style="width:100%;"><h4>Alerting schedule: </h4>'+
		'<ul class="monitis_schedule">';
		for( var i in period ) {
			var item = period[i];
			checked = '';
			if( item.value > 0 ) checked = 'checked';

			var title = NotificationTitles[i];
			str += '<li><input type="radio" name="period" '+checked+' value="'+i+'" /> <lable>'+title+'</lable></li>';
		}
		str += '</ul><p id="scheduleContent"></p></div>'+
		'</div></form>'
		$('#monitis_notification_dialog_div').html(str);
	
	})();

	// failureCount
	$('.monitis_notification_options select[name=failureCount]').change(function(){
		sets.failureCount = parseInt($(this).val());
	});
	
	$('.monitis_notification_options input[type=checkbox]').change(function(){
		var name = $(this).attr('name');
		if( $(this).attr('checked') ) {
			sets[name] = 1;
		} else {
			sets[name] = 0;
		}
		//sets.failureCount = parseInt($(this).val());
	});
	
	function timeStr( time, name, period ) {
		var t = time.split(":");
		var s = '<select name="hour'+name+'" period="'+period+'">'
		for(var i=0; i<hour.length; i++){
			var sel = '';
			if( hour[i] == t[0]) sel='selected';
			s+='<option value="'+hour[i]+'" '+sel+'>'+hour[i]+'</option>';
		}
		s+='</select> : ';
		s += ' <select name="minute'+name+'" period="'+period+'">'
		for(var i=0; i<minute.length; i++){
			var sel = '';
			if( minute[i] == t[1]) sel='selected';
			s+='<option value="'+minute[i]+'" '+sel+'>'+minute[i]+'</option>';
		}
		s+='</select>';
		return s;
	}
	
	function weekdayStr(weekday, name, period) {
		var day = parseInt(weekday);
		var s = '<select name="weekday'+name+'" period="'+period+'">'
		for(var i=0; i<weekDays.length; i++){
			var sel = '';
			if( i == (day-1)) sel='selected';
			s+='<option value="'+(i+1)+'" '+sel+'>'+weekDays[i]+'</option>';
		}
		s+='</select>';
		return s;
	}
	
	function update() {
		$('.monitis_schedule input[type=radio]').each(function( index ) {
			var curr = null;
			if( $(this).attr('checked') ) {
				// reset period
				for( var i in sets.period ) {
					sets.period[i].value = 0;
				}
				var type = $(this).val();
				curr = sets.period[type];
				var str = '';
				if( type == 'specifiedTime'  ) {
					str = '<div>';
					str += '<div>From: '+timeStr( curr.params.timeFrom, 'From', type )+' GMT</div> ';
					str += '<div>To: '+timeStr( curr.params.timeTo, 'To', type )+' GMT</div>';
					str += '</div>';
					$('#scheduleContent').html(str);
					$('#scheduleContent select').change(function(){
						var params = sets.period.specifiedTime.params;
						params.timeFrom = ""+$('#scheduleContent select[name=hourFrom]').val() +":"+$('#scheduleContent select[name=minuteFrom]').val()+":00";
						params.timeTo = ""+$('#scheduleContent select[name=hourTo]').val() +":"+$('#scheduleContent select[name=minuteTo]').val()+":00";
					});
					sets.period.specifiedTime.value = 1;
				} else if( type == 'specifiedDays' ) {
					str = '<div>';
					str += '<div>From: '+weekdayStr(curr.params.weekdayFrom.day, 'From', type)+' '+timeStr( curr.params.weekdayFrom.time, 'From', type )+' GMT</div> ';
					str += '<div>To: '+weekdayStr(curr.params.weekdayTo.day, 'To', type)+' '+timeStr( curr.params.weekdayTo.time, 'To', type )+' GMT</div>';
					str += '</div>';
					$('#scheduleContent').html(str);
					$('#scheduleContent select').change(function(){
						var params = sets.period.specifiedDays.params;
						params.weekdayFrom.time = ""+$('#scheduleContent select[name=hourFrom]').val() +":"+$('#scheduleContent select[name=minuteFrom]').val()+":00";
						params.weekdayFrom.day = $('#scheduleContent select[name=weekdayFrom]').val();
						
						params.weekdayTo.time = ""+$('#scheduleContent select[name=hourTo]').val() +":"+$('#scheduleContent select[name=minuteTo]').val()+":00";
						params.weekdayTo.day = $('#scheduleContent select[name=weekdayTo]').val()
					});
					sets.period.specifiedDays.value = 1;
				} else {
					sets.period.always.value = 1;
					$('#scheduleContent').html(str);
				}
			}
		});
	};	
	update();
	
	$('.monitis_schedule input[type=radio]').bind('click', function () {
		update();
	});
	
	var dialog = $('#monitis_notification_dialog_div').find('.monitis_dialog_content').dialog({
		width: 500,
		autoOpen: false,
		modal: true,
		buttons: {
			'save': {
				text: 'Save', class: 'btn',
				click: function() {
					save();
					$(this).dialog("close");
				}
			},
			'close': {
				text: 'Close',class: 'btn',
				click: function() {
					$(this).remove();
				}
			}
		},
		close: function() {
			$(this).remove();
		}
	});
	
	dialog.dialog('open');
	
	function groupNameById( id ) {

		var grp = groupObj.list;
		for(var i=0; i<grp.length; i++) {
			if(grp[i].id == id )
				return grp[i];
		}
		return null;
	}
	function save() {

		var json = JSON.stringify(sets);
		json = json.replace(/"/g, '~');
		var group = null;
		if( groupObj ) {
			var groupid = parseInt( $('.monitis_dialog_content .contactgroup').val() );
			var name = noalert;
			var grp = groupNameById( groupid );
			if( grp ) name = grp['name'];
			group = { id: groupid, name: name };
		}
		callback(json, group);
	}
}

//////////////
function monitisLocationDialogClass( opts, callback ) {
	var max_loc = opts.max_loc;
	var loc_ids = opts.loc_ids.split(",");
	var parentId = opts.parentId;
	
	var checkedCount = 0;

	for(var i=0; i<loc_ids.length; i++){
		loc_ids[i] = parseInt(loc_ids[i]);
	}
//console.log(loc_ids);

	(function () {
		var str = '<div class="monitisMultiselectDialog" title="Monitoring locations"><table style="width:100%;" cellpadding=10><tr><td id="maxlocationsmsgid" colspan="4">Maximum '+max_loc+' locations can be selected</td></tr><tr>';
		for (var name in countryname) {
			str += '<td style="vertical-align: top;"><div style="font-weight: bold; color: #71a9d2;">'+name+'</div><hr/>'
			var column = countryname[name];
			for (var location in column) {
				var checked = ($.inArray(column[location].id, loc_ids) !== -1) ? "checked" : "";
				str += ' <div><input type="checkbox" name="locationIDs[]" id="locationIDs" '+checked+' value="'+column[location].id + '" /> '+column[location].fullName+' </div>'
			}
			str += '</td>';
		}
		str += '</tr></table></div>';
		$('#'+parentId).html(str);
		
		monitisInitMultiselect('#'+parentId);
		
		$('.monitisMultiselectDialog input[type="checkbox"]').click(function(event) {
			var selectedCount = $('.monitisMultiselectDialog input[type=checkbox]:checked').size();
			if (selectedCount > parseInt(max_loc)) {
				event.preventDefault();
				$('#maxlocationsmsgid').css('color', '#ff0000');
			} else {
				$('#maxlocationsmsgid').css('color', '#000000')
			}
		});
	})();
	function monitisInitMultiselect(container) {
		var dialog = $(container).find(".monitisMultiselectDialog").dialog({
			width: 600,
			zIndex:1100,
			autoOpen: false,
			modal: true,
			buttons: {
				'save': {
					text: 'Save', class: 'btn',
					click: function() {
						//var ids_str = updateInput();
						callback( updateInput(), checkedCount );
						$(this).dialog("close");
					}
				},
				'close': {
					text: 'Close', class: 'btn',
					click: function() {
						$(this).dialog("close");
					}
				}
			},
			close: function() { $(this).remove(); }
		});

		dialog.dialog('open');
		function updateInput() {
			selectedCount = 0;
			var ids = [];
			$(dialog).find('input[type="checkbox"]').each(function(index) {
				if ($(this).is(':checked')) {
					selectedCount++;
					ids.push($(this).val());
				}
			});
			if (max_loc < selectedCount) {
				ids = ids.slice(0, parseInt(max_loc));

			}
			var ids_str = ids.toString();
			if (max_loc < selectedCount) {
				selectedCount = max_loc;
			}
			checkedCount = selectedCount;
			return ids_str;
		}
		updateInput();
	}
}
