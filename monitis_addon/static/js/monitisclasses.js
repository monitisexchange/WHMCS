
function monitisNotificationRuleClass( settings, mtype,  groupObj, callback, timezone ) {
	var timeZonesList = [-12,-11,-10,-9,-8,-7,-6,-5,-4,-3,-3.5,-2,-1,0,1,2,3,3.5,4,4.5,5,5.5,5.75,6,6.5,7,8,9,9.5,10,11,12,13];
	var noalert = 'no alert';
	var NotificationTitles = {
		'always':'24/7',
		'specifiedTime':monitisLang.everyday,
		'specifiedDays':monitisLang.daysOnly
	};
	var weekDays = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
	var hour = [];
	var minute = [];
	
	var sets = settings;
	if( typeof settings == 'string' ) {
		var str = settings.replace(/~/g, '"');
		sets = JSON.parse(str);
	}
	
	function add0(s){ var str=""+s; if(str.length == 1) return "0"+s; else	return ""+s; }

	(function(){
		for(var i=0; i<24; i++) { hour[hour.length] = add0( i); }
		for(var i=0; i<60; i=i+15) { minute[minute.length] = add0( i); }

		var str = '';
		str += '<form id="notificationRuleForm"><div class="monitis_dialog_content" title="'+monitisLang.alertRule+'">';
		str += '<ul class="monitis_notification_options">'

		if(typeof timezone != 'undefined') {
			str += '<li><lable>Timezone:</lable> <select name="timeZonesList">'
				for(var i=0; i<timeZonesList.length; i++) {
					var sel = '';
					if(timezone == timeZonesList[i]) sel = 'selected';
					str += '<option value="'+timeZonesList[i]+'" '+sel+'>GMT '+timeZonesList[i]+'</option>';
				}
			str += '</select></li>';
			str += '<li><lable> <hr /></lable></li>';
			sets.timeZone = timezone;
		}
		
		if( groupObj && groupObj.list ){
			var groups = groupObj.list;
			str += '<li><lable>'+monitisLang.selectContactGroup+':</lable> <select class="contactgroup">';
			str += '<option value="0"> '+noalert+' </option>';
			for(var i=0; i<groups.length; i++) {
				var sel = '';
				if( parseInt(groupObj['id']) === parseInt(groups[i]['id']) ) sel = 'selected';
				str += '<option value="'+groups[i]['id']+'" '+sel+'>'+groups[i]['name']+'</option>';
			}
			str += '</select></li>';
		}

		str += '<li><lable>'+monitisLang.alertUpon+'</lable> <select name="failureCount" class="fwDefault">';
		for(var i=1; i<6; i++) {
			var selected = ( sets.failureCount == i ) ? 'selected' : '';
			str += '<option value='+i+' '+selected+'>'+i+'</option>';
		}
		str += '</select> <lable> '+monitisLang.failureS+'</lable>';
		if(mtype == 'external'){
			str += '<lable style="padding:0px 5px;"> from</lable><select name="minFailedLocationCount" >';
			for(var i=1; i<5; i++) {
                   
				var selected = ( sets.minFailedLocationCount == i ) ? 'selected' : '';
				str += '<option value='+i+' '+selected+'>'+i+'</option>';
			}
			str += '</select><lable style="padding:0px 5px;">'+monitisLang.locationS+'</lable>';
		}
		str += '<li><lable> <hr /></lable></li>';
		
		var checked = ( sets.notifyBackup > 0 ) ? 'checked' : '';
		str += '<li><input type="checkbox" name="notifyBackup" '+checked+' /> <lable>'+monitisLang.alertFixed+'</lable></li>'
		checked = ( sets.continuousAlerts > 0 ) ? 'checked' : '';
		str += '<li><input type="checkbox" name="continuousAlerts" '+checked+' /> <lable>'+monitisLang.continuousAlerts+'</lable></li>';
		str += '<li><lable> <hr /></lable></li></ul>';

		var period = sets.period;
		str += '<div style="width:100%;"><h4>'+monitisLang.alertingSchedule+': </h4>'+
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

	// timezome
	$('.monitis_notification_options select[name=timeZonesList]').change(function(){
		sets.timeZone = parseFloat($(this).val());
	});
	
	// failureCount
	$('.monitis_notification_options select[name=failureCount]').change(function(){
		sets.failureCount = parseInt($(this).val());
	});
	
	// minFailedLocationCount
	$('.monitis_notification_options select[name=minFailedLocationCount]').change(function(){
		sets.minFailedLocationCount = parseInt($(this).val());
	});
        
	$('.monitis_notification_options input[type=checkbox]').change(function(){
		var name = $(this).attr('name');
		if( $(this).attr('checked') ) {
			sets[name] = 1;
		} else {
			sets[name] = 0;
		}
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
					str += '<div>'+monitisLang.from+': '+timeStr( curr.params.timeFrom, 'From', type )+' GMT</div> ';
					str += '<div>'+monitisLang.to+': '+timeStr( curr.params.timeTo, 'To', type )+' GMT</div>';
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
					str += '<div>'+monitisLang.from+': '+weekdayStr(curr.params.weekdayFrom.day, 'From', type)+' '+timeStr( curr.params.weekdayFrom.time, 'From', type )+' GMT</div> ';
					str += '<div>'+monitisLang.to+': '+weekdayStr(curr.params.weekdayTo.day, 'To', type)+' '+timeStr( curr.params.weekdayTo.time, 'To', type )+' GMT</div>';
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
		width: 520,
		zIndex:1100,
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

		if(groupObj.list) {
			var grp = groupObj.list;
			for(var i=0; i<grp.length; i++) {
				if(grp[i].id == id )
					return grp[i];
			}
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

$(document).ready(function() {
	$('.monitis_link_result a, .monitis_link_button, a.monitis_link_result').click(function(){
		try {
			monitisModal.open({content: null});
		} catch(ex) {}
	});
});

var monitisModal = (function(){
	var 
	method = {},
	$overlay,
	$modal,
	$content,
	$close;

	// Center the modal in the viewport
	method.center = function () {
		var top, left;
		top = Math.max($(window).height() - $modal.outerHeight(), 0) / 2;
		left = Math.max($(window).width() - $modal.outerWidth(), 0) / 2;

		$modal.css({
			top:top + $(window).scrollTop(), 
			left:left + $(window).scrollLeft()
		});
	};

	// Open the modal
	method.open = function (settings) {
		$content.empty().append(settings.content);
		$modal.css({
			width: settings.width || 'auto', 
			height: settings.height || 'auto'
		});
		method.center();
		$(window).bind('resize.modal', method.center);
		$modal.show();
		$overlay.show();
	};

	// Close the modal
	method.close = function () {
		$modal.hide();
		$overlay.hide();
		$content.empty();
		$(window).unbind('resize.modal');
	};

	// Generate the HTML and add it to the document
	$overlay = $('<div id="monitis_modal_overlay"></div>');
	$modal = $('<div id="monitis_modal"></div>');
	$content = $('<div id="monitis_modal_content"></div>');
	$close = $('<a id="monitis_modal_close" href="#"></a>');

	$modal.hide();
	$overlay.hide();
	$modal.append($content, $close);

	$(document).ready(function(){
		$('body').append($overlay, $modal);	
	});

	$close.click(function(e){
		e.preventDefault();
		method.close();
	});

	return method;
}());